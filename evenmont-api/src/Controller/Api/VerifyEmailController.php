<?php
// src/Controller/Api/VerifyEmailController.php
namespace App\Controller\Api;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;

final class VerifyEmailController
{
    #[Route('/api/verify-email', name: 'api_verify_email', methods: ['GET'])]
    public function __invoke(Request $req, UserRepository $users, EntityManagerInterface $em): Response
    {
        $token = (string) $req->query->get('token', '');
        if ($token === '') {
            return new Response('<h1>Lien invalide</h1>', 400);
        }

        $u = $users->findOneBy(['verifyToken' => $token]);
        if (!$u) {
            return new Response('<h1>Token inconnu</h1>', 404);
        }

        $exp = $u->getVerifyTokenExpiresAt();
        if ($exp && $exp < new \DateTimeImmutable()) {
            return new Response('<h1>Token expiré</h1><p>Recommence l’inscription.</p>', 410);
        }

        $u->setEmailVerifiedAt(new \DateTimeImmutable());
        $u->setVerifyToken(null);
        $u->setVerifyTokenExpiresAt(null);
        $em->flush();

        $html = '<h1>Email vérifié</h1><p>Tu peux maintenant te connecter : <a href="http://localhost:5173/login">login</a>.</p>';
        return new Response($html, 200);
    }
}
