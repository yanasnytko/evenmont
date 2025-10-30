<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/api')]
class OrganizerController
{
    private function abs(string $host, ?string $path): ?string
    {
        if (!$path) return null;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) return $path;
        return rtrim($host, '/') . $path;
    }

    #[Route('/organizers', name: 'api_organizers', methods: ['GET'])]
    public function list(Request $req, EntityManagerInterface $em): JsonResponse
    {
        $q     = $req->query->get('q');
        $page  = max(1, (int)$req->query->get('page', 1));
        $limit = min(50, max(1, (int)$req->query->get('limit', 12)));
        $off   = ($page - 1) * $limit;

        $qb = $em->getRepository(User::class)->createQueryBuilder('u')
            // Users dont les rôles JSON contiennent "ROLE_ORGANIZER"
            ->andWhere('u.roles LIKE :roleJson')
            ->setParameter('roleJson', '%"ROLE_ORGANIZER"%');

        if ($q) {
            $qb->andWhere('LOWER(u.email) LIKE :q OR LOWER(u.firstName) LIKE :q OR LOWER(u.lastName) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        $total = (int) (clone $qb)->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();

        $host  = $req->getSchemeAndHttpHost();

        $items = $qb
            ->select('u', '(SELECT COUNT(e2.id) FROM App\Entity\Event e2 WHERE e2.organizer = u) AS eventsCount')
            ->orderBy('u.createdAt', 'DESC')
            ->setFirstResult($off)->setMaxResults($limit)
            ->getQuery()->getResult();

        $out = [];
        foreach ($items as $row) {
            /** @var User $u */
            $u     = $row[0];
            $count = (int) $row['eventsCount'];

            $name   = trim(($u->getFirstName() ?? '') . ' ' . ($u->getLastName() ?? '')) ?: $u->getEmail();
            $avatar = method_exists($u, 'getAvatarUrl') ? $u->getAvatarUrl() : null;
            if ($avatar && str_starts_with($avatar, '/')) {
                $avatar = $host . $avatar; // absolu
            }

            $out[] = [
                'id'          => $u->getId(),
                'name'        => $name,
                'email'       => $u->getEmail(),
                'avatarUrl'   => $avatar,
                'banner'      => null,     // ajoute si tu crées le champ
                'eventsCount' => $count,
            ];
        }

        return new JsonResponse([
            'items' => $out,
            'total' => $total,
            'page'  => $page,
            'pages' => max(1, (int) ceil($total / $limit)),
        ]);
    }

    #[Route('/organizers/{id}', name: 'api_organizers_show', methods: ['GET'])]
    public function show(int $id, Request $req, EntityManagerInterface $em): JsonResponse
    {
        $u = $em->getRepository(User::class)->find($id);
        if (!$u) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        // sécurité : doit être organisateur
        if (!in_array('ROLE_ORGANIZER', $u->getRoles(), true)) {
            return new JsonResponse(['error' => 'Not an organizer'], 404);
        }

        $host       = $req->getSchemeAndHttpHost();
        $eventsCount = (int) $em->createQuery(
            'SELECT COUNT(e.id) FROM App\Entity\Event e WHERE e.organizer = :o'
        )->setParameter('o', $u)->getSingleScalarResult();

        $name   = trim(($u->getFirstName() ?? '') . ' ' . ($u->getLastName() ?? '')) ?: $u->getEmail();
        $avatar = method_exists($u, 'getAvatarUrl') ? $u->getAvatarUrl() : null;
        if ($avatar && str_starts_with($avatar, '/')) {
            $avatar = $host . $avatar; // absolu
        }

        // banner: si tu ajoutes un champ "bannerUrl" sur User, rends-la absolue comme ci-dessus
        $banner = null;

        // city/bio si présents dans l'entité
        $city = method_exists($u, 'getCity') ? $u->getCity() : null;
        $bio  = method_exists($u, 'getBio')  ? $u->getBio()  : null;

        return new JsonResponse([
            'id'          => $u->getId(),
            'name'        => $name,
            'email'       => $u->getEmail(),
            'city'        => $city,
            'bio'         => $bio,
            'avatarUrl'   => $avatar,
            'banner'      => $banner,
            'eventsCount' => $eventsCount,
        ]);
    }

    #[Route('/organizers/{id}/events', name: 'api_organizers_events', methods: ['GET'])]
    public function events(int $id, Request $req, EntityManagerInterface $em): JsonResponse
    {
        $page  = max(1, (int)$req->query->get('page', 1));
        $limit = min(50, max(1, (int)$req->query->get('limit', 12)));
        $off   = ($page - 1) * $limit;

        $org = $em->getRepository(User::class)->find($id);
        if (!$org || !in_array('ROLE_ORGANIZER', $org->getRoles(), true)) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        $repo = $em->getRepository(Event::class);
        $qb = $repo->createQueryBuilder('e')
            ->andWhere('e.organizer = :o')->setParameter('o', $org);

        $total = (int) (clone $qb)->select('COUNT(e.id)')->getQuery()->getSingleScalarResult();

        $rows = $qb->orderBy('e.startAt', 'ASC')
            ->setFirstResult($off)->setMaxResults($limit)
            ->getQuery()->getResult();

        $host = $req->getSchemeAndHttpHost();
        $items = array_map(function (Event $e) use ($org, $host) {
            return [
                'id'          => $e->getId(),
                'title'       => $e->getTitle(),
                'city'        => $e->getCity(),
                'description' => $e->getDescription(),
                'startAt'     => $e->getStartAt()?->format(DATE_ATOM),
                'endAt'       => $e->getEndAt()?->format(DATE_ATOM),
                'image'       => $this->abs($host, $e->getCoverUrl()) ?? '/img/demo.jpg',
                'organizerId' => $org->getId(),
            ];
        }, $rows);

        return new JsonResponse([
            'items' => $items,
            'total' => $total,
            'page'  => $page,
            'pages' => max(1, (int) ceil($total / $limit)),
        ]);
    }
}
