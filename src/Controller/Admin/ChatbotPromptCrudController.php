<?php

namespace App\Controller\Admin;

use App\Entity\ChatbotPrompt;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class ChatbotPromptCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ChatbotPrompt::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setDefaultSort(['position' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            ChoiceField::new('category', 'Catégorie')
                ->setChoices([
                    'Parcours & Formation'    => 'Parcours & Formation',
                    'Expérience & Projets'    => 'Expérience & Projets',
                    'Compétences techniques'  => 'Compétences techniques',
                    'Recherche d\'emploi'     => 'Recherche d\'emploi',
                    'Personnalité & Hobbies'  => 'Personnalité & Hobbies',
                ]),
            TextField::new('context', 'Contexte')
                ->setHelp('Titre court ex: "Formation actuelle", "Stack principale"'),
            TextareaField::new('content', 'Contenu')
                ->setNumOfRows(4)
                ->setHelp('L\'information que le chatbot pourra restituer sur ce point.'),
            IntegerField::new('position', 'Ordre'),
            BooleanField::new('isActive', 'Actif'),
        ];
    }
}