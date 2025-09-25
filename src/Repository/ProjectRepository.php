<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Project::class); }

    /** Retourne tous les projets avec tâches et acteurs (pour éviter le N+1) */
    public function findAllWithTasks(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.tasks', 't')->addSelect('t')
            ->leftJoin('t.actors', 'a')->addSelect('a')
            ->orderBy('p.id', 'ASC')
            ->getQuery()->getResult();
    }

    /** Partitionne projets: ceux où $user est acteur d'au moins une tâche, puis les autres */
   public function splitMineFirst(array $projects, ?User $user): array
    {
        if (!$user) return [$projects, []];

        $mine = [];
        $others = [];

        foreach ($projects as $p) {
            $hasMe = false;
            foreach ($p->getTasks() as $t) {
                if ($t->getActors()->contains($user)) { $hasMe = true; break; }
            }

            if ($hasMe) {
                $mine[] = $p;
            } else {
                $others[] = $p;
            }
        }

        return [$mine, $others];
    }

}
