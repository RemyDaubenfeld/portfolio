<?php

namespace App\Controller\Admin;

use App\Entity\Project;
use App\Form\ProjectLinkType;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use Vich\UploaderBundle\Form\Type\VichImageType;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class ProjectCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Project::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Projet')
            ->setEntityLabelInPlural('Projets')
            ->setDefaultSort(['sortOrder' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title', 'Titre');
        yield SlugField::new('slug')->setTargetFieldName('title')->hideOnIndex();
        yield BooleanField::new('inProgress', 'En cours');

        yield TextareaField::new('description', 'Description')
            ->setNumOfRows(4)->hideOnIndex();

        yield ImageField::new('image', 'Image')
            ->setBasePath('/assets/img')
            ->setRequired(false)
            ->hideOnForm();

        yield Field::new('imageFile', 'Image')
            ->setFormType(VichImageType::class)
            ->setRequired(false)
            ->hideOnIndex();

        yield UrlField::new('url', 'URL du projet')->hideOnIndex();
        yield UrlField::new('githubUrl', 'GitHub')->hideOnIndex();

        yield AssociationField::new('technologies', 'Technologies')
            ->setFormTypeOptions(['by_reference' => false])
            ->hideOnIndex();

        yield ArrayField::new('features', 'Fonctionnalités')->hideOnIndex();
        yield CollectionField::new('links', 'Liens supplémentaires')
            ->setEntryType(ProjectLinkType::class)
            ->setFormTypeOptions(['by_reference' => false])
            ->allowAdd()
            ->allowDelete()
            ->setEntryIsComplex()
            ->hideOnIndex();

        yield IntegerField::new('sortOrder', 'Ordre');

        yield BooleanField::new('showOnCv', 'Afficher sur le CV');
        yield TextareaField::new('cvDescription', 'Descriptif CV')
            ->setNumOfRows(3)
            ->setHelp('Descriptif court affiché dans le CV. Rempli uniquement si le projet apparaît sur le CV.')
            ->hideOnIndex();

        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
    }
}