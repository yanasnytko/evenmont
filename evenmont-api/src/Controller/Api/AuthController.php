<?php

namespace App\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    public function __construct(private UserRepository $users) {}

    #[Route('/api/login', name: 'api_login_info', methods: ['GET', 'POST'])]
    public function loginInfo(): JsonResponse
    {
        return $this->json(['message' => 'POST form-data (email,password) here.']);
    }

    #[Route('/api/login_check', name: 'api_login_check', methods: ['POST'])]
    public function loginCheck(): never
    {
        throw new \LogicException('This should be intercepted by the security firewall.');
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $u = $this->getUser();
        if (!$u) {
            return $this->json(['user' => null]);
        }

        // Récupère un email quel que soit l'objet renvoyé par Security
        $email = method_exists($u, 'getUserIdentifier')
            ? $u->getUserIdentifier()
            : null;

        // Recharge l'entité User depuis la DB pour être sûr d'avoir nos méthodes
        $entity = null;
        if ($u instanceof \App\Entity\User) {
            $entity = $u;
        } elseif ($email) {
            $entity = $this->users->findOneBy(['email' => $email]);
        }

        // S'il n'y a pas d'entité (cas extrême), on renvoie au moins email/roles
        $entity = $u instanceof \App\Entity\User ? $u : ($email ? $this->users->findOneBy(['email' => $email]) : null);
        $roles = $entity?->getRoles() ?? (method_exists($u, 'getRoles') ? $u->getRoles() : []);

        return $this->json([
            'id'        => $entity?->getId(),
            'email'     => $email,
            'roles'     => $roles,
            'firstName' => $entity?->getFirstName(),
            'lastName'  => $entity?->getLastName(),
        ]);
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        // Réponse OK
        $resp = $this->json(['ok' => true]);

        // Supprime le cookie côté client
        // (Signature clearCookie(name, path = '/', domain = null, secure = true, httpOnly = true, sameSite = 'lax'))
        $resp->headers->clearCookie('refresh_token', '/', null, true, true, 'lax');

        return $resp;
    }
}
