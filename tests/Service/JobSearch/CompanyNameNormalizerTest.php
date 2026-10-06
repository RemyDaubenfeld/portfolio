<?php

namespace App\Tests\Service\JobSearch;

use App\Service\JobSearch\CompanyNameNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CompanyNameNormalizerTest extends TestCase
{
    #[DataProvider('names')]
    public function testNormalize(string $name, string $expected): void
    {
        self::assertSame($expected, CompanyNameNormalizer::normalize($name));
    }

    public static function names(): iterable
    {
        yield ['ACME SAS', 'acme'];
        yield ['Acmé S.A.S.', 'acme'];
        yield ['  Sopra-Steria Group ', 'sopra steria group'];
        yield ['Abylsen EST', 'abylsen est'];
        yield ['3 CGEST', '3 cgest'];
    }

    #[DataProvider('locations')]
    public function testCityAndRegionFromLocation(?string $location, ?string $city, ?string $region): void
    {
        self::assertSame($city, CompanyNameNormalizer::cityFromLocation($location));
        self::assertSame($region, CompanyNameNormalizer::regionFromLocation($location));
    }

    public static function locations(): iterable
    {
        yield 'France Travail' => ['57 - METZ', 'Metz', 'Lorraine'];
        yield 'Indeed' => ['Strasbourg (67)', 'Strasbourg', 'Alsace'];
        yield 'code postal' => ['54000 Nancy', 'Nancy', 'Lorraine'];
        yield 'HelloWork' => ['Talange - 57', 'Talange', 'Lorraine'];
        yield 'casse conservée' => ['57070 Saint-Julien-lès-Metz', 'Saint-Julien-lès-Metz', 'Lorraine'];
        yield 'LinkedIn' => ['Luxembourg, Luxembourg', 'Luxembourg', 'Luxembourg'];
        yield 'sans département' => ['Metz, Grand Est, France', 'Metz', 'Lorraine'];
        yield 'ville seule hors liste' => ['Lille', 'Lille', null];
        yield 'télétravail' => ['Télétravail', null, null];
        yield 'hors zone' => ['75 - PARIS 08', 'Paris 08', 'Autre'];
        yield 'vide' => [null, null, null];
    }
}
