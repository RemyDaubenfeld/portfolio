<?php
namespace App\Controller\JobSearch;

use App\Entity\JobApplication;
use App\Entity\JobOffer;
use App\Enum\JobOfferStatus;
use App\Service\JobSearch\CompanyMatcher;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Symfony\Component\HttpFoundation\Response;

class JobOfferCrudController extends AbstractCrudController
{
    use RedirectBackTrait;

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

    public function configureActions(Actions $actions): Actions
    {
        $isToReview = fn (JobOffer $offer) => $offer->getApplicationStatus() === JobOfferStatus::ToReview;

        $open = Action::new('open', 'Voir l\'annonce', 'fa fa-up-right-from-square')
            ->linkToUrl(fn (JobOffer $offer) => $offer->getUrl())
            ->setHtmlAttributes(['target' => '_blank', 'rel' => 'noopener']);

        $apply = Action::new('apply', 'Postuler', 'fa fa-paper-plane')
            ->linkToCrudAction('apply')
            ->renderAsForm()
            ->asSuccessAction()
            ->displayIf($isToReview);

        $notInterested = Action::new('notInterested', 'Pas intéressé', 'fa fa-ban')
            ->linkToCrudAction('notInterested')
            ->renderAsForm()
            ->displayIf($isToReview);

        return $actions
            ->add(Crud::PAGE_INDEX, $open)
            ->add(Crud::PAGE_INDEX, $apply)
            ->add(Crud::PAGE_INDEX, $notInterested)
            ->add(Crud::PAGE_DETAIL, $open)
            ->add(Crud::PAGE_DETAIL, $apply)
            ->add(Crud::PAGE_DETAIL, $notInterested);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('source')
            ->add('applicationStatus')
            ->add('linkedCompany')
            ->add('publishedAt');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title', 'Intitulé');
        yield TextField::new('company', 'Entreprise');
        yield AssociationField::new('linkedCompany', 'Fiche entreprise')
            ->autocomplete()
            ->hideOnIndex();
        yield TextField::new('location', 'Lieu');
        yield ChoiceField::new('source', 'Source')
            ->setChoices([
                'France Travail' => 'france_travail',
                'Indeed' => 'indeed',
                'LinkedIn' => 'linkedin',
                'Hello Work' => 'hellowork',
                'Free-Work' => 'freework'
            ]);
        yield UrlField::new('url', 'Lien')->hideOnIndex();
        yield DateTimeField::new('publishedAt', 'Publiée le');
        yield ChoiceField::new('applicationStatus', 'Statut')
            ->setChoices(array_combine(
                array_map(fn($c) => $c->label(), JobOfferStatus::cases()),
                JobOfferStatus::cases()
            ))
            ->formatValue(fn ($value, $entity) => $entity->getApplicationStatus()->label())
            ->renderAsBadges(array_combine(
                array_map(fn($c) => $c->value, JobOfferStatus::cases()),
                array_map(fn($c) => $c->color(), JobOfferStatus::cases())
            ));
        yield TextareaField::new('description', 'Description')->hideOnIndex();
        yield TextareaField::new('notes', 'Notes perso')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Ajoutée le')->hideOnForm();
    }

    /** Crée la candidature liée à l'offre (et la fiche entreprise si besoin). */
    #[AdminRoute('/{entityId}/apply', name: 'apply', options: ['methods' => ['POST']])]
    public function apply(AdminContext $context, EntityManagerInterface $em, CompanyMatcher $companyMatcher): Response
    {
        /** @var JobOffer $offer */
        $offer = $context->getEntity()->getInstance();

        $companyMatcher->link($offer);
        $application = JobApplication::fromOffer($offer);
        $em->persist($application);
        $em->flush();

        $this->addFlash('success', sprintf(
            'Candidature enregistrée pour « %s ». Relance prévue le %s.',
            $offer->getTitle(),
            $application->getFollowUpAt()->format('d/m/Y'),
        ));

        return $this->redirectBack($context);
    }

    #[AdminRoute('/{entityId}/not-interested', name: 'not_interested', options: ['methods' => ['POST']])]
    public function notInterested(AdminContext $context, EntityManagerInterface $em): Response
    {
        /** @var JobOffer $offer */
        $offer = $context->getEntity()->getInstance();
        $offer->setApplicationStatus(JobOfferStatus::NotInterested);
        $em->flush();

        $this->addFlash('info', sprintf('Offre « %s » écartée.', $offer->getTitle()));

        return $this->redirectBack($context);
    }
}
