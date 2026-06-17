<?php

namespace App\Controller\Admin;

use App\Entity\Skill;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Vich\UploaderBundle\Form\Type\VichImageType;

class SkillCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return Skill::class; }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Skill')
            ->setEntityLabelInPlural('Skills')
            ->setDefaultSort(['sortOrder' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Nom');

        yield ImageField::new('icon', 'Icône')
            ->setBasePath('/assets/img')
            ->setRequired(false)
            ->hideOnForm();

        yield Field::new('iconFile', 'Icône')
            ->setFormType(VichImageType::class)
            ->setRequired(false)
            ->hideOnIndex();

        yield AssociationField::new('category', 'Catégorie');
        yield IntegerField::new('sortOrder', 'Ordre');
    }
}
