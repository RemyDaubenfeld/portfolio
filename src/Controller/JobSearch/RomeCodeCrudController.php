<?php

namespace App\Controller\JobSearch;

use App\Entity\RomeCode;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class RomeCodeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return RomeCode::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Code ROME')
            ->setEntityLabelInPlural('Codes ROME')
            ->setDefaultSort(['code' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('code', 'Code');
        yield TextField::new('wording', 'Libellé');
        yield BooleanField::new('active', 'Actif');
    }
}