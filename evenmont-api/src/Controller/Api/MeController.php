<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
final class MeController extends AbstractController
{
    public function __construct(
        private UserRepository $users,
        private EntityManagerInterface $em,
    ) {}

    /** GET /api/me — profil connecté */
    #[Route('/me', name: 'api_me_get', methods: ['GET'])]
    public function getMe(): JsonResponse
    {
        /** @var User|null $u */
        $u = $this->getUser();
        if (!$u instanceof User) {
            return $this->json(['user' => null], 200);
        }

        return $this->json([
            'id'             => $u->getId(),
            'email'          => $u->getEmail(),
            'roles'          => $u->getRoles(),
            'firstName'      => $u->getFirstName(),
            'lastName'       => $u->getLastName(),
            'avatarUrl'      => $u->getAvatarUrl(),
            'emailVerified'  => (bool) $u->getEmailVerifiedAt(),
        ]);
    }

    /** PUT /api/me — met à jour le profil */
    #[Route('/me', name: 'api_me_put', methods: ['PUT'])]
    public function updateMe(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User|null $u */
        $u = $this->getUser();
        if (!$u instanceof User) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent() ?: '{}', true);

        // Champs autorisés à la mise à jour
        $fields = [
            'firstName' => 'setFirstName',
            'lastName'  => 'setLastName',
            'city'      => 'setCity',
            'bio'       => 'setBio',
            'avatarUrl' => 'setAvatarUrl',
        ];

        foreach ($fields as $key => $setter) {
            if (array_key_exists($key, $data)) {
                $value = $data[$key];
                // On autorise null ou chaîne vide → null
                if (is_string($value) && trim($value) === '') {
                    $value = null;
                }
                if (method_exists($u, $setter)) {
                    $u->$setter($value);
                }
            }
        }

        $this->em->persist($u);
        $this->em->flush();

        return $this->json([
            'ok' => true,
            'user' => [
                'id'        => $u->getId(),
                'email'     => $u->getEmail(),
                'firstName' => $u->getFirstName(),
                'lastName'  => $u->getLastName(),
                'avatarUrl' => $u->getAvatarUrl(),
            ],
        ]);
    }
}
