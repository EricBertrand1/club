<?php

namespace App\Repository;

use App\Entity\CastellumSubcategory;
use App\Entity\FormationPage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FormationPageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormationPage::class);
    }

    public function findOrCreateForSub(\Doctrine\ORM\EntityManagerInterface $em, CastellumSubcategory $sub): FormationPage
    {
        $page = $this->findOneBy(['subcategory' => $sub]);
        if (!$page) {
            $page = (new FormationPage())->setSubcategory($sub);
            $em->persist($page);
            $em->flush();
        }
        return $page;
    }
}
