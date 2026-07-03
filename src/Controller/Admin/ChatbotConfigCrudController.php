<?php

namespace App\Controller\Admin;

use App\Entity\ChatbotConfig;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ChatbotConfigCrudController extends AbstractCrudController
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $groqApiKey,
    ) {}

    public static function getEntityFqcn(): string
    {
        return ChatbotConfig::class;
    }

    private function fetchGroqModels(): array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.groq.com/openai/v1/models', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                ],
                'timeout' => 5,
            ]);

            $data = $response->toArray();
            $models = array_column($data['data'] ?? [], 'id');
            sort($models);

            return array_combine($models, $models);
        } catch (\Exception $e) {
            // Fallback si l'API est indisponible
            return [
                'llama-3.3-70b-versatile' => 'llama-3.3-70b-versatile',
                'llama-3.1-8b-instant'    => 'llama-3.1-8b-instant',
                'mixtral-8x7b-32768'      => 'mixtral-8x7b-32768',
            ];
        }
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', 'Nom du chatbot'),
            BooleanField::new('isActive', 'Actif'),
            Field::new('iconFile', 'Icône')
                ->setFormType(VichImageType::class)
                ->onlyOnForms(),
            TextareaField::new('introMessage1', 'Message d\'intro 1')
                ->setNumOfRows(3),
            TextareaField::new('introMessage2', 'Message d\'intro 2')
                ->setNumOfRows(3),
            TextareaField::new('rules', 'Règles de comportement')
                ->setNumOfRows(10)
                ->hideOnIndex()
                ->setHelp('Décris le ton, les sujets autorisés/refusés, le comportement général du chatbot.'),
            ChoiceField::new('model', 'Modèle Groq')
                ->setChoices($this->fetchGroqModels()),
            NumberField::new('temperature', 'Température')
                ->setNumDecimals(1),
            IntegerField::new('maxTokens', 'Max tokens'),
        ];
    }
}