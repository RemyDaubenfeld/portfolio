<?php

namespace App\Tests\Service\JobSearch;

use App\Entity\Department;
use App\Entity\JobOffer;
use App\Entity\NegativeKeyword;
use App\Entity\SearchCriteria;
use App\Service\JobSearch\OfferScorer;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class OfferScorerTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        foreach (['job_application', 'job_offer', 'search_criteria', 'negative_keyword', 'department'] as $table) {
            $this->em->getConnection()->executeStatement("DELETE FROM $table");
        }

        foreach (['php', 'symfony', 'full-stack', 'mysql', 'backend'] as $keyword) {
            $this->em->persist((new SearchCriteria())->setKeyWord($keyword)->setActive(true));
        }
        foreach (['java', 'c#', '.net', 'alternance', 'front-end'] as $keyword) {
            $this->em->persist((new NegativeKeyword())->setKeyWord($keyword));
        }
        $this->em->persist((new NegativeKeyword())->setKeyWord('senior')->setActive(false));
        $this->em->persist((new Department())->setCode('57')->setName('Moselle'));
        $this->em->persist((new Department())->setCode('54')->setName('Meurthe-et-Moselle'));
        $this->em->persist((new Department())->setCode('67')->setName('Bas-Rhin')->setActive(false));
        $this->em->flush();
    }

    #[DataProvider('offers')]
    public function testScore(string $title, ?string $location, ?string $description, int $expected): void
    {
        $offer = (new JobOffer())->setTitle($title)->setLocation($location)->setDescription($description);
        static::getContainer()->get(OfferScorer::class)->score($offer);

        self::assertSame($expected, $offer->getRelevanceScore(), json_encode($offer->getRelevanceDetails(), JSON_UNESCAPED_UNICODE));
    }

    public static function offers(): iterable
    {
        // base 40
        yield 'PHP Symfony en Meurthe-et-Moselle' => ['Développeur PHP - Symfony H/F', 'Nancy - 54', null, 40 + 15 + 15 + 15];
        yield 'variantes de full-stack' => ['DEVELOPPEUR FULLSTACK', '57 - Metz', null, 40 + 15 + 15];
        yield 'mot-clé dans la description' => ['Développeur web', 'Metz (57)', 'Stack : Symfony, MySQL', 40 + 5 + 5 + 15];
        yield 'java ne correspond pas à javascript' => ['Développeur JavaScript', null, null, 40];
        yield 'c# et .net' => ['Analyste Développeur C# - .Net', 'Nancy - 54', null, 40 - 30 - 15 + 15];
        yield 'mot-clé inactif ignoré' => ['Senior Backend Developer', null, null, 40 + 15];
        yield 'département inactif = hors zone' => ['Développeur PHP', 'Strasbourg (67)', null, 40 + 15 - 20];
        yield 'Luxembourg sans département' => ['Software Developer', 'Luxembourg, Luxembourg', null, 40 + 10];
        yield 'télétravail' => ['Développeur Full Stack', 'Télétravail', null, 40 + 15 + 5];
        yield 'plafond du titre' => ['Dev PHP Symfony MySQL Backend Full-Stack', null, null, 40 + 45];
        yield 'jamais sous 0' => ['Alternance Java C# .Net Front-End', 'Paris (75)', null, 0];
    }

    public function testComputedFieldsDoNotTouchUpdatedAt(): void
    {
        $offer = (new JobOffer())->setTitle('Développeur PHP')->setSource('indeed')->setUrl('https://example.test/' . uniqid());
        $this->em->persist($offer);
        $this->em->flush();
        self::assertNull($offer->getUpdatedAt());

        static::getContainer()->get(OfferScorer::class)->scoreAll([$offer]);
        self::assertSame(55, $offer->getRelevanceScore());
        self::assertNull($offer->getUpdatedAt(), 'Recalculer la note ne doit pas compter comme une modification');

        $offer->setNotes('Vu');
        $this->em->flush();
        self::assertNotNull($offer->getUpdatedAt());
    }
}
