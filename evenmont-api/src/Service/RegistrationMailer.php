<?php
// src/Service/RegistrationMailer.php
namespace App\Service;

use App\Entity\User;
use App\Entity\Event;
use App\Entity\EventRegistration;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class RegistrationMailer
{
    public function __construct(private MailerInterface $mailer) {}

    public function sendUserConfirmation(User $user, Event $event, EventRegistration $reg): void
    {
        $email = (new TemplatedEmail())
            ->from('no-reply@evenmont.com')
            ->to($user->getEmail())
            ->subject('Inscription confirmée — ' . $event->getTitle())
            ->htmlTemplate('emails/registration_user.html.twig')
            ->context([
                'user' => $user,
                'event' => $event,
                'reg' => $reg,
            ]);
        try {
            $this->mailer->send($email);
        } catch (\Throwable $e) {
            // log ou ignore en dev, mais ne bloque pas l'inscription
        }
    }

    public function sendOrganizerNotification(Event $event, EventRegistration $reg): void
    {
        $orga = $event->getOrganizer();
        if (!$orga || !$orga->getEmail()) return;

        $email = (new TemplatedEmail())
            ->from('no-reply@evenmont.com')
            ->to($orga->getEmail())
            ->subject('Nouvelle inscription — ' . $event->getTitle())
            ->htmlTemplate('emails/registration_organizer.html.twig')
            ->context([
                'event' => $event,
                'reg' => $reg,
                'user' => $reg->getUser(),
            ]);
        try {
            $this->mailer->send($email);
        } catch (\Throwable $e) {
            // log ou ignore en dev, mais ne bloque pas l'inscription
        }
    }

    public function sendUserCancellation(User $user, Event $event, EventRegistration $reg): void
    {
        $email = (new TemplatedEmail())
            ->from('no-reply@evenmont.com')
            ->to($user->getEmail())
            ->subject('Inscription annulée — ' . $event->getTitle())
            ->htmlTemplate('emails/registration_cancel.html.twig')
            ->context(compact('user', 'event', 'reg'));
        try {
            $this->mailer->send($email);
        } catch (\Throwable $e) {
            // log ou ignore en dev, mais ne bloque pas l'inscription
        }
    }
}
