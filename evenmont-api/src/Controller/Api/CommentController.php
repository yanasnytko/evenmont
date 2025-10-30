<?php
namespace App\Controller\Api;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/comments', name: 'api_comments_')]
class CommentController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(CommentRepository $commentRepository): JsonResponse
    {
        $comments = $commentRepository->findAll();
        return $this->json($comments);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(Comment $comment): JsonResponse
    {
        return $this->json($comment);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        return $this->json(['message' => 'Comment created'], 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(Comment $comment, Request $request, EntityManagerInterface $em): JsonResponse
    {
        return $this->json(['message' => 'Comment updated']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(Comment $comment, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($comment);
        $em->flush();
        return $this->json(['message' => 'Comment deleted']);
    }
}
