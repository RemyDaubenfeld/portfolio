<?php
namespace App\Controller\JobSearch;

use App\Entity\JobOffer;
use App\Enum\JobOfferStatus;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

class JobOfferCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return JobOffer::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Offre')
            ->setEntityLabelInPlural('Offres d\'emploi')
            ->setDefaultSort(['publishedAt' => 'DESC'])
            ->setSearchFields(['title', 'company', 'location']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('source')
            ->add('applicationStatus')
            ->add('publishedAt');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title', 'Intitulé');
        yield TextField::new('company', 'Entreprise');
        yield TextField::new('location', 'Lieu');
        yield ChoiceField::new('source', 'Source')
            ->setChoices([
                'France Travail' => 'france_travail',
                'Indeed' => 'indeed',
                'LinkedIn' => 'linkedin',
            ]);
        yield UrlField::new('url', 'Lien');
        yield DateTimeField::new('publishedAt', 'Publiée le');
        yield ChoiceField::new('applicationStatus', 'Statut')
            ->setChoices(array_combine(
                array_map(fn($c) => $c->label(), JobOfferStatus::cases()),
                JobOfferStatus::cases()
            ))
            ->formatValue(fn ($value, $entity) => $entity->getApplicationStatus()->label())
            ->renderAsBadges([
                'to_review' => 'secondary',
                'applied' => 'info',
                'follow_up' => 'warning',
                'interview' => 'primary',
                'rejected' => 'danger',
                'accepted' => 'success',
    ]);
        yield TextareaField::new('description', 'Description')->hideOnIndex();
        yield TextareaField::new('notes', 'Notes perso')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Ajoutée le')->hideOnForm();
    }
}