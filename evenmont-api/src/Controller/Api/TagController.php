<?php
// src/Controller/Api/TagController.php
namespace App\Controller\Api;

use App\Entity\Tag;
use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
final class TagController extends AbstractController
{
    #[Route('/tags', name: 'api_tags', methods: ['GET'])]
    public function list(Request $req, EntityManagerInterface $em): JsonResponse
    {
        $withCounts = filter_var($req->query->get('withCounts', '0'), FILTER_VALIDATE_BOOL);

        $qb = $em->getRepository(Tag::class)->createQueryBuilder('t')
            ->orderBy('t.name', 'ASC');

        if ($withCounts) {
            // LEFT JOIN vers EventTag -> Event pour compter sans N+1
            $qb->leftJoin('t.eventTags', 'et')
                ->leftJoin('et.event', 'e')
                ->addSelect('COUNT(DISTINCT e.id) AS eventsCount')
                ->groupBy('t.id');
        }

        $rows = $qb->getQuery()->getResult();

        // Si withCounts=true, $rows = [ [0 => Tag, 'eventsCount' => string|int], ... ]
        // Sinon, $rows = [ Tag, Tag, ... ]
        $items = [];
        foreach ($rows as $row) {
            /** @var Tag $t */
            $t = is_array($row) ? $row[0] : $row;
            $count = is_array($row) ? (int)$row['eventsCount'] : null;

            $items[] = [
                'id'    => $t->getId(),
                'slug'  => $t->getSlug(),
                'name'  => $t->getName(),
                // n’inclus la métrique que si demandée
                ...($withCounts ? ['eventsCount' => $count] : []),
            ];
        }

        return $this->json(['items' => $items]);
    }
}
