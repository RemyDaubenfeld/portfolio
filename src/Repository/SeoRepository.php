<?php

namespace App\Repository;

use App\Entity\Seo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SeoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Seo::class);
    }

    public function findByPageKey(?string $pageKey): ?Seo
    {
        return $this->findOneBy(['pageKey' => $pageKey]);
    }
}