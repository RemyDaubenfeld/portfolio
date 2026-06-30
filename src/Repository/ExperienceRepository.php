<?php

namespace App\Repository;

use App\Entity\Experience;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ExperienceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Experience::class);
    }

    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
