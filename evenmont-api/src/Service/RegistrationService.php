<?php
namespace App\Service;

use App\Entity\Event;
use App\Entity\User;
use App\Entity\EventRegistration;
use App\Repository\EventRegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\RegistrationMailer;

class RegistrationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private EventRegistrationRepository $regs,
        private RegistrationMailer $mailer, // on le crée en §3
    ) {}

    /** @return array{registration: EventRegistration, created: bool} */
    public function register(User $user, Event $event): array
    {
        // 1) Garde-fous
        if ($event->isPast()) {
            throw new \RuntimeException('event_past');
        }
        if ($event->getOrganizer() && $event->getOrganizer()->getId() === $user->getId()) {
            throw new \RuntimeException('organizer_cannot_register');
        }
        // capacité (si tu l’utilises) => on compte uniquement confirmed (ou pending, à ta convenance)
        if ($event->getCapacity()) {
            $count = $this->regs->countByEventAndStatus($event, ['confirmed']);
            if ($count >= $event->getCapacity()) {
                throw new \RuntimeException('event_full');
            }
        }

        // 2) Idempotence (unique(event,user))
        $existing = $this->regs->findOneBy(['event' => $event, 'user' => $user]);
        if ($existing) {
            return ['registration' => $existing, 'created' => false];
        }

        // 3) Création
        $reg = (new EventRegistration())
            ->setEvent($event)
            ->setUser($user)
            ->setStatus('confirmed');

        $this->em->persist($reg);
        $this->em->flush();

        // 4) Emails
        $this->mailer->sendUserConfirmation($user, $event, $reg);
        $this->mailer->sendOrganizerNotification($event, $reg);

        return ['registration' => $reg, 'created' => true];
    }

    public function cancel(User $user, EventRegistration $reg): void
    {
        if ($reg->getUser()->getId() !== $user->getId()) {
            throw new \RuntimeException('forbidden');
        }
        if ($reg->getEvent()->isPast()) {
            throw new \RuntimeException('event_past');
        }
        $reg->setStatus('cancelled');
        $this->em->flush();

        // (option) mail d’annulation
        $this->mailer->sendUserCancellation($user, $reg->getEvent(), $reg);
    }
}
