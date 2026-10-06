<?php

namespace App\Repository;

use App\Entity\JobApplication;
use App\Enum\JobApplicationStatus;
use App\Enum\JobApplicationType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobApplication>
 */
class JobApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobApplication::class);
    }

    /** @return JobApplication[] candidatures sans réponse dont la relance est due */
    public function findFollowUpsDue(): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.company', 'c')->addSelect('c')
            ->leftJoin('a.offer', 'o')->addSelect('o')
            ->where('a.status IN (:statuses)')
            ->andWhere('a.followUpAt <= :now')
            ->setParameter('statuses', [JobApplicationStatus::Sent, JobApplicationStatus::FollowedUp])
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('a.followUpAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return JobApplication[] candidatures envoyées sans réponse, la prochaine relance en premier */
    public function findAwaitingResponse(): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.company', 'c')->addSelect('c')
            ->leftJoin('a.offer', 'o')->addSelect('o')
            ->where('a.status IN (:statuses)')
            ->setParameter('statuses', [JobApplicationStatus::Sent, JobApplicationStatus::FollowedUp])
            ->orderBy('a.followUpAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return JobApplication[] */
    public function findByStatus(JobApplicationStatus $status): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.company', 'c')->addSelect('c')
            ->leftJoin('a.offer', 'o')->addSelect('o')
            ->where('a.status = :status')
            ->setParameter('status', $status)
            ->orderBy('a.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array{sent: int, jobPosting: int, spontaneous: int, answered: int, interviews: int, awaiting: int, last7Days: int}
     */
    public function getStats(): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select('a.type AS type, a.status AS status, COUNT(a.id) AS total')
            ->where('a.status != :draft')
            ->setParameter('draft', JobApplicationStatus::Draft)
            ->groupBy('a.type, a.status')
            ->getQuery()
            ->getResult();

        $stats = ['sent' => 0, 'jobPosting' => 0, 'spontaneous' => 0, 'answered' => 0, 'interviews' => 0, 'awaiting' => 0];
        foreach ($rows as $row) {
            $total = (int) $row['total'];
            $status = $row['status'] instanceof JobApplicationStatus ? $row['status'] : JobApplicationStatus::from($row['status']);
            $type = $row['type'] instanceof JobApplicationType ? $row['type'] : JobApplicationType::from($row['type']);

            $stats['sent'] += $total;
            $stats[$type === JobApplicationType::JobPosting ? 'jobPosting' : 'spontaneous'] += $total;
            $stats['answered'] += $status->isAnswered() ? $total : 0;
            $stats['interviews'] += $status === JobApplicationStatus::Interview ? $total : 0;
            $stats['awaiting'] += $status->isAwaitingResponse() ? $total : 0;
        }

        $stats['last7Days'] = (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.sentAt >= :since')
            ->setParameter('since', new \DateTimeImmutable('-7 days'))
            ->getQuery()
            ->getSingleScalarResult();

        return $stats;
    }
}
