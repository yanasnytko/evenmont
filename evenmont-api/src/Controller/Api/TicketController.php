<?php
namespace App\Controller\Api;

use App\Entity\Ticket;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/tickets', name: 'api_tickets_')]
class TicketController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(TicketRepository $ticketRepository): JsonResponse
    {
        $tickets = $ticketRepository->findAll();
        return $this->json($tickets);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(Ticket $ticket): JsonResponse
    {
        return $this->json($ticket);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // ...parse and validate request, create Ticket...
        return $this->json(['message' => 'Ticket created'], 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(Ticket $ticket, Request $request, EntityManagerInterface $em): JsonResponse
    {
        // ...update ticket...
        return $this->json(['message' => 'Ticket updated']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(Ticket $ticket, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($ticket);
        $em->flush();
        return $this->json(['message' => 'Ticket deleted']);
    }
}
