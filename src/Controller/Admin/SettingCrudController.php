<?php

namespace App\Controller\Admin;

use App\Entity\Setting;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class SettingCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return Setting::class; }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Paramètre')
            ->setEntityLabelInPlural('Paramètres')
            ->setDefaultSort(['key' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('key', 'Clé')
            ->setHelp('Ex : site_title, contact_email…')
            // La clé est la PK, on la rend non-éditable après création
            ->setFormTypeOption('disabled', $pageName === Crud::PAGE_EDIT);
        yield TextareaField::new('value', 'Valeur')->setNumOfRows(3);
    }
}