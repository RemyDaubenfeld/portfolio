<?php

namespace App\Service\JobSearch;

use App\Entity\Company;
use App\Entity\JobOffer;
use App\Enum\CompanySource;
use App\Enum\CompanyStatus;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Rattache une offre à une fiche Company existante, ou en crée une "à qualifier".
 */
class CompanyMatcher
{
    /** Valeurs envoyées par les job boards quand l'entreprise est masquée (noms normalisés). */
    private const PLACEHOLDER_NAMES = ['non renseigne', 'nc', 'confidentiel', 'entreprise confidentielle', 'anonyme'];

    /** @var array<string, Company[]> entreprises créées mais pas encore flushées, par nom normalisé */
    private array $pending = [];

    public function __construct(
        private readonly CompanyRepository $repository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /** Lie l'offre à une entreprise ; retourne null si l'offre n'a pas de nom d'entreprise exploitable. */
    public function link(JobOffer $offer): ?Company
    {
        if ($offer->getLinkedCompany()) {
            return $offer->getLinkedCompany();
        }

        $name = trim((string) $offer->getCompany());
        $normalized = CompanyNameNormalizer::normalize($name);
        if ($normalized === '' || in_array($normalized, self::PLACEHOLDER_NAMES, true)) {
            return null;
        }

        $city = CompanyNameNormalizer::cityFromLocation($offer->getLocation());
        $company = $this->find($normalized, $city) ?? $this->create($name, $normalized, $city, $offer);

        $offer->setLinkedCompany($company);

        return $company;
    }

    private function find(string $normalized, ?string $city): ?Company
    {
        $candidates = [...$this->repository->findByNormalizedName($normalized), ...($this->pending[$normalized] ?? [])];
        if (!$candidates) {
            return null;
        }

        // Une même entreprise peut avoir plusieurs agences : on privilégie celle de la ville de l'offre
        if ($city !== null) {
            $normalizedCity = CompanyNameNormalizer::normalize($city);
            foreach ($candidates as $candidate) {
                if ($candidate->getCity() && CompanyNameNormalizer::normalize($candidate->getCity()) === $normalizedCity) {
                    return $candidate;
                }
            }
        }

        return $candidates[0];
    }

    private function create(string $name, string $normalized, ?string $city, JobOffer $offer): Company
    {
        $company = (new Company())
            ->setName($name)
            ->setCity($city)
            ->setRegion(CompanyNameNormalizer::regionFromLocation($offer->getLocation()))
            ->setSource(CompanySource::AutoOffer)
            ->setStatus(CompanyStatus::ToQualify);

        $this->em->persist($company);
        $this->pending[$normalized][] = $company;

        return $company;
    }
}
