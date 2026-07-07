<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class HealthController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    #[Route('/health', name: 'app_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        $databaseStatus = $this->checkDatabase();
        $chatbotStatus = $this->checkChatbot();

        $overallStatus = ($databaseStatus === 'ok' && $chatbotStatus === 'ok') ? 'ok' : 'degraded';

        // 200 même en cas de "degraded" : le endpoint lui-même fonctionne,
        // c'est le corps JSON qui porte le détail (le monitoring externe
        // fait la distinction entre "endpoint down" et "service dégradé").
        return $this->json([
            'status' => $overallStatus,
            'database' => $databaseStatus,
            'chatbot' => $chatbotStatus,
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ]);
    }

    private function checkDatabase(): string
    {
        try {
            $this->connection->executeQuery('SELECT 1');

            return 'ok';
        } catch (\Throwable $e) {
            return 'error';
        }
    }

    private function checkChatbot(): string
    {
        $groqApiKey = $_ENV['GROQ_API_KEY'] ?? null;

        if (!$groqApiKey) {
            return 'error';
        }

        try {
            // On tape /models plutôt qu'une vraie complétion : ça vérifie la clé API
            // et la connectivité Groq sans consommer de tokens ni dépendre d'une
            // réponse LLM (rapide, gratuit, déterministe).
            $response = $this->httpClient->request('GET', 'https://api.groq.com/openai/v1/models', [
                'headers' => [
                    'Authorization' => 'Bearer '.$groqApiKey,
                ],
                'timeout' => 5,
            ]);

            return $response->getStatusCode() === 200 ? 'ok' : 'error';
        } catch (TransportExceptionInterface|\Throwable $e) {
            return 'error';
        }
    }
}