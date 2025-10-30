<?php
namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Filesystem\Filesystem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/api')]
class ProfileController extends AbstractController
{
    #[Route('/me/avatar', name: 'api_me_avatar', methods: ['POST'])]
    public function uploadAvatar(Request $req, EntityManagerInterface $em, string $upload_dir): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        /** @var UploadedFile|null $file */
        $file = $req->files->get('file');
        if (!$file) return $this->json(['error' => 'file is required (multipart/form-data)'], 400);

        // validations
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array((string)$file->getMimeType(), $allowed, true)) {
            return $this->json(['error' => 'Only JPEG/PNG/WEBP allowed'], 415);
        }
        if ($file->getSize() !== null && $file->getSize() > 5 * 1024 * 1024) {
            return $this->json(['error' => 'Max 5MB'], 413);
        }

        // dossier utilisateur
        $fs = new Filesystem();
        $userDir = rtrim($upload_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $user->getId() . DIRECTORY_SEPARATOR . 'avatar';
        $fs->mkdir($userDir, 0775);

        // nom safe
        $ext = $file->guessExtension() ?: 'jpg';
        $name = 'avatar_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $file->move($userDir, $name);

        $relative = '/uploads/' . $user->getId() . '/avatar/' . $name;

        // MAJ profil
        $user->setAvatarUrl($relative);
        $em->persist($user);
        $em->flush();

        return $this->json([
            'url' => $relative,
            'absoluteUrl' => $req->getSchemeAndHttpHost() . $relative,
        ], 201);
    }
}
