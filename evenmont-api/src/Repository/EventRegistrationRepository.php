<?php

namespace App\Repository;

use App\Entity\EventRegistration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Event;

/**
 * @extends ServiceEntityRepository<EventRegistration>
 */
class EventRegistrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventRegistration::class);
    }

    // src/Repository/EventRegistrationRepository.php
    public function countByEventAndStatus(Event $event, array $statuses): int
    {
        return (int)$this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.event = :e')->setParameter('e', $event)
            ->andWhere('r.status IN (:s)')->setParameter('s', $statuses)
            ->getQuery()->getSingleScalarResult();
    }
}
