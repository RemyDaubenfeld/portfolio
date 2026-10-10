<?php

namespace App\Service\JobSearch;

use App\Entity\JobOffer;
use App\Enum\JobOfferStatus;
use App\Repository\DepartmentRepository;
use App\Repository\JobOfferRepository;
use App\Repository\NegativeKeywordRepository;
use App\Repository\SearchCriteriaRepository;
use Doctrine\ORM\EntityManagerInterface;

use function Symfony\Component\String\u;

/**
 * Note de pertinence d'une offre (0 à 100) : mots-clés de recherche, mots-clés à éviter et lieu.
 * Le détail du calcul est conservé sur l'offre pour être affiché.
 */
class OfferScorer
{
    public const BASE = 40;

    // Mots-clés de recherche (les mêmes que ceux envoyés à n8n)
    private const KEYWORD_IN_TITLE = 15;
    private const KEYWORD_IN_DESCRIPTION = 5;
    private const MAX_TITLE_BONUS = 45;
    private const MAX_DESCRIPTION_BONUS = 15;

    // Mots-clés à éviter
    private const NEGATIVE_IN_TITLE = -30;
    private const NEGATIVE_IN_DESCRIPTION = -5;
    private const MAX_TITLE_PENALTY = -45;
    private const MAX_DESCRIPTION_PENALTY = -15;

    // Lieu
    private const ACTIVE_DEPARTMENT = 15;
    private const NEARBY_REGION = 10;   // Lorraine ou Luxembourg sans département identifié
    private const REMOTE = 5;
    private const OTHER_DEPARTMENT = -20;

    /** @var array{keywords: string[], negatives: string[], departments: array<string, string>}|null */
    private ?array $config = null;

