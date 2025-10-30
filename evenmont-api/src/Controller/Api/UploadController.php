<?php

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;

final class UploadController extends AbstractController
{
    #[Route('/api/upload', name: 'api_upload', methods: ['POST', 'OPTIONS'])]
    public function upload(Request $request, string $upload_dir): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'file_required', 'hint' => 'multipart/form-data with "file"'], 400);
        }

         if ($file->getError() !== \UPLOAD_ERR_OK) {
        $map = [
            \UPLOAD_ERR_INI_SIZE   => 'upload_max_filesize exceeded',
            \UPLOAD_ERR_FORM_SIZE  => 'MAX_FILE_SIZE exceeded',
            \UPLOAD_ERR_PARTIAL    => 'partial upload',
            \UPLOAD_ERR_NO_FILE    => 'no file',
            \UPLOAD_ERR_NO_TMP_DIR => 'missing tmp dir',
            \UPLOAD_ERR_CANT_WRITE => 'cannot write',
            \UPLOAD_ERR_EXTENSION  => 'blocked by extension',
        ];
        $msg = $map[$file->getError()] ?? 'upload error';
        return $this->json(['error' => 'upload_error', 'message' => $msg], 400);
    }

        // 1) Whitelist stricte (pas de SVG/GIF)
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $mime = (string) $file->getMimeType();
        if (!in_array($mime, $allowed, true)) {
            return $this->json(['error' => 'mime_not_allowed', 'allowed' => $allowed], 415);
        }

        // 2) Max 5 MB
        $size = null;
        try {
            $size = $file->getSize(); // normalement issu de $_FILES['size'], sinon stat()
        } catch (\Throwable $e) {
            // fallback : on tente le Content-Length global si présent
            $size = (int) ($request->headers->get('content-length') ?? 0);
        }
        // applique la règle 5 Mo si on a une taille valable
        if ($size > 0 && $size > 5 * 1024 * 1024) {
            return $this->json(['error' => 'Max 5MB'], 413);
        }

        // 3) Map MIME -> extension (anti-polyglot)
        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];
        $ext = $extMap[$mime] ?? 'bin';

        // 4) Dossier par utilisateur
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'auth_required'], 401);
        }
        $userId = $user->getId();

        $fs = new Filesystem();
        $targetDir = rtrim($upload_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $userId;

        try {
            if (!is_dir($targetDir)) {
                $fs->mkdir($targetDir, 0775);
            }

            // 5) Nom aléatoire + move
            $name = bin2hex(random_bytes(8)) . '.' . $ext;
            $file->move($targetDir, $name);

            $relative = "/uploads/$userId/$name";
            return $this->json([
                'url'         => $relative,
                'absoluteUrl' => $request->getSchemeAndHttpHost() . $relative,
                'mime'        => $mime,
                'size'        => $size,
            ], 201);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'upload_failed', 'message' => $e->getMessage()], 500);
        }
    }
}
