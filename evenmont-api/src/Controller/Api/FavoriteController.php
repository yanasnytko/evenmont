<?php
namespace App\Controller\Api;

use App\Entity\Favorite;
use App\Repository\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/favorites', name: 'api_favorites_')]
class FavoriteController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(FavoriteRepository $favoriteRepository): JsonResponse
    {
        $favorites = $favoriteRepository->findAll();
        return $this->json($favorites);
    }

    #[Route('/{userId}/{eventId}', methods: ['GET'])]
    public function show(FavoriteRepository $favoriteRepository, int $userId, int $eventId): JsonResponse
    {
        $favorite = $favoriteRepository->findOneBy(['user' => $userId, 'event' => $eventId]);
        return $this->json($favorite);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // ...parse and validate request, create Favorite...
        return $this->json(['message' => 'Favorite created'], 201);
    }

    #[Route('/{userId}/{eventId}', methods: ['DELETE'])]
    public function delete(FavoriteRepository $favoriteRepository, EntityManagerInterface $em, int $userId, int $eventId): JsonResponse
    {
        $favorite = $favoriteRepository->findOneBy(['user' => $userId, 'event' => $eventId]);
        if ($favorite) {
            $em->remove($favorite);
            $em->flush();
            return $this->json(['message' => 'Favorite deleted']);
        }
        return $this->json(['message' => 'Favorite not found'], 404);
    }
}
