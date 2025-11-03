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

        $html = '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="2;url=https://isl.yanasnytko.com/login"><title>Email vérifié</title><style>body{font-family:sans-serif;background:#f6f6f6;text-align:center;padding-top:80px;}h1{color:#2b7a2b;}a.btn{display:inline-block;padding:10px 18px;background:#2b7a2b;color:#fff;border-radius:6px;text-decoration:none;font-weight:600;margin-top:18px;}</style></head><body><h1>Email vérifié !</h1><p>Tu peux maintenant te connecter.</p><a class="btn" href="https://isl.yanasnytko.com/login">Aller à la connexion</a><p style="color:#888;margin-top:24px;font-size:14px">Redirection automatique…</p></body></html>';
    return new Response($html, 200);
    }
}
