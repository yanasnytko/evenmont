<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Entity\EventRegistration;
use App\Entity\Payment;
use App\Entity\User;
use App\Service\MollieClientFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

#[Route('/api')]
class PaymentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private MollieClientFactory $mollieFactory,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
    ) {}

    #[Route('/events/{id}/pay', name: 'api_event_pay', methods: ['POST'])]
    public function createPayment(int $id, Request $req): JsonResponse
    {
        $u = $this->getUser();
        if (!$u instanceof User) return $this->json(['error' => 'auth_required'], 401);

        $event = $this->em->getRepository(Event::class)->find($id);
        if (!$event) return $this->json(['error' => 'event_not_found'], 404);

        // Ex: 10€ par ticket — adapte (prix stocké sur Event ?)
        $priceStr = $event->getPrice(); // "12.00" ou null
        $amount = $priceStr !== null
            ? number_format((float)$priceStr, 2, '.', '')
            : '0.00'; // si tu veux refuser 0 => retourne une 400 ici
        if ($amount === '0.00') {
            return $this->json(['error' => 'event_free'], 400);
        }

        // Si déjà inscrit·e confirmé·e, inutile de payer
        $regRepo = $this->em->getRepository(EventRegistration::class);
        $exists = $regRepo->findOneBy(['event' => $event, 'user' => $u]);
        if ($exists) {
            return $this->json(['message' => 'already_registered'], 200);
        }

        // Optionnel: check capacité soft
        if (null !== $event->getCapacity()) {
            $count = $regRepo->count(['event' => $event]);
            if ($count >= $event->getCapacity()) {
                return $this->json(['error' => 'event_full'], 409);
            }
        }

        $payment = (new Payment())
            ->setProvider('mollie')
            ->setUser($u)->setEvent($event)
            ->setAmount($amount)->setCurrency('EUR')
            ->setStatus('open')
            ->setMetadata(['userId' => $u->getId(), 'eventId' => $event->getId()]);
        $this->em->persist($payment);
        $this->em->flush();

        $publicBase   = $_ENV['APP_BASE_URL'] ?? $req->getSchemeAndHttpHost();
        $webhookBase  = $_ENV['MOLLIE_WEBHOOK_BASE_URL'] ?? $publicBase;
        $frontendBase = $_ENV['FRONTEND_BASE_URL'] ?? $publicBase;

        $returnUrl = rtrim($frontendBase, '/') . '/payments/return?pid=' . $payment->getId() . '&event=' . $event->getId();
        $webhookUrl = rtrim($webhookBase,  '/') . '/api/payments/webhook';

        $title = (string) ($event->getTitle() ?? ('Event #' . $event->getId()));
        $description = 'Evenmont — ' . $title;
        try {
            $mollie = $this->mollieFactory->client();
            $mp = $mollie->payments->create([
                'amount' => ['currency' => 'EUR', 'value' => $amount],
                'description' => $description,
                'redirectUrl' => $returnUrl,
                'webhookUrl'  => $webhookUrl,
                'metadata'    => ['paymentId' => $payment->getId()],
                // 'method' => ['bancontact','ideal'] // optionnel
            ]);
        } catch (\Mollie\Api\Exceptions\ApiException $e) {
            return $this->json([
                'error'   => 'payment_create_failed',
                'message' => $e->getMessage(),
            ], 422);
        }

        $payment->setMollieId($mp->id);
        $payment->setCheckoutUrl($mp->getCheckoutUrl());
        $this->em->flush();

        return $this->json([
            'paymentId' => $payment->getId(),
            'checkoutUrl' => $payment->getCheckoutUrl(),
            'status' => $payment->getStatus(),
        ], 201);
    }

    #[Route('/payments/webhook', name: 'api_payments_webhook', methods: ['POST'])]
    public function webhook(Request $req): JsonResponse
    {
        $this->logger->info('Webhook hit', ['raw' => $req->request->all()]);
        $id = $req->request->get('id'); // Mollie envoie payment[id]
        if (!$id) return $this->json(['ok' => true]); // ignore

        $mollie = $this->mollieFactory->client();
        $mp = $mollie->payments->get($id);

        $payment = $this->em->getRepository(Payment::class)->findOneBy(['mollieId' => $mp->id]);
        if (!$payment) return $this->json(['ok' => true]);

        $old = $payment->getStatus();
        $new = $mp->status; // paid|open|canceled|failed|expired|...
        if ($old === $new) return $this->json(['ok' => true]);

        $payment->setStatus($new);
        if ($new === 'paid') {
            $payment->setPaidAt(new \DateTimeImmutable());

            // Idempotent: si déjà inscrit, ne pas dupliquer
            $u = $payment->getUser();
            $event = $payment->getEvent();
            $repo = $this->em->getRepository(EventRegistration::class);
            $exists = $repo->findOneBy(['event' => $event, 'user' => $u]);

            if (!$exists) {
                // Vérif capacité (hard) au moment critique
                if (null !== $event->getCapacity()) {
                    $count = $repo->count(['event' => $event]);
                    if ($count >= $event->getCapacity()) {
                        // Ici: soit rembourser (à implémenter), soit marquer failed.
                        $payment->setStatus('failed');
                        $this->em->flush();
                        return $this->json(['ok' => true]);
                    }
                }

                $reg = (new EventRegistration())
                    ->setEvent($event)
                    ->setUser($u)
                    ->setStatus('confirmed')
                    ->setCreatedAt(new \DateTimeImmutable());
                $this->em->persist($reg);
                $this->em->flush();

                // Email confirmation (utilise ton style AccountController)
                if ($u->getEmail()) {
                    try {
                        $msg = (new Email())
                            ->from('no-reply@evenmont.com')
                            ->to($u->getEmail())
                            ->subject('Inscription confirmée — ' . $event->getTitle())
                            ->html('<p>Merci pour votre paiement. Inscription confirmée à <strong>' .
                                htmlspecialchars((string)$event->getTitle()) . '</strong>.</p>');
                        $this->mailer->send($msg);
                    } catch (\Throwable $e) {
                    }
                }
            }
        }

        $this->em->flush();
        return $this->json(['ok' => true]);
    }

    #[Route('/payments/{id}', name: 'api_payments_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $u = $this->getUser();
        if (!$u instanceof User) return $this->json(['error' => 'auth_required'], 401);

        $p = $this->em->getRepository(Payment::class)->find($id);
        if (!$p || $p->getUser()->getId() !== $u->getId()) return $this->json(['error' => 'not_found'], 404);

        return $this->json([
            'id' => $p->getId(),
            'status' => $p->getStatus(),
            'checkoutUrl' => $p->getCheckoutUrl(),
            'amount' => $p->getAmount(),
            'currency' => $p->getCurrency(),
            'eventId' => $p->getEvent()->getId(),
        ]);
    }

    #[Route('/payments/{id}/finalize', name: 'api_payments_finalize', methods: ['POST'])]
    public function finalize(int $id): JsonResponse
    {
        $this->logger->info('Finalize hit', ['paymentId' => $id]);
        /** @var Payment|null $payment */
        $payment = $this->em->getRepository(Payment::class)->find($id);
        if (!$payment) return $this->json(['error' => 'not_found'], 404);

        // On ne dépend PAS de la session ici
        $u = $payment->getUser();
        $event = $payment->getEvent();
        if (!$u instanceof User || !$event instanceof Event) {
            return $this->json(['error' => 'payment_links_missing'], 400);
        }

        if (!$payment->getMollieId()) {
            return $this->json(['error' => 'no_mollie_id'], 400);
        }

        // 1) Récupérer le statut live chez Mollie
        $mollie = $this->mollieFactory->client();
        try {
            $mp = $mollie->payments->get($payment->getMollieId());
        } catch (\Mollie\Api\Exceptions\ApiException $e) {
            // Erreur côté API Mollie → renvoie une 502 lisible au front
            return $this->json([
                'error'   => 'mollie_fetch_failed',
                'message' => $e->getMessage(),
            ], 502);
        } catch (\Throwable $e) {
            // Toute autre erreur non prévue → 500 mais lisible
            return $this->json([
                'error'   => 'finalize_crash',
                'message' => $e->getMessage(),
            ], 500);
        }
        $new = $mp->status; // paid | open | canceled | failed | expired | ...
        $old = $payment->getStatus();

        if ($old !== $new) {
            $payment->setStatus($new);
            if ($new === 'paid' && method_exists($payment, 'setPaidAt')) {
                $payment->setPaidAt(new \DateTimeImmutable());
            }
        }

        // 2) Si payé → créer l'inscription si elle n'existe pas (idempotent)
        $registered = false;
        if ($new === 'paid') {
            $repo = $this->em->getRepository(EventRegistration::class);
            $exists = $repo->findOneBy(['event' => $event, 'user' => $u]);

            if (!$exists) {
                // Hard check capacité
                if (null !== $event->getCapacity()) {
                    $count = $repo->count(['event' => $event]);
                    if ($count >= $event->getCapacity()) {
                        $payment->setStatus('failed');
                        $this->em->flush();
                        return $this->json([
                            'status'     => 'failed',
                            'registered' => false,
                            'reason'     => 'event_full',
                        ], 409);
                    }
                }

                try {
                    $reg = (new EventRegistration())
                        ->setEvent($event)
                        ->setUser($u)
                        ->setStatus('confirmed')
                        ->setCreatedAt(new \DateTimeImmutable());
                    $this->em->persist($reg);
                    $this->em->flush();
                    $registered = true;
                } catch (\Throwable $e) {
                    // Renvoie proprement au front au lieu d’un 500 opaque
                    return $this->json([
                        'error'   => 'registration_create_failed',
                        'message' => $e->getMessage(),
                    ], 500);
                }

                // Email (déjà dans un try/catch, garde-le)
                if ($u->getEmail()) {
                    try {
                        $msg = (new Email())
                            ->from('no-reply@evenmont.com')
                            ->to($u->getEmail())
                            ->subject('Inscription confirmée — ' . ($event->getTitle() ?: 'EvenMont'))
                            ->html('<p>Merci pour votre paiement. Inscription confirmée à <strong>' .
                                htmlspecialchars((string)$event->getTitle()) . '</strong>.</p>');
                        $this->mailer->send($msg);
                    } catch (\Throwable $e) { /* ignore en dev */
                    }
                }
            } else {
                $registered = true;
            }
        }

        $this->em->flush();

        return $this->json([
            'status'      => $payment->getStatus(),
            'registered'  => $registered,
            'paymentId'   => $payment->getId(),
            'eventId'     => $event->getId(),
        ]);
    }
}
