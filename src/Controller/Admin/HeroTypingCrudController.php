<?php

namespace App\Controller\Admin;

use App\Entity\HeroTyping;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class HeroTypingCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return HeroTyping::class; }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Phrase (typing)')
            ->setEntityLabelInPlural('Phrases (typing)')
            ->setDefaultSort(['sortOrder' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('phrase', 'Phrase')->setHelp('Affichée en animation dans le hero');
        yield IntegerField::new('sortOrder', 'Ordre');
    }
}