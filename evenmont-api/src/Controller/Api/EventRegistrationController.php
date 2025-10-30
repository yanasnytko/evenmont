<?php
// src/Controller/Api/EventRegistrationController.php
namespace App\Controller\Api;

use App\Entity\Event;
use App\Entity\EventRegistration;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
final class EventRegistrationController extends AbstractController
{
    #[Route('/events/{id}/registrations', name: 'api_event_registrations_create', methods: ['POST'])]
    public function create(
        int $id,
        Request $req,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): JsonResponse {
        $u = $this->getUser();
        if (!$u instanceof User) {
            return $this->json(['error' => 'auth_required'], 401);
        }

        $event = $em->getRepository(Event::class)->find($id);
        if (!$event) return $this->json(['error' => 'event_not_found'], 404);

        // L’organisateur ne peut pas s’inscrire
        if ($event->getOrganizer() && $event->getOrganizer()->getId() === $u->getId()) {
            return $this->json(['error' => 'organizer_cannot_register'], 400);
        }

        // Événement passé ?
        if ($event->getStartAt() && $event->getStartAt() < new \DateTimeImmutable()) {
            return $this->json(['error' => 'event_past'], 400);
        }

        // Déjà inscrit ?
        $repo = $em->getRepository(EventRegistration::class);
        $existing = $repo->findOneBy(['event' => $event, 'user' => $u]);
        if ($existing) {
            return $this->json([
                'id' => $existing->getId(),
                'status' => $existing->getStatus(),
                'message' => 'already_registered',
            ], 200);
        }

        // Capacité ?
        if (null !== $event->getCapacity()) {
            $count = $repo->count(['event' => $event]); // simple & efficace
            if ($count >= $event->getCapacity()) {
                return $this->json(['error' => 'event_full'], 400);
            }
        }

        // Création
        $reg = (new EventRegistration())
            ->setEvent($event)
            ->setUser($u)
            ->setStatus('confirmed') // ou 'pending' si paiement plus tard
            ->setCreatedAt(new \DateTimeImmutable());
        $em->persist($reg);
        $em->flush();

        // Email de confirmation à l’inscrit
        if ($u->getEmail()) {
            $emailUser = (new TemplatedEmail())
                ->from('no-reply@evenmont.com')
                ->to($u->getEmail())
                ->subject('Inscription confirmée — ' . $event->getTitle())
                ->html("<p>Merci ! Tu es inscrit à <strong>" . htmlspecialchars($event->getTitle()) . "</strong>.</p>");
            try {
                $mailer->send($emailUser);
            } catch (\Throwable $e) {
                // log ou ignore en dev, mais ne bloque pas l'inscription
            }
        }

        // Email à l’organisateur (facultatif)
        $org = $event->getOrganizer();
        if ($org && $org->getEmail()) {
            $emailOrg = (new TemplatedEmail())
                ->from('no-reply@evenmont.com')
                ->to($org->getEmail())
                ->subject('Nouvelle inscription à ton événement')
                ->html("<p>" . htmlspecialchars($u->getEmail()) . " s'est inscrit à <strong>" . htmlspecialchars($event->getTitle()) . "</strong>.</p>");
            try {
                $mailer->send($emailOrg);
            } catch (\Throwable $e) {
                // log ou ignore en dev, mais ne bloque pas l'inscription
            }
        }

        return $this->json([
            'id' => $reg->getId(),
            'status' => $reg->getStatus(),
            'message' => 'registered',
        ], 201);
    }

    // (Optionnel) annulation de l’inscription par l’utilisateur
    #[Route('/registrations/{id}', name: 'api_event_registrations_delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $u = $this->getUser();
        if (!$u instanceof User) return $this->json(['error' => 'auth_required'], 401);

        $reg = $em->getRepository(EventRegistration::class)->find($id);
        if (!$reg) return $this->json(['error' => 'not_found'], 404);
        if ($reg->getUser()->getId() !== $u->getId()) {
            return $this->json(['error' => 'forbidden'], 403);
        }

