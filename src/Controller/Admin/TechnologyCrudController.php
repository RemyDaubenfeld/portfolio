<?php

namespace App\Controller\Admin;

use App\Entity\Technology;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Vich\UploaderBundle\Form\Type\VichImageType;

class TechnologyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return Technology::class; }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Technologie')
            ->setEntityLabelInPlural('Technologies')
            ->setDefaultSort(['name' => 'ASC']);
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
    }

    public function deleteEntity(EntityManagerInterface $em, mixed $entityInstance): void
    {
        $projectCount = (int) $em->createQuery(
            'SELECT COUNT(p.id) FROM App\Entity\Project p
             JOIN p.technologies t WHERE t.id = :id'
        )->setParameter('id', $entityInstance->getId())->getSingleScalarResult();

        if ($projectCount > 0) {
            $this->addFlash(
                'danger',
                "Impossible de supprimer cette technologie : elle est liée à {$projectCount} projet(s)."
            );
            return;
        }

        parent::deleteEntity($em, $entityInstance);
    }
}
