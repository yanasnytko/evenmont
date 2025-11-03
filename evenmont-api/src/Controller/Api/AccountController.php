<?php
// src/Controller/Api/AccountController.php
namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, JsonResponse};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Route('/api')]
final class AccountController extends AbstractController
{
    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $req,
        EntityManagerInterface $em,
        UserRepository $users,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $hasher,
        MailerInterface $mailer,
    ): JsonResponse {
        $p = str_contains($req->headers->get('content-type', ''), 'application/json')
            ? (json_decode($req->getContent(), true) ?? [])
            : $req->request->all();

        $email = strtolower(trim((string)($p['email'] ?? '')));
        $password = (string)($p['password'] ?? '');
        $first = trim((string)($p['firstName'] ?? ''));
        $last  = trim((string)($p['lastName'] ?? ''));
        $asOrganizer = filter_var($p['organizer'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($email === '' || $password === '') {
            return $this->json(['error' => 'email_and_password_required'], 422);
        }
        if ($users->findOneBy(['email' => $email])) {
            return $this->json(['error' => 'email_already_used'], 409);
        }
        if (strlen($password) < 8) {
            return $this->json(['error' => 'password_too_short', 'min' => 8], 422);
        }

        $u = new User();
        $u->setEmail($email);
        $u->setFirstName($first ?: null);
        $u->setLastName($last ?: null);
        $u->setRoles($asOrganizer ? ['ROLE_ORGANIZER'] : ['ROLE_USER']);
        $u->setPassword($hasher->hashPassword($u, $password));

        // token de vérif 24h
        $token = bin2hex(random_bytes(24));
        $u->setVerifyToken($token);
        $u->setVerifyTokenExpiresAt(new \DateTimeImmutable('+24 hours'));

        $errors = $validator->validate($u);
        if (\count($errors)) {
            return $this->json(['error' => 'invalid_user', 'violations' => (string) $errors], 422);
        }

        $em->persist($u);
        $em->flush();

        // lien de vérification (backend)
        $verifyUrl = $req->getSchemeAndHttpHost() . '/api/verify-email?token=' . $token;

        try {
            $msg = (new Email())
                ->from('no-reply@evenmont.com')
                ->to($u->getEmail())
                ->subject('Vérifie ton email — EvenMont')
                ->html(<<<HTML
                    <p>Bonjour {$this->escape($u->getFirstName() ?: '👋')},</p>
                    <p>Bienvenue sur <strong>EvenMont</strong> !</p>
                    <p>Merci de confirmer ton adresse email :</p>
                    <p><a href="$verifyUrl" style="display:inline-block;padding:10px 18px;background:#2b7a2b;color:#fff;border-radius:6px;text-decoration:none;font-weight:600">Confirmer mon inscription</a></p>
                    <p>(lien valable 24h)</p>
                HTML);
            $mailer->send($msg);
        } catch (\Throwable $e) {
            // on n'échoue pas l'inscription si l'email tombe; log en prod
        }

        return $this->json([
            'id'        => $u->getId(),
            'email'     => $u->getEmail(),
            'roles'     => $u->getRoles(),
            'firstName' => $u->getFirstName(),
            'lastName'  => $u->getLastName(),
            'emailVerified' => false,
        ], 201);
    }

    // petite aide pour l'HTML d'email
    private function escape(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

        #[Route('/resend-verification', name: 'api_resend_verification', methods: ['POST'])]
        public function resendVerification(Request $req, UserRepository $users, MailerInterface $mailer): JsonResponse
        {
            $p = str_contains($req->headers->get('content-type', ''), 'application/json')
                ? (json_decode($req->getContent(), true) ?? [])
                : $req->request->all();
            $email = strtolower(trim((string)($p['email'] ?? '')));
            if ($email === '') {
                return $this->json(['error' => 'email_required'], 422);
            }
            $u = $users->findOneBy(['email' => $email]);
            if (!$u || $u->getEmailVerifiedAt()) {
                return $this->json(['error' => 'user_not_found_or_already_verified'], 404);
            }
            // Regénère le token si expiré
            $token = $u->getVerifyToken();
            $exp = $u->getVerifyTokenExpiresAt();
            if (!$token || !$exp || $exp < new \DateTimeImmutable()) {
                $token = bin2hex(random_bytes(24));
                $u->setVerifyToken($token);
                $u->setVerifyTokenExpiresAt(new \DateTimeImmutable('+24 hours'));
                $users->getEntityManager()->flush();
            }
            $verifyUrl = $req->getSchemeAndHttpHost() . '/api/verify-email?token=' . $token;
            try {
                $msg = (new Email())
                    ->from('no-reply@evenmont.com')
                    ->to($u->getEmail())
                    ->subject('Vérifie ton email — EvenMont')
                    ->html('<p>Bonjour,</p><p>Merci de confirmer ton adresse email :</p><p><a href="'.$verifyUrl.'">'.$verifyUrl.'</a></p><p>(lien valable 24h)</p>');
                $mailer->send($msg);
            } catch (\Throwable $e) {
                return $this->json(['error' => 'mail_failed'], 500);
            }
            return $this->json(['success' => true]);
        }
}
