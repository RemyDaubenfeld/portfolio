<?php

namespace App\Controller\JobSearch;

use App\Service\JobSearch\DailyDigest;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Déclenche le récapitulatif quotidien : appelé par n8n en fin de collecte des offres du matin.
 */
class DailyDigestApiController
{
    #[Route('/api/job-search/daily-digest', name: 'api_job_search_daily_digest', methods: ['POST'])]
    public function send(
        Request $request,
        DailyDigest $digest,
        #[Autowire('%env(JOB_OFFER_API_KEY)%')] string $expectedApiKey,
    ): JsonResponse {
        $providedKey = $request->headers->get('X-API-KEY');
        if (!$providedKey || !hash_equals($expectedApiKey, $providedKey)) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $content = $digest->collect(new \DateTimeImmutable('-24 hours'));
        $counts = array_map('count', $content);

        if (!$digest->hasNews($content) && !$request->query->getBoolean('force')) {
            return new JsonResponse(['status' => 'nothing_to_send', 'counts' => $counts]);
        }

        try {
            $digest->send($content);
        } catch (TransportExceptionInterface $e) {
            return new JsonResponse(['status' => 'error', 'error' => $e->getMessage()], 502);
        }

        return new JsonResponse(['status' => 'sent', 'counts' => $counts]);
    }
}
