<?php
namespace App\Controller\JobSearch;

use App\Entity\JobOffer;
use App\Enum\JobOfferStatus;
use App\Repository\JobOfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class JobOfferApiController
{
    #[Route('/api/job-offers', name: 'api_job_offers_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        JobOfferRepository $repository,
        #[Autowire('%env(JOB_OFFER_API_KEY)%')] string $expectedApiKey,
    ): JsonResponse {
        // --- Authentification simple par clé API ---
        $providedKey = $request->headers->get('X-API-KEY');
        if (!$providedKey || !hash_equals($expectedApiKey, $providedKey)) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        // --- Validation minimale des champs requis ---
        foreach (['title', 'company', 'source', 'url'] as $field) {
            if (empty($data[$field])) {
                return new JsonResponse(['error' => "Missing field: $field"], 400);
            }
        }

        $hash = md5($data['url']);

        // --- Dédoublonnage ---
        $existing = $repository->findOneBy(['hash' => $hash]);
        if ($existing) {
            return new JsonResponse(['status' => 'duplicate', 'id' => $existing->getId()], 200);
        }

        $offer = new JobOffer();
        $offer->setTitle($data['title']);
        $offer->setCompany($data['company']);
        $offer->setLocation($data['location'] ?? null);
        $offer->setSource($data['source']);
        $offer->setUrl($data['url']);
        $offer->setApplicationStatus(JobOfferStatus::ToReview);
        $offer->setDescription($data['description'] ?? null);

        if (!empty($data['publishedAt'])) {
            try {
                $offer->setPublishedAt(new \DateTimeImmutable($data['publishedAt']));
            } catch (\Exception) {
            
            }
        }

        $em->persist($offer);
        $em->flush();

        return new JsonResponse(['status' => 'created', 'id' => $offer->getId()], 201);
    }
}