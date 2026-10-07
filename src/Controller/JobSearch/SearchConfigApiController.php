<?php

namespace App\Controller\JobSearch;

use App\Repository\DepartmentRepository;
use App\Repository\RomeCodeRepository;
use App\Repository\SearchCriteriaRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class SearchConfigApiController
{
    #[Route('/api/search-config', name: 'api_search_config', methods: ['GET'])]
    public function index(
        Request $request,
        SearchCriteriaRepository $criteriaRepo,
        RomeCodeRepository $romeRepo,
        DepartmentRepository $departmentRepo,
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

        $departements = array_map(
            fn($d) => $d->getCode(),
            $departmentRepo->findBy(['active' => true], ['code' => 'ASC'])
        );

        return new JsonResponse([
            'keyWords' => array_values($keyWords),
            'romeCodes' => array_values($romeCodes),
            'departements' => array_values($departements),
        ]);
    }
}