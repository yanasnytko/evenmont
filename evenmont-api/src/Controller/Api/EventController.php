<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Entity\User;
use App\Repository\EventRepository;
use App\Security\Voter\EventVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class EventController extends AbstractController
{
    /** Rend un chemin relatif (/uploads/...) en URL absolue http(s)://host/... */
    private function abs(string $host, ?string $path): ?string
    {
        if (!$path) return null;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) return $path;
        return rtrim($host, '/') . $path;
    }

    /** Normalise un Event pour l’API (utilisé par list() & show()) */
    private function mapEvent(Event $e, string $host): array
    {
        $imgAbs = $this->abs($host, $e->getCoverUrl());

        return [
            'id'          => $e->getId(),
            'title'       => (string) $e->getTitle(),
            'city'        => (string) ($e->getCity() ?? ''),
            'description' => (string) ($e->getDescription() ?? ''),
            'startAt'     => $e->getStartAt()?->format(DATE_ATOM),
            'endAt'       => $e->getEndAt()?->format(DATE_ATOM),
            'image'       => $imgAbs ?? '/img/demo.jpg', // image absolue pour le front
            'coverUrl'    => $e->getCoverUrl(),          // valeur brute (relative ou absolue d’origine)
            'organizerId' => $e->getOrganizer()?->getId(),

            // ===== Ajouts nécessaires pour le front/paiement =====
            'capacity'            => $e->getCapacity(),
            'registrationsCount'  => $e->getEventRegistrations()->count(),
            'price'               => $e->getPrice() !== null ? (float)$e->getPrice() : 0.0,

            // Catégories (inchangé)
            'categories'  => array_map(
                static fn($et) => [
                    'slug' => $et->getTag()->getSlug(),
                    'name' => $et->getTag()->getName(),
                ],
                $e->getEventTags()->toArray()
            ),
        ];
    }

    #[Route('/api/events', name: 'api_events_list', methods: ['GET'])]
    public function list(Request $req, EventRepository $repo): JsonResponse
    {
        $q        = $req->query->get('q');
        $sort     = $req->query->get('sort', 'date_asc'); // date_asc|date_desc|title_asc|title_desc
        $page     = max(1, (int) $req->query->get('page', 1));
        $limit    = min(50, max(1, (int) $req->query->get('limit', 12)));
        $from     = $req->query->get('from');
        $to       = $req->query->get('to');
        $category = $req->query->get('category'); // slug (optionnel)

        $res   = $repo->search($q, $sort, $page, $limit, $from, $to, $category);
        $items = $res['items'] ?? [];
        $total = (int) ($res['total'] ?? count($items));
        $pages = (int) ($res['pages'] ?? max(1, (int) ceil($total / $limit)));

        $host = $req->getSchemeAndHttpHost();
        $outItems = array_map(fn(Event $e) => $this->mapEvent($e, $host), $items);

        return $this->json([
            'items' => $outItems,
            'total' => $total,
            'page'  => $page,
            'pages' => $pages,
        ]);
    }

    #[Route('/api/events/{id}', name: 'api_events_show', methods: ['GET'])]
    public function show(int $id, Request $req, EntityManagerInterface $em): JsonResponse
    {
        $e = $em->getRepository(Event::class)->find($id);
        if (!$e) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $host = $req->getSchemeAndHttpHost();
        return $this->json($this->mapEvent($e, $host));
    }

    #[Route('/api/events', name: 'api_events_create', methods: ['POST'])]
    public function create(Request $req, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ORGANIZER');

        $isJson = str_contains($req->headers->get('content-type', ''), 'application/json');
        $p = $isJson ? (json_decode($req->getContent(), true) ?? []) : $req->request->all();

        $title = trim((string) ($p['title'] ?? ''));
        if ($title === '') {
            return $this->json(['error' => 'Le titre est obligatoire'], 422);
        }

        $start = !empty($p['startAt']) ? new \DateTimeImmutable($p['startAt']) : null;
        $end   = !empty($p['endAt'])   ? new \DateTimeImmutable($p['endAt'])   : null;
        if ($start && $end && $end < $start) {
            return $this->json(['error' => 'endAt doit être après startAt'], 422);
        }

        // capacity
        $capacity = null;
        if (array_key_exists('capacity', $p) && $p['capacity'] !== null && $p['capacity'] !== '') {
            $capacity = max(0, (int) $p['capacity']);
        }

        // price (decimal string pour Doctrine)
        $price = null;
        if (array_key_exists('price', $p) && $p['price'] !== null && $p['price'] !== '') {
            $price = number_format((float) $p['price'], 2, '.', ''); // "12.00"
        }

        $e = new Event();
        $e->setTitle($title);
        $e->setCity($p['city'] ?? null);
        $e->setDescription($p['description'] ?? null);
        if ($start) $e->setStartAt($start);
        if ($end)   $e->setEndAt($end);
        $e->setCapacity($capacity);
        $e->setPrice($price); // 👈 nouveau

        // coverUrl : accepte absolu (retiré d’origine) & relatif
        $cover = $p['coverUrl'] ?? null;
        if ($cover) {
            $parsed = parse_url($cover);
            if (!empty($parsed['scheme']) && !empty($parsed['host']) && !empty($parsed['path'])) {
                $cover = $parsed['path']; // /uploads/...
            }
        }
        $e->setCoverUrl($cover);

        // Organizer = user courant
        $u = $this->getUser();
        if (!$u instanceof User) {
            return $this->json(['error' => 'Auth required'], 401);
        }
        $e->setOrganizer($u);

        $em->persist($e);
        $em->flush();

        $host = $req->getSchemeAndHttpHost();
        return $this->json([
            'id'    => $e->getId(),
            'image' => $this->abs($host, $e->getCoverUrl()),
        ], 201);
    }

    #[Route('/api/events/{id}', name: 'api_events_update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $req, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ORGANIZER');

        $e = $em->find(Event::class, $id);
        if (!$e) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $this->denyAccessUnlessGranted(EventVoter::EDIT, $e);

        $p = json_decode($req->getContent(), true) ?? [];

        if (array_key_exists('title', $p))        $e->setTitle((string) $p['title']);
        if (array_key_exists('city', $p))         $e->setCity($p['city'] ?? null);
        if (array_key_exists('description', $p))  $e->setDescription($p['description'] ?? null);
        if (!empty($p['startAt']))                $e->setStartAt(new \DateTimeImmutable($p['startAt']));
        if (!empty($p['endAt']))                  $e->setEndAt(new \DateTimeImmutable($p['endAt']));

        if (array_key_exists('capacity', $p)) {
            $cap = ($p['capacity'] === '' || $p['capacity'] === null) ? null : max(0, (int)$p['capacity']);
            $e->setCapacity($cap);
        }

        if (array_key_exists('price', $p)) {
            $price = ($p['price'] === '' || $p['price'] === null)
                ? null
                : number_format((float)$p['price'], 2, '.', '');
            $e->setPrice($price);
        }

        if (array_key_exists('coverUrl', $p)) {
            $cover = $p['coverUrl'] ?? null;
            if ($cover) {
                $parsed = parse_url($cover);
                if (!empty($parsed['scheme']) && !empty($parsed['host']) && !empty($parsed['path'])) {
                    $cover = $parsed['path']; // /uploads/...
                }
            }
            $e->setCoverUrl($cover);
        }

        $em->flush();

        $host = $req->getSchemeAndHttpHost();
        return $this->json([
            'ok'    => true,
            'image' => $this->abs($host, $e->getCoverUrl()),
        ]);
    }
}
