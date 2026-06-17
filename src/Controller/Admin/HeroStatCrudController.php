<?php

namespace App\Controller\Admin;

use App\Entity\HeroStat;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class HeroStatCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return HeroStat::class; }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Stat hero')
            ->setEntityLabelInPlural('Stats hero')
            ->setDefaultSort(['sortOrder' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('value', 'Valeur')->setHelp('Ex : 3+, 10, 42%');
        yield TextField::new('label', 'Label')->setHelp('Ex : Projets réalisés');
        yield TextField::new('sub', 'Sous-texte')->setRequired(false);
        yield IntegerField::new('sortOrder', 'Ordre');
    }
}