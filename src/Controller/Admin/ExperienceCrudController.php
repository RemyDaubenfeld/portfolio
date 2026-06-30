<?php

namespace App\Controller\Admin;

use App\Entity\Experience;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class ExperienceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return Experience::class; }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Expérience')
            ->setEntityLabelInPlural('Expériences')
            ->setDefaultSort(['sortOrder' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title', 'Poste');
        yield TextField::new('company', 'Entreprise');
        yield TextField::new('location', 'Lieu');
        yield ChoiceField::new('type', 'Type')
            ->setChoices([
                'Stage' => 'stage',
                'Emploi' => 'emploi',
                'Freelance' => 'freelance',
                'Bénévolat' => 'benevolat',
            ])
            ->allowMultipleChoices(false);
        yield TextField::new('startDate', 'Début')->setHelp('Ex: Juillet 2024');
        yield TextField::new('endDate', 'Fin')->setHelp('Laisser vide si en cours')->setRequired(false);
        yield TextareaField::new('description', 'Description')->setRequired(false)->hideOnIndex();
        yield IntegerField::new('sortOrder', 'Ordre');
    }
}
