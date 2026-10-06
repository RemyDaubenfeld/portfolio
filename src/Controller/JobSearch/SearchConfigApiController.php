<?php

namespace App\Controller\JobSearch;

use App\Repository\RomeCodeRepository;
use App\Repository\SearchCriteriaRepository;
use App\Repository\SettingRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class SearchConfigApiController
{
    /** Clé de la table setting listant les départements à cibler, séparés par des virgules. */
    private const DEPARTEMENTS_SETTING = 'job_search_departements';
    private const DEFAULT_DEPARTEMENTS = '54,57,55';

    #[Route('/api/search-config', name: 'api_search_config', methods: ['GET'])]
    public function index(
        Request $request,
        SearchCriteriaRepository $criteriaRepo,
        RomeCodeRepository $romeRepo,
        SettingRepository $settingRepo,
        #[Autowire('%env(JOB_OFFER_API_KEY)%')] string $expectedApiKey,
    ): JsonResponse {
        $providedKey = $request->headers->get('X-API-KEY');
        if (!$providedKey || !hash_equals($expectedApiKey, $providedKey)) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $keyWords = array_map(
            fn($c) => $c->getKeyWord(),
            $criteriaRepo->findBy(['active' => true])
        );

        $romeCodes = array_map(
            fn($r) => $r->getCode(),
            $romeRepo->findBy(['active' => true])
        );

        $departements = $settingRepo->find(self::DEPARTEMENTS_SETTING)?->getValue() ?? self::DEFAULT_DEPARTEMENTS;

        return new JsonResponse([
            'keyWords' => array_values($keyWords),
            'romeCodes' => array_values($romeCodes),
            'departements' => array_values(array_filter(array_map('trim', explode(',', $departements)))),
        ]);
    }
}