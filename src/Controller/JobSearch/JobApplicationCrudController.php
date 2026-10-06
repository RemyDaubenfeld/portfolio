<?php

namespace App\Controller\JobSearch;

use App\Entity\JobApplication;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\Response;

class JobApplicationCrudController extends AbstractCrudController
{
    use RedirectBackTrait;

    public static function getEntityFqcn(): string
    {
        return JobApplication::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Candidature')
            ->setEntityLabelInPlural('Candidatures')
            ->setDefaultSort(['sentAt' => 'DESC'])
            ->setSearchFields(['company.name', 'offer.title', 'notes']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $isAwaiting = fn (JobApplication $application) => $application->getStatus()->isAwaitingResponse();

        $followUp = Action::new('followUp', 'Relancé', 'fa fa-bell')
            ->linkToCrudAction('followUp')
            ->renderAsForm()
            ->asWarningAction()
            ->displayIf($isAwaiting);

        $noResponse = Action::new('noResponse', 'Sans réponse', 'fa fa-hourglass-end')
            ->linkToCrudAction('noResponse')
            ->renderAsForm()
            ->displayIf($isAwaiting);

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $followUp)
            ->add(Crud::PAGE_INDEX, $noResponse)
            ->add(Crud::PAGE_DETAIL, $followUp)
            ->add(Crud::PAGE_DETAIL, $noResponse);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('status')
            ->add('type')
            ->add('company')
            ->add('sentAt')
            ->add('followUpAt');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->hideOnIndex();
        yield AssociationField::new('company', 'Entreprise')->autocomplete();
        yield AssociationField::new('offer', 'Offre')
            ->autocomplete()
            ->setHelp('Laisser vide pour une candidature spontanée.');
        yield ChoiceField::new('type', 'Type')
            ->setChoices(array_combine(
                array_map(fn ($c) => $c->label(), JobApplicationType::cases()),
                JobApplicationType::cases()
            ))
            ->formatValue(fn ($value, JobApplication $entity) => $entity->getType()->label());
        yield ChoiceField::new('status', 'Statut')
            ->setChoices(array_combine(
                array_map(fn ($c) => $c->label(), JobApplicationStatus::cases()),
                JobApplicationStatus::cases()
            ))
            ->formatValue(fn ($value, JobApplication $entity) => $entity->getStatus()->label())
            ->renderAsBadges(array_combine(
                array_map(fn ($c) => $c->value, JobApplicationStatus::cases()),
                array_map(fn ($c) => $c->color(), JobApplicationStatus::cases())
            ));
        yield DateField::new('sentAt', 'Envoyée le')
            ->setHelp('Renseignée automatiquement à l\'envoi si laissée vide.');
        yield DateField::new('followUpAt', 'Relance prévue le')
            ->setHelp('Calculée automatiquement (10 jours après l\'envoi ou la dernière relance) si laissée vide.');
        yield DateField::new('lastFollowedUpAt', 'Dernière relance')->hideOnIndex();
        yield IntegerField::new('followUpCount', 'Relances')->hideOnForm();
        yield TextField::new('cvVersion', 'Version du CV')->hideOnIndex();
        yield TextareaField::new('notes', 'Notes')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Créée le')->onlyOnDetail();
    }

    #[AdminRoute('/{entityId}/follow-up', name: 'follow_up', options: ['methods' => ['POST']])]
    public function followUp(AdminContext $context, EntityManagerInterface $em): Response
    {
        /** @var JobApplication $application */
        $application = $context->getEntity()->getInstance();
        $application->followUp();
        $em->flush();

        $this->addFlash('success', sprintf(
            'Relance n°%d enregistrée pour %s. Prochaine relance le %s.',
            $application->getFollowUpCount(),
            $application,
            $application->getFollowUpAt()->format('d/m/Y'),
        ));

        return $this->redirectBack($context);
    }

    #[AdminRoute('/{entityId}/no-response', name: 'no_response', options: ['methods' => ['POST']])]
    public function noResponse(AdminContext $context, EntityManagerInterface $em): Response
    {
        /** @var JobApplication $application */
        $application = $context->getEntity()->getInstance();
        $application->setStatus(JobApplicationStatus::NoResponse);
        $em->flush();

        $this->addFlash('info', sprintf('Candidature %s classée sans réponse.', $application));

        return $this->redirectBack($context);
    }
}
