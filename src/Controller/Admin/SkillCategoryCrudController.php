<?php

namespace App\Controller\Admin;

use App\Entity\SkillCategory;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

class SkillCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return SkillCategory::class; }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Catégorie de skill')
            ->setEntityLabelInPlural('Catégories de skills')
            ->setDefaultSort(['sortOrder' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Nom');
        yield IntegerField::new('sortOrder', 'Ordre');
    }

    public function deleteEntity(EntityManagerInterface $em, mixed $entityInstance): void
{
    try {
        parent::deleteEntity($em, $entityInstance);
    } catch (ForeignKeyConstraintViolationException) {
        $this->addFlash(
            'danger',
            'Impossible de supprimer cette catégorie : des skills y sont rattachés.'
        );
    }
}
}