<?php

namespace App\Controller\JobSearch;

use App\Entity\CoverLetterTemplate;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CoverLetterTemplateCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CoverLetterTemplate::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Modèle de lettre')
            ->setEntityLabelInPlural('Modèles de lettre')
            ->setDefaultSort(['name' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->hideOnIndex();
        yield TextField::new('name', 'Nom')->setHelp('Par exemple : ESN, Éditeur logiciel, Startup IA.');
        yield TextareaField::new('body', 'Corps de la lettre')
            ->setNumOfRows(18)
            ->hideOnIndex()
            ->setHelp(
                'Uniquement les paragraphes, séparés par une ligne vide : « Madame, Monsieur, » et la formule de politesse sont ajoutés automatiquement. '
                . 'Variables disponibles : {entreprise}, {poste}, {ville}.'
            );
    }
}
