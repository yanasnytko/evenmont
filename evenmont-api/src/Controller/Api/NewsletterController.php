<?php
namespace App\Controller\Api;

use App\Entity\Newsletter;
use App\Repository\NewsletterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/newsletters', name: 'api_newsletters_')]
class NewsletterController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(NewsletterRepository $newsletterRepository): JsonResponse
    {
        $newsletters = $newsletterRepository->findAll();
        return $this->json($newsletters);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(Newsletter $newsletter): JsonResponse
    {
        return $this->json($newsletter);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // ...parse and validate request, create Newsletter...
        return $this->json(['message' => 'Newsletter created'], 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(Newsletter $newsletter, Request $request, EntityManagerInterface $em): JsonResponse
    {
        // ...update newsletter...
        return $this->json(['message' => 'Newsletter updated']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(Newsletter $newsletter, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($newsletter);
        $em->flush();
        return $this->json(['message' => 'Newsletter deleted']);
    }
}
