<?php

namespace App\Controller\Admin;

use App\Entity\Education;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class EducationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return Education::class; }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Formation')
            ->setEntityLabelInPlural('Formations & Diplômes')
            ->setDefaultSort(['sortOrder' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title', 'Diplôme / Formation');
        yield TextField::new('institution', 'Établissement');
        yield TextField::new('location', 'Lieu')->setRequired(false);
        yield TextField::new('startDate', 'Début')->setHelp('Ex: Décembre 2023');
        yield TextField::new('endDate', 'Fin')->setHelp('Laisser vide si en cours')->setRequired(false);
        yield TextareaField::new('description', 'Description')->setRequired(false)->hideOnIndex();
        yield IntegerField::new('sortOrder', 'Ordre');
    }
}
