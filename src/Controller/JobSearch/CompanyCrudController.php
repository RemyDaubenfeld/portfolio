<?php

namespace App\Controller\JobSearch;

use App\Entity\Company;
use App\Entity\JobApplication;
use App\Enum\CompanySource;
use App\Enum\CompanyStatus;
use App\Enum\JobApplicationStatus;
use App\Enum\JobApplicationType;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Symfony\Component\HttpFoundation\Response;

class CompanyCrudController extends AbstractCrudController
{
    use RedirectBackTrait;

    public static function getEntityFqcn(): string
    {
        return Company::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Entreprise')
            ->setEntityLabelInPlural('Entreprises')
            ->setDefaultSort(['name' => 'ASC'])
            ->setSearchFields(['name', 'city', 'type', 'technologies', 'jobs']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $spontaneous = Action::new('spontaneous', 'Candidature spontanée envoyée', 'fa fa-paper-plane')
            ->linkToCrudAction('spontaneous')
            ->renderAsForm()
            ->asSuccessAction()
            ->displayIf(fn (Company $company) => in_array($company->getStatus(), [CompanyStatus::ToQualify, CompanyStatus::ToContact], true));

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $spontaneous)
            ->add(Crud::PAGE_DETAIL, $spontaneous);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('status')
            ->add('region')
            ->add('source')
            ->add('type')
            ->add('city');
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addColumn(6);
        yield IdField::new('id')->hideOnForm()->hideOnIndex();
        yield TextField::new('name', 'Nom');
        yield TextField::new('city', 'Ville');
        yield ChoiceField::new('region', 'Région')
            ->setChoices(array_combine(Company::REGIONS, Company::REGIONS));
        yield TextField::new('type', 'Type')->setHelp('ESN, éditeur logiciel, startup...');
        yield ChoiceField::new('status', 'Statut')
            ->setChoices(array_combine(
                array_map(fn ($c) => $c->label(), CompanyStatus::cases()),
                CompanyStatus::cases()
            ))
            ->formatValue(fn ($value, Company $entity) => $entity->getStatus()->label())
            ->renderAsBadges(array_combine(
                array_map(fn ($c) => $c->value, CompanyStatus::cases()),
                array_map(fn ($c) => $c->color(), CompanyStatus::cases())
            ));
        yield ChoiceField::new('source', 'Source')
            ->setChoices(array_combine(
                array_map(fn ($c) => $c->label(), CompanySource::cases()),
                CompanySource::cases()
            ))
            ->formatValue(fn ($value, Company $entity) => $entity->getSource()->label())
            ->hideOnIndex();
        yield AssociationField::new('applications', 'Candidatures')->hideOnForm();
        yield AssociationField::new('jobOffers', 'Offres')->hideOnForm();

        yield FormField::addColumn(6);
        yield UrlField::new('website', 'Site web')->hideOnIndex();
        yield UrlField::new('applicationUrl', 'Page carrières / formulaire')->hideOnIndex();
        yield EmailField::new('contactEmail', 'Email de contact')->hideOnIndex();
        yield TextField::new('address', 'Adresse')->hideOnIndex();
        yield TextField::new('workforce', 'Effectif')->hideOnIndex();
        yield TextField::new('remote', 'Télétravail')->hideOnIndex();
        yield TextareaField::new('technologies', 'Technologies')->hideOnIndex();
        yield TextareaField::new('jobs', 'Métiers')->hideOnIndex();
        yield TextareaField::new('details', 'Activité')->hideOnIndex();
        yield TextareaField::new('notes', 'Notes perso')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Ajoutée le')->onlyOnDetail();
    }

    /** Enregistre une candidature spontanée envoyée à l'entreprise. */
    #[AdminRoute('/{entityId}/spontaneous', name: 'spontaneous', options: ['methods' => ['POST']])]
    public function spontaneous(AdminContext $context, EntityManagerInterface $em): Response
    {
        /** @var Company $company */
        $company = $context->getEntity()->getInstance();

        $application = (new JobApplication())
            ->setCompany($company)
            ->setType(JobApplicationType::Spontaneous)
            ->setStatus(JobApplicationStatus::Sent);
        $em->persist($application);
        $em->flush();

        $this->addFlash('success', sprintf(
            'Candidature spontanée enregistrée pour %s. Relance prévue le %s.',
            $company->getName(),
            $application->getFollowUpAt()->format('d/m/Y'),
        ));

        return $this->redirectBack($context);
    }
}
