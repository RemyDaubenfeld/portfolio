<?php

namespace App\Controller;

use App\Repository\ChatbotConfigRepository;
use App\Repository\ChatbotPromptRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ChatController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $groqApiKey,
        private RateLimiterFactoryInterface $chatApiLimiter,
        private ChatbotConfigRepository $chatbotConfigRepository,
        private ChatbotPromptRepository $chatbotPromptRepository,
    ) {}

    #[Route('/api/chat', name: 'api_chat', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        $limiter = $this->chatApiLimiter->create($request->getClientIp());
        $limit = $limiter->consume(1);

        if (!$limit->isAccepted()) {
            return $this->json([
                'error' => "Stag'IA'ire est surchargé de travail, merci de patienter quelques instants.",
            ], 429, [
                'Retry-After' => $limit->getRetryAfter()->getTimestamp() - time(),
            ]);
        }

        $config = $this->chatbotConfigRepository->getConfig();

        if (!$config->isActive()) {
            return $this->json(['error' => "Stag'IA'ire est parti chercher du café, il revient vite."], 503);
        }

        $data = json_decode($request->getContent(), true);
        $messages = $data['messages'] ?? [];

        if (empty($messages)) {
            return $this->json(['error' => 'No messages provided'], 400);
        }

        $payload = array_merge(
            [['role' => 'system', 'content' => $this->buildSystemPrompt($config)]],
            $messages
        );

        try {
            $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $config->getModel(),
                    'max_tokens' => $config->getMaxTokens(),
                    'temperature' => $config->getTemperature(),
                    'messages' => $payload,
                ],
                'timeout' => 15,
            ]);

            $result = $response->toArray();

            return $this->json([
                'message' => $result['choices'][0]['message']['content'] ?? 'Erreur de réponse.',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => "Stag'IA'ire est parti chercher du café, il revient vite."], 503);
        }
    }

    #[Route('/api/chat/config', name: 'api_chat_config', methods: ['GET'])]
    public function config(): JsonResponse
    {
        $config = $this->chatbotConfigRepository->getConfig();

        return $this->json([
            'name'          => $config->getName(),
            'introMessage1' => $config->getIntroMessage1(),
            'introMessage2' => $config->getIntroMessage2(),
            'isActive'      => $config->isActive(),
        ]);
    }

    private function buildSystemPrompt(\App\Entity\ChatbotConfig $config): string
    {
        $prompts = $this->chatbotPromptRepository->findActive();

        $knowledge = '';
        $currentCategory = '';

        foreach ($prompts as $prompt) {
            if ($prompt->getCategory() !== $currentCategory) {
                $currentCategory = $prompt->getCategory();
                $knowledge .= "\n## {$currentCategory}\n";
            }
            $knowledge .= "- **{$prompt->getContext()}** : {$prompt->getContent()}\n";
        }

        $rules = $config->getRules() ?? '';

        return <<<PROMPT
{$rules}

---

Voici les informations sur Rémy que tu peux utiliser pour répondre :

{$knowledge}
PROMPT;
    }
}