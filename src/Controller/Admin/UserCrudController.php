<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Vich\UploaderBundle\Form\Type\VichImageType;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Profil')
            ->setEntityLabelInPlural('Profil')
            ->setPageTitle(Crud::PAGE_INDEX, 'Mon profil')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier le profil');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield TextField::new('firstName', 'Prénom');
        yield TextField::new('lastName', 'Nom');
        yield TextField::new('jobTitle', 'Titre / poste');

        yield TextareaField::new('heroTagline', 'Accroche hero')
            ->setNumOfRows(2)
            ->setHelp('Texte affiché sous le titre dans la section hero. HTML autorisé (<strong>).')
            ->hideOnIndex();

        yield TextareaField::new('bioHeadline', 'Bio — accroche')
            ->setNumOfRows(2)->hideOnIndex();
        yield TextareaField::new('bioBackground', 'Bio — parcours')
            ->setNumOfRows(4)->hideOnIndex();
        yield TextareaField::new('bioObjective', 'Bio — objectif')
            ->setNumOfRows(3)->hideOnIndex();
        yield TextareaField::new('cvSummary', 'Accroche CV')
            ->setNumOfRows(3)
            ->setHelp('Texte affiché uniquement sur le CV. Si vide, la bio accroche sera utilisée.')
            ->hideOnIndex();

        yield TextField::new('situation', 'Situation actuelle')->hideOnIndex();
        yield TextField::new('contractType', 'Type de contrat recherché')->hideOnIndex();
        yield TextareaField::new('availability', 'Disponibilité')->hideOnIndex();
        yield TextareaField::new('contactTagline', 'Tagline section contact')->hideOnIndex();

        yield EmailField::new('email', 'Email');
        yield TelephoneField::new('phone', 'Téléphone')->hideOnIndex();
        yield TextField::new('location', 'Localisation')->hideOnIndex();
        yield UrlField::new('mapUrl', 'Lien Google Maps')->hideOnIndex();

        yield UrlField::new('linkedinUrl', 'LinkedIn')->hideOnIndex();
        yield UrlField::new('githubUrl', 'GitHub')->hideOnIndex();
        yield UrlField::new('websiteUrl', 'Site web')->hideOnIndex();

        yield TextField::new('birthDate', 'Date / âge')->hideOnIndex()
            ->setHelp('Ex: 38 ans ou 12/05/1987');
        yield BooleanField::new('drivingLicense', 'Permis B')->hideOnIndex();
        yield BooleanField::new('vehicle', 'Véhicule personnel')->hideOnIndex();

        yield Field::new('photoFileFile', 'Photo de profil')
            ->setFormType(VichImageType::class)
            ->setRequired(false)
            ->hideOnIndex();
    }
}