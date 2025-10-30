<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class MyEventsController extends AbstractController
{
    /** Utilitaire: chemin relatif -> absolu */
    private function abs(string $host, ?string $path): ?string
    {
        if (!$path) return null;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) return $path;
        return rtrim($host, '/') . $path;
    }

    #[Route('/my/events', name: 'api_my_events', methods: ['GET'])]
    public function myEvents(Request $req, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $u = $this->getUser();
        if (!$u instanceof User || !in_array('ROLE_ORGANIZER', $u->getRoles(), true)) {
            return $this->json(['error' => 'Organizer required'], 403);
        }

        $page  = max(1, (int)$req->query->get('page', 1));
        $limit = min(50, max(1, (int)$req->query->get('limit', 6)));
        $off   = ($page - 1) * $limit;

        $repo = $em->getRepository(Event::class);
        $qb = $repo->createQueryBuilder('e')
            ->andWhere('e.organizer = :me')->setParameter('me', $u);

        $total = (int)(clone $qb)->select('COUNT(e.id)')->getQuery()->getSingleScalarResult();

        // tri: à venir d'abord, puis passés (les plus récents avant les plus anciens)
        $now = new \DateTimeImmutable('now');
        $qb
            ->addSelect('(CASE WHEN e.startAt >= :now THEN 0 ELSE 1 END) AS HIDDEN past_rank')
            ->setParameter('now', $now)
            ->orderBy('past_rank', 'ASC')     // 0 (à venir) puis 1 (passé)
            ->addOrderBy('e.startAt', 'ASC'); // à venir: du plus proche au plus lointain

        $rows = $qb
            ->setFirstResult($off)->setMaxResults($limit)
            ->getQuery()->getResult();

        $host = $req->getSchemeAndHttpHost();

        $items = array_map(function (Event $e) use ($u, $host) {
            return [
                'id'          => $e->getId(),
                'title'       => $e->getTitle(),
                'city'        => $e->getCity(),
                'description' => $e->getDescription(),
                'startAt'     => $e->getStartAt()?->format(DATE_ATOM),
                'endAt'       => $e->getEndAt()?->format(DATE_ATOM),
                'image'       => $this->abs($host, $e->getCoverUrl()) ?? '/img/demo.jpg',
                'coverUrl'    => $e->getCoverUrl(), // brut si besoin
                'organizerId' => $u->getId(),
                // Si tu affiches des tags sur les cards :
                'categories'  => array_map(
                    fn($et) => [
                        'slug' => $et->getTag()->getSlug(),
                        'name' => $et->getTag()->getName(),
                    ],
                    $e->getEventTags()->toArray()
                ),
            ];
        }, $rows);

        return $this->json([
            'items' => $items,
            'total' => $total,
            'page'  => $page,
            'pages' => max(1, (int)ceil($total / $limit)),
        ]);
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

        $now = new \DateTimeImmutable('now');
        $rows = $qb
            ->addSelect('(CASE WHEN e.startAt >= :now THEN 0 ELSE 1 END) AS HIDDEN past_rank')
            ->setParameter('now', $now)
            ->orderBy('past_rank', 'ASC')
            ->addOrderBy('e.startAt', 'ASC')
            ->setFirstResult($off)->setMaxResults($limit)
            ->getQuery()->getResult();

        $host = $req->getSchemeAndHttpHost();

        $items = [];
        foreach ($rows as $row) {
            $r = is_array($row) ? $row[0] : $row; // Doctrine peut renvoyer [r, past_rank]
            $e = $r->getEvent();
            $items[] = [
                'registrationId' => $r->getId(),
                'status'         => $r->getStatus(),
                'createdAt'      => $r->getCreatedAt()?->format(DATE_ATOM),
                'event' => [
                    'id'       => $e->getId(),
                    'title'    => $e->getTitle(),
                    'city'     => $e->getCity(),
                    'startAt'  => $e->getStartAt()?->format(DATE_ATOM),
                    'endAt'    => $e->getEndAt()?->format(DATE_ATOM),
                    'image'    => $this->abs($host, $e->getCoverUrl()) ?? '/img/demo.jpg',
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
}
