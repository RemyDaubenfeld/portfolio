<?php

namespace App\Repository;

use App\Entity\Company;
use App\Enum\CompanyStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Company>
 */
class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    /** @return Company[] */
    public function findByNormalizedName(string $normalizedName): array
    {
        return $this->findBy(['normalizedName' => $normalizedName], ['id' => 'ASC']);
    }

    /** @return array<string, int> nombre d'entreprises indexé par valeur de statut */
    public function countByStatus(): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('c.status AS status, COUNT(c.id) AS total')
            ->groupBy('c.status')
            ->getQuery()
            ->getArrayResult();

        $counts = array_fill_keys(array_map(fn (CompanyStatus $s) => $s->value, CompanyStatus::cases()), 0);
        foreach ($rows as $row) {
            $status = $row['status'] instanceof CompanyStatus ? $row['status']->value : $row['status'];
            $counts[$status] = (int) $row['total'];
        }

        return $counts;
    }

    /** @return array<string, int> nombre d'entreprises par région, la plus fournie en premier */
    public function countByRegion(): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select("COALESCE(c.region, 'Non renseignée') AS region, COUNT(c.id) AS total")
            ->groupBy('region')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return array_column(array_map(fn ($r) => [$r['region'], (int) $r['total']], $rows), 1, 0);
    }
}
