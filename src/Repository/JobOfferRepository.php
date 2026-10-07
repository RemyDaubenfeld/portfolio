<?php
namespace App\Repository;

use App\Entity\JobApplication;
use App\Entity\JobOffer;
use App\Enum\JobOfferStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class JobOfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobOffer::class);
    }

    public function countByApplicationStatus(JobOfferStatus $status): int
    {
        return $this->count(['applicationStatus' => $status]);
    }

    public function countCreatedSince(\DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.createdAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return JobOffer[] offres encore à étudier arrivées depuis la date donnée */
    public function findToReviewCreatedSince(\DateTimeImmutable $since): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.applicationStatus = :status')
            ->andWhere('o.createdAt >= :since')
            ->setParameter('status', JobOfferStatus::ToReview)
            ->setParameter('since', $since)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return JobOffer[] dernières offres à étudier */
    public function findLatestToReview(int $limit): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.applicationStatus = :status')
            ->setParameter('status', JobOfferStatus::ToReview)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param JobOfferStatus[] $statuses
     * @return JobOffer[] offres ayant l'un des statuts donnés mais aucune candidature associée
     */
    public function findWithoutApplication(array $statuses): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin(JobApplication::class, 'a', 'WITH', 'a.offer = o')
            ->where('a.id IS NULL')
            ->andWhere('o.applicationStatus IN (:statuses)')
            ->setParameter('statuses', $statuses)
            ->orderBy('o.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return JobOffer[] offres avec un nom d'entreprise mais sans fiche entreprise liée */
    public function findWithoutLinkedCompany(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.linkedCompany IS NULL')
            ->andWhere("o.company IS NOT NULL AND o.company != ''")
            ->getQuery()
            ->getResult();
    }
}
