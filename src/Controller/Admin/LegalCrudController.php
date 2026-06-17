<?php

namespace App\Controller\Admin;

use App\Entity\Legal;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;

class LegalCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return Legal::class; }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Mentions légales')
            ->setEntityLabelInPlural('Mentions légales');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('hostName', 'Hébergeur — nom');
        yield TextareaField::new('hostAddress', 'Hébergeur — adresse')->setNumOfRows(3);
        yield UrlField::new('hostWebsite', 'Hébergeur — site web')->setRequired(false);
        yield TelephoneField::new('hostPhone', 'Hébergeur — téléphone')->setRequired(false);
        yield DateField::new('updatedAt', 'Mis à jour le');
    }
}