    public function __construct(
        private readonly SearchCriteriaRepository $criteriaRepository,
        private readonly NegativeKeywordRepository $negativeRepository,
        private readonly DepartmentRepository $departmentRepository,
        private readonly JobOfferRepository $offerRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function score(JobOffer $offer): void
    {
        $config = $this->config();
        $title = self::normalize($offer->getTitle());
        $description = self::normalize((string) $offer->getDescription());
        $details = [];

        $this->addKeywordPoints($details, $config['keywords'], $title, $description, positive: true);
        $this->addKeywordPoints($details, $config['negatives'], $title, $description, positive: false);
        $this->addLocationPoints($details, $offer->getLocation(), $config['departments']);

        $score = self::BASE + array_sum(array_column($details, 'points'));
        $offer->setRelevance(max(0, min(100, $score)), $details);
    }

    /** Recalcule les offres à étudier, par exemple après un changement de mots-clés ou de départements. */
    public function rescoreToReview(): int
    {
        return $this->scoreAll($this->offerRepository->findBy(['applicationStatus' => JobOfferStatus::ToReview]));
    }

    /** Note les offres à étudier qui n'en ont pas encore (offres arrivées avant la mise en place du score). */
    public function scoreMissing(): int
    {
        return $this->scoreAll($this->offerRepository->findBy(['applicationStatus' => JobOfferStatus::ToReview, 'relevanceScore' => null]));
    }

    /** @param JobOffer[] $offers */
    public function scoreAll(array $offers): int
    {
        $this->config = null; // relit les mots-clés et départements, qui ont pu changer

        foreach ($offers as $offer) {
            $this->score($offer);
        }
        $this->em->flush();

        return count($offers);
    }

    /**
     * Ajoute les points des mots-clés trouvés, en distinguant titre et description, avec un plafond par zone.
     * Les mots-clés trouvés au-delà du plafond restent dans le détail, avec 0 point.
     *
     * @param list<array{label: string, points: int}> $details
     * @param string[] $keywords
     */
    private function addKeywordPoints(array &$details, array $keywords, string $title, string $description, bool $positive): void
    {
        [$inTitle, $inDescription, $maxTitle, $maxDescription] = $positive
            ? [self::KEYWORD_IN_TITLE, self::KEYWORD_IN_DESCRIPTION, self::MAX_TITLE_BONUS, self::MAX_DESCRIPTION_BONUS]
            : [self::NEGATIVE_IN_TITLE, self::NEGATIVE_IN_DESCRIPTION, self::MAX_TITLE_PENALTY, self::MAX_DESCRIPTION_PENALTY];

        $titleTotal = $descriptionTotal = 0;
        foreach ($keywords as $keyword) {
            $pattern = self::keywordPattern($keyword);

            if (preg_match($pattern, $title)) {
                $points = self::capped($inTitle, $titleTotal, $maxTitle);
                $details[] = ['label' => sprintf('« %s » dans le titre', $keyword), 'points' => $points];
            } elseif ($description !== '' && preg_match($pattern, $description)) {
                $points = self::capped($inDescription, $descriptionTotal, $maxDescription);
                $details[] = ['label' => sprintf('« %s » dans la description', $keyword), 'points' => $points];
            }
        }

    }

    /**
     * @param list<array{label: string, points: int}> $details
     * @param array<string, string> $departments numéro => nom des départements actifs
     */
    private function addLocationPoints(array &$details, ?string $location, array $departments): void
    {
        if ($location === null || trim($location) === '') {
            return;
        }

        $department = CompanyNameNormalizer::departmentFromLocation($location);
        $region = CompanyNameNormalizer::regionFromLocation($location);

        if ($department !== null && isset($departments[$department])) {
            $details[] = ['label' => sprintf('Lieu : %s (%s)', $departments[$department], $department), 'points' => self::ACTIVE_DEPARTMENT];
        } elseif ($department === null && in_array($region, ['Lorraine', 'Luxembourg'], true)) {
            $details[] = ['label' => sprintf('Lieu : %s', $region), 'points' => self::NEARBY_REGION];
        } elseif (preg_match('/t[ée]l[ée]travail|remote/iu', $location)) {
            $details[] = ['label' => 'Télétravail', 'points' => self::REMOTE];
        } elseif ($department !== null) {
            $details[] = ['label' => sprintf('Lieu hors zone (%s)', $department), 'points' => self::OTHER_DEPARTMENT];
        }
    }

    /** Points à accorder sans dépasser le plafond de la zone (titre ou description). */
    private static function capped(int $points, int &$total, int $max): int
    {
        $allowed = $max > 0 ? min($points, max(0, $max - $total)) : max($points, min(0, $max - $total));
        $total += $allowed;

        return $allowed;
    }

    /** Minuscules sans accents : « Développeur Symfony » -> « developpeur symfony ». */
    private static function normalize(string $text): string
    {
        return u($text)->ascii()->lower()->toString();
    }

    /**
     * Le mot-clé doit être un mot entier (« java » ne correspond pas à « javascript ») ;
     * espaces et tirets sont interchangeables ou facultatifs (« full-stack », « full stack », « fullstack »).
     */
    private static function keywordPattern(string $keyword): string
    {
        $parts = preg_split('/[\s\-]+/', self::normalize(trim($keyword)), -1, PREG_SPLIT_NO_EMPTY);
        $body = implode('[\s\-]?', array_map(fn ($p) => preg_quote($p, '/'), $parts));

        return '/(?<![a-z0-9])' . $body . '(?![a-z0-9])/';
    }

    /** @return array{keywords: string[], negatives: string[], departments: array<string, string>} */
    private function config(): array
    {
        return $this->config ??= [
            'keywords' => array_map(fn ($c) => $c->getKeyWord(), $this->criteriaRepository->findBy(['active' => true])),
            'negatives' => array_map(fn ($n) => $n->getKeyWord(), $this->negativeRepository->findBy(['active' => true])),
            'departments' => array_column(array_map(
                fn ($d) => [$d->getCode(), $d->getName()],
                $this->departmentRepository->findBy(['active' => true]),
            ), 1, 0),
        ];
    }
}