        $em->remove($reg);
        $em->flush();
        return $this->json(['ok' => true]);
    }

    #[Route('/my/registrations', name: 'api_my_registrations', methods: ['GET'])]
    public function myRegistrations(Request $req, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $u = $this->getUser();
        if (!$u instanceof User) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $page  = max(1, (int)$req->query->get('page', 1));
        $limit = min(50, max(1, (int)$req->query->get('limit', 6)));
        $off   = ($page - 1) * $limit;

        $qb = $em->createQueryBuilder()
            ->select('r', 'e')
            ->from('App\Entity\EventRegistration', 'r')
            ->join('r.event', 'e')
            ->andWhere('r.user = :me')->setParameter('me', $u);

        $total = (int)(clone $qb)->select('COUNT(r.id)')->getQuery()->getSingleScalarResult();

        $now  = new \DateTimeImmutable();
        $rows = $qb
            ->addSelect('(CASE WHEN e.startAt >= :now THEN 0 ELSE 1 END) AS HIDDEN past_rank')
            ->setParameter('now', $now)
            ->orderBy('past_rank', 'ASC')
            ->addOrderBy('e.startAt', 'ASC')
            ->setFirstResult($off)->setMaxResults($limit)
            ->getQuery()->getResult();

        $host  = $req->getSchemeAndHttpHost();
        $items = [];
        foreach ($rows as $row) {
            $r = is_array($row) ? $row[0] : $row;      // r = EventRegistration
            $e = $r->getEvent();

            $items[] = [
                // champs “EventCard”
                'id'          => $e->getId(),
                'title'       => (string)$e->getTitle(),
                'city'        => (string)($e->getCity() ?? ''),
                'description' => (string)($e->getDescription() ?? ''),
                'startAt'     => $e->getStartAt()?->format(DATE_ATOM),
                'endAt'       => $e->getEndAt()?->format(DATE_ATOM),
                'image'       => $e->getCoverUrl() && !str_starts_with($e->getCoverUrl(), 'http')
                    ? $host . $e->getCoverUrl()
                    : ($e->getCoverUrl() ?: '/img/demo.jpg'),
                'organizerId' => $e->getOrganizer()?->getId(),
                // méta d’inscription utiles (si tu veux afficher un badge)
                'registration' => [
                    'id'        => $r->getId(),
                    'status'    => $r->getStatus(),
                    'createdAt' => $r->getCreatedAt()?->format(DATE_ATOM),
                ],
            ];
        }

        return $this->json([
            'items' => $items,
            'total' => $total,
            'page'  => $page,
            'pages' => max(1, (int)ceil($total / $limit)),
        ]);
    }
    #[Route('/events/{id}/registrations/me', name: 'api_event_registrations_me', methods: ['GET'])]
    public function me(int $id, EntityManagerInterface $em): JsonResponse
    {
        $u = $this->getUser();
        if (!$u instanceof \App\Entity\User) return $this->json(['error' => 'auth_required'], 401);

        $event = $em->getRepository(\App\Entity\Event::class)->find($id);
        if (!$event) return $this->json(['error' => 'event_not_found'], 404);

        $reg = $em->getRepository(\App\Entity\EventRegistration::class)
            ->findOneBy(['event' => $event, 'user' => $u]);

        return $this->json([
            'registered' => (bool)$reg,
            'registrationId' => $reg?->getId(),
            'status' => $reg?->getStatus(),
        ]);
    }
    #[Route('/events/{id}/registrations', name: 'api_event_registrations_delete_by_event', methods: ['DELETE'])]
    public function deleteByEvent(
        int $id,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): JsonResponse {
        $u = $this->getUser();
        if (!$u instanceof User) return $this->json(['error' => 'auth_required'], 401);

        $event = $em->getRepository(Event::class)->find($id);
        if (!$event) return $this->json(['error' => 'event_not_found'], 404);

        $reg = $em->getRepository(EventRegistration::class)
            ->findOneBy(['event' => $event, 'user' => $u]);
        if (!$reg) return $this->json(['message' => 'not_registered'], 200);

        $em->remove($reg);
        $em->flush(); // → libère la place

        // Mail de confirmation à l’utilisateur
        if ($u->getEmail()) {
            $email = (new TemplatedEmail())
                ->from('no-reply@evenmont.com')
                ->to($u->getEmail())
                ->subject('Désinscription confirmée — ' . $event->getTitle())
                ->html(sprintf(
                    '<p>Bonjour %s,</p>
                 <p>Votre désinscription à <strong>%s</strong> (%s → %s) est confirmée.</p>
                 <p>À bientôt sur <strong>Evenmont</strong> !</p>',
                    htmlspecialchars($u->getEmail()),
                    htmlspecialchars((string)$event->getTitle()),
                    $event->getStartAt()?->format('d/m/Y H:i'),
                    $event->getEndAt()?->format('d/m/Y H:i')
                ));
            try {
                $mailer->send($email);
            } catch (\Throwable $e) {
                // log ou ignore en dev, mais ne bloque pas la désinscription
            }
        }

        return $this->json(['registered' => false, 'message' => 'unregistered'], 200);
    }
}