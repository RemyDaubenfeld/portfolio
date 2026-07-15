<?php

namespace App\Controller\JobSearch;

use App\Entity\SearchCriteria;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SearchCriteriaCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SearchCriteria::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Mot-clé de recherche')
            ->setEntityLabelInPlural('Mots-clés de recherche')
            ->setDefaultSort(['keyWord' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('keyWord', 'Mot-clé');
        yield BooleanField::new('active', 'Actif');
        yield DateTimeField::new('createdAt', 'Ajouté le')->hideOnForm();
    }
}