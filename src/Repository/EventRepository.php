<?php

// src/Repository/EventRepository.php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function findForWeek(\DateTime $date): array
    {
        // Calculer le début et la fin de la semaine
        $startOfWeek = clone $date;
        $startOfWeek->modify('Monday this week');
        $endOfWeek = clone $startOfWeek;
        $endOfWeek->modify('+6 days')->setTime(23, 59, 59);

        // Récupérer les événements de cette semaine
        return $this->createQueryBuilder('e')
            ->andWhere('e.start_time BETWEEN :start AND :end')
            ->setParameter('start', $startOfWeek)
            ->setParameter('end', $endOfWeek)
            ->orderBy('e.start_time', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
