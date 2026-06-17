<?php

namespace App\Repository;

use App\Entity\HeroTyping;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HeroTyping>
 *
 * @method HeroTyping|null find($id, $lockMode = null, $lockVersion = null)
 * @method HeroTyping|null findOneBy(array $criteria, array $orderBy = null)
 * @method HeroTyping[]    findAll()
 * @method HeroTyping[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HeroTypingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HeroTyping::class);
    }

//    /**
//     * @return HeroTyping[] Returns an array of HeroTyping objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?HeroTyping
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
