<?php

namespace App\Controller\JobSearch;

use App\Entity\JobApplication;
use App\Repository\CoverLetterTemplateRepository;
use App\Service\JobSearch\CoverLetterGenerator;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

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

        $coverLetter = Action::new('coverLetter', 'Lettre de motivation', 'fa fa-file-lines')
            ->linkToCrudAction('coverLetter');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $followUp)
            ->add(Crud::PAGE_INDEX, $noResponse)
            ->add(Crud::PAGE_INDEX, $coverLetter)
            ->add(Crud::PAGE_DETAIL, $followUp)
            ->add(Crud::PAGE_DETAIL, $noResponse)
            ->add(Crud::PAGE_DETAIL, $coverLetter);
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
        yield Field::new('events', 'Historique')
            ->onlyOnDetail()
            ->setTemplatePath('admin/field/application_timeline.html.twig');
    }

    /** Rédaction de la lettre : application d'un modèle puis ajustement du texte. */
    #[AdminRoute('/{entityId}/cover-letter', name: 'cover_letter', options: ['methods' => ['GET', 'POST']])]
    public function coverLetter(
        AdminContext $context,
        Request $request,
        EntityManagerInterface $em,
        CoverLetterTemplateRepository $templateRepository,
        CoverLetterGenerator $generator,
    ): Response {
        /** @var JobApplication $application */
        $application = $context->getEntity()->getInstance();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('cover_letter', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            if ($request->request->has('apply_template')) {
                $template = $templateRepository->find($request->request->getInt('template'));
                if ($template) {
                    $application->setCoverLetter($generator->fill($template, $application));
                    $this->addFlash('success', sprintf('Modèle « %s » appliqué. Ajustez le texte puis enregistrez.', $template->getName()));
                }
            } else {
                $application->setCoverLetter(trim($request->request->getString('body')) ?: null);
                $this->addFlash('success', 'Lettre enregistrée.');
            }
            $em->flush();

            return $this->redirect($request->getUri());
        }

        return $this->render('admin/job_application/cover_letter.html.twig', [
            'application' => $application,
            'subject' => $generator->subject($application),
            'templates' => $templateRepository->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[AdminRoute('/{entityId}/cover-letter.pdf', name: 'cover_letter_pdf', options: ['methods' => ['GET']])]
    public function coverLetterPdf(AdminContext $context, Request $request, CoverLetterGenerator $generator): Response
    {
        /** @var JobApplication $application */
        $application = $context->getEntity()->getInstance();

        $disposition = $request->query->getBoolean('download') ? ResponseHeaderBag::DISPOSITION_ATTACHMENT : ResponseHeaderBag::DISPOSITION_INLINE;
        $response = new Response($generator->renderPdf($application), 200, ['Content-Type' => 'application/pdf']);
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition($disposition, $generator->filename($application)));

        return $response;
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
