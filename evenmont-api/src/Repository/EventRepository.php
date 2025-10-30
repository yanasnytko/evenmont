<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * @return array{items: list<Event>, total: int, page: int, pages: int}
     */
    public function search(
        ?string $q,
        string $sort,
        int $page,
        int $limit,
        ?string $from,
        ?string $to,
        ?string $categorySlug
    ): array {
        $qb = $this->createQueryBuilder('e');

        // Texte / ville
        if ($q) {
            $qb->andWhere('LOWER(e.title) LIKE :q OR LOWER(e.city) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        // Dates
        if ($from) {
            $qb->andWhere('e.startAt >= :from')
                ->setParameter('from', new \DateTimeImmutable($from . ' 00:00:00'));
        }
        if ($to) {
            $qb->andWhere('e.startAt <= :to')
                ->setParameter('to', new \DateTimeImmutable($to . ' 23:59:59'));
        }

        // Catégorie via EXISTS (évite tout JOIN dans la requête principale)
        if ($categorySlug) {
            $qb->andWhere(
                $qb->expr()->exists(
                    'SELECT 1 
                     FROM App\Entity\EventTag et2 
                     JOIN et2.tag t2 
                     WHERE et2.event = e AND t2.slug = :cat'
                )
            )
                ->setParameter('cat', $categorySlug);
        }

        // Tri
        $now = new \DateTimeImmutable();
        switch ($sort) {
            case 'date_desc':
                // Futur d'abord, puis passé, à l'intérieur tri décroissant
                $qb->addSelect('(CASE WHEN e.startAt >= :now THEN 0 ELSE 1 END) AS HIDDEN pastFlag')
                    ->setParameter('now', $now)
                    ->addOrderBy('pastFlag', 'ASC')
                    ->addOrderBy('e.startAt', 'DESC');
                break;

            case 'title_asc':
                $qb->addOrderBy('e.title', 'ASC');
                break;

            case 'title_desc':
                $qb->addOrderBy('e.title', 'DESC');
                break;

            default: // date_asc
                // Futur d'abord, puis passé, à l'intérieur tri croissant
                $qb->addSelect('(CASE WHEN e.startAt >= :now THEN 0 ELSE 1 END) AS HIDDEN pastFlag')
                    ->setParameter('now', $now)
                    ->addOrderBy('pastFlag', 'ASC')
                    ->addOrderBy('e.startAt', 'ASC');
        }

        // Pagination
        $page   = max(1, $page);
        $limit  = min(50, max(1, $limit));
        $first  = ($page - 1) * $limit;

        $qb->setFirstResult($first)->setMaxResults($limit);

        // Paginator sans DISTINCT ni fetch-join
        $paginator = new Paginator($qb->getQuery(), false);
        $items     = iterator_to_array($paginator, false);
        $total     = count($paginator);
        $pages     = max(1, (int)ceil($total / $limit));

        return [
            'items' => $items,
            'total' => $total,
            'page'  => $page,
            'pages' => $pages,
        ];
    }
}
