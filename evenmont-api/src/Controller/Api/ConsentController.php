<?php
namespace App\Controller\Api;

use App\Entity\Consent;
use App\Repository\ConsentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/consents', name: 'api_consents_')]
class ConsentController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(ConsentRepository $consentRepository): JsonResponse
    {
        $consents = $consentRepository->findAll();
        return $this->json($consents);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(Consent $consent): JsonResponse
    {
        return $this->json($consent);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        return $this->json(['message' => 'Consent created'], 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(Consent $consent, Request $request, EntityManagerInterface $em): JsonResponse
    {
        return $this->json(['message' => 'Consent updated']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(Consent $consent, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($consent);
        $em->flush();
        return $this->json(['message' => 'Consent deleted']);
    }
}
