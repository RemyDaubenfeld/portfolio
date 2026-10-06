<?php

namespace App\Service\JobSearch;

use function Symfony\Component\String\u;

/**
 * Normalise un nom d'entreprise pour comparer "ACME SAS", "Acme" et "acmé s.a.s." comme une seule entreprise.
 */
final class CompanyNameNormalizer
{
    /** Mentions de lieu qui ne sont pas une ville (normalisées). */
    private const NOT_A_CITY = ['teletravail', 'remote', 'full remote', 'france'];

    /** Villes principales de la zone de recherche, pour les offres sans numéro de département. */
    private const CITY_REGIONS = [
        'metz' => 'Lorraine', 'nancy' => 'Lorraine', 'thionville' => 'Lorraine', 'epinal' => 'Lorraine',
        'verdun' => 'Lorraine', 'bar le duc' => 'Lorraine', 'sarreguemines' => 'Lorraine', 'forbach' => 'Lorraine',
        'strasbourg' => 'Alsace', 'mulhouse' => 'Alsace', 'colmar' => 'Alsace', 'haguenau' => 'Alsace',
    ];

    private const LEGAL_FORMS = ['sa', 'sas', 'sasu', 'sarl', 'eurl', 'sci', 'snc', 'scop', 'gmbh', 'ltd', 'inc', 'sl', 'bv', 'nv'];

    public static function normalize(string $name): string
    {
        $name = u($name)->ascii()->lower()->toString();
        // "s.a.s." -> "sas" avant de remplacer la ponctuation par des espaces
        $name = str_replace('.', '', $name);
        $name = preg_replace('/[^a-z0-9]+/', ' ', $name);

        $words = array_filter(
            explode(' ', $name),
            fn (string $word) => $word !== '' && !in_array($word, self::LEGAL_FORMS, true),
        );

        return implode(' ', $words);
    }

    /** Extrait la ville d'une localisation d'offre ("57 - METZ", "Metz - 57", "Metz (57)", "57000 Metz", "Metz, Grand Est, France"). */
    public static function cityFromLocation(?string $location): ?string
    {
        if ($location === null || trim($location) === '') {
            return null;
        }

        $city = explode(',', $location)[0];
        $city = preg_replace(['/^\s*\d{2,3}\s*-\s*/', '/\s*-\s*\d{2,3}\s*$/', '/\(\s*\d{2,5}\s*\)/', '/\b\d{5}\b/'], '', $city);
        $city = trim($city, " \t-");

        if ($city === '' || in_array(self::normalize($city), self::NOT_A_CITY, true)) {
            return null;
        }

        // France Travail envoie parfois les villes en majuscules ("54 - NANCY") : on ne retouche que ce cas
        return $city === mb_strtoupper($city) ? mb_convert_case(mb_strtolower($city), MB_CASE_TITLE) : $city;
    }

    /** Déduit la région (Alsace / Lorraine / Luxembourg / Autre) d'une localisation d'offre. */
    public static function regionFromLocation(?string $location): ?string
    {
        if ($location === null || trim($location) === '') {
            return null;
        }

        if (stripos($location, 'luxembourg') !== false) {
            return 'Luxembourg';
        }

        if (preg_match('/\b(\d{2})(?:\d{3})?\b/', $location, $matches)) {
            return match ($matches[1]) {
                '67', '68' => 'Alsace',
                '54', '55', '57', '88' => 'Lorraine',
                default => 'Autre',
            };
        }

        $city = self::cityFromLocation($location);

        return $city === null ? null : (self::CITY_REGIONS[self::normalize($city)] ?? null);
    }
}
