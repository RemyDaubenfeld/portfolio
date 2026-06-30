<?php

namespace App\Controller\Admin;

use App\Entity\Interest;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class InterestCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return Interest::class; }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular("Centre d'intérêt")
            ->setEntityLabelInPlural("Centres d'intérêt")
            ->setDefaultSort(['sortOrder' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Nom');
        yield BooleanField::new('showOnPortfolio', 'Portfolio');
        yield BooleanField::new('showOnCv', 'CV');
        yield IntegerField::new('sortOrder', 'Ordre');
    }
}