<?php
namespace App\Twig;

use App\Repository\ChatbotConfigRepository;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class ChatbotExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private ChatbotConfigRepository $chatbotConfigRepository,
    ) {}

    public function getGlobals(): array
    {
        return [
            'chatbotConfig' => $this->chatbotConfigRepository->getConfig(),
        ];
    }
}