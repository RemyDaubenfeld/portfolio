<?php

namespace App\Repository;

use App\Entity\Language;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LanguageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Language::class);
    }

    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
