<?php

namespace App\Repository;

use App\Entity\Interest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InterestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Interest::class);
    }

    public function findForPortfolio(): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.showOnPortfolio = true')
            ->orderBy('i.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findForCv(): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.showOnCv = true')
            ->orderBy('i.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}