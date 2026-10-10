<?php

namespace App\Controller\JobSearch;

use App\Entity\NegativeKeyword;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class NegativeKeywordCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return NegativeKeyword::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Mot-clé à éviter')
            ->setEntityLabelInPlural('Mots-clés à éviter')
            ->setPageTitle(Crud::PAGE_INDEX, 'Mots-clés à éviter')
            ->setHelp(Crud::PAGE_INDEX, 'Font baisser la note de pertinence des offres qui les contiennent. Ils ne sont pas envoyés à n8n.')
            ->setDefaultSort(['keyWord' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->hideOnIndex();
        yield TextField::new('keyWord', 'Mot-clé')->setHelp('Insensible aux accents et à la casse. « front-end » couvre aussi « frontend » et « front end ».');
        yield BooleanField::new('active', 'Actif');
    }
}
