<?php
// src/Controller/Api/RegistrationController.php
namespace App\Controller\Api;

use App\Entity\Event;
use App\Entity\EventRegistration;
use App\Repository\EventRegistrationRepository;
use App\Service\RegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class RegistrationController extends AbstractController
{
    public function __construct(
        private RegistrationService $svc,
        private EntityManagerInterface $em,
        private EventRegistrationRepository $regs,
    ) {}

    #[Route('/registrations', name: 'api_registrations_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $p = json_decode($request->getContent(), true) ?? [];
        $eventId = isset($p['eventId']) ? (int)$p['eventId'] : null;
        if (!$eventId) return $this->json(['error' => 'event_required'], 400);

        $event = $this->em->find(Event::class, $eventId);
        if (!$event) return $this->json(['error' => 'event_not_found'], 404);

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        try {
            $res = $this->svc->register($user, $event);
            $r = $res['registration'];
            return $this->json([
                'id'     => $r->getId(),
                'status' => $r->getStatus(),
                'created' => $res['created'],
            ], $res['created'] ? 201 : 200);
        } catch (\RuntimeException $e) {
            $map = [
                'event_past' => [422, 'L’événement est passé.'],
                'organizer_cannot_register' => [403, 'Un organisateur ne peut pas s’inscrire à son propre événement.'],
                'event_full' => [409, 'Événement complet.'],
            ];
            $m = $map[$e->getMessage()] ?? [400, 'registration_error'];
            return $this->json(['error' => $e->getMessage(), 'message' => $m[1]], $m[0]);
        }
    }

    #[Route('/registrations/me', name: 'api_registrations_me', methods: ['GET'])]
    public function mine(): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $rows = $this->regs->createQueryBuilder('r')
            ->select('r', 'e')
            ->join('r.event', 'e')
            ->andWhere('r.user = :u')->setParameter('u', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()->getResult();

        $out = [];
        foreach ($rows as $r) {
            /** @var EventRegistration $r */
            $e = $r->getEvent();
            $out[] = [
                'id' => $r->getId(),
                'status' => $r->getStatus(),
                'createdAt' => $r->getCreatedAt()->format(DATE_ATOM),
                'event' => [
                    'id' => $e->getId(),
                    'title' => $e->getTitle(),
                    'startAt' => $e->getStartAt()?->format(DATE_ATOM),
                ],
            ];
        }
        return $this->json(['items' => $out]);
    }

    #[Route('/registrations/{id}', name: 'api_registrations_cancel', methods: ['DELETE'])]
    public function cancel(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $reg = $this->em->find(EventRegistration::class, $id);
        if (!$reg) return $this->json(['error' => 'not_found'], 404);

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        try {
            $this->svc->cancel($user, $reg);
            return $this->json(['ok' => true]);
        } catch (\RuntimeException $e) {
            $code = $e->getMessage() === 'forbidden' ? 403 : 422;
            return $this->json(['error' => $e->getMessage()], $code);
        }
    }
}
