<?php

namespace App\Controller\JobSearch;

use App\Entity\Department;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class DepartmentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Department::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Département')
            ->setEntityLabelInPlural('Départements')
            ->setDefaultSort(['code' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->hideOnIndex();
        yield TextField::new('code', 'Numéro')->setHelp('Par exemple : 57, 2A.');
        yield TextField::new('name', 'Nom');
        yield BooleanField::new('active', 'Actif');
    }
}
