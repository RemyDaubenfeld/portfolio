<?php

namespace App\Controller\JobSearch;

use App\Entity\JobApplication;
use App\Repository\CoverLetterTemplateRepository;
use App\Repository\JobApplicationRepository;
use App\Service\JobSearch\ApplicationMailer;
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
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

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

        $sendEmail = Action::new('sendEmail', 'Envoyer par email', 'fa fa-envelope')
            ->linkToCrudAction('sendEmail')
            ->renderAsForm()
            ->asSuccessAction()
            ->askConfirmation('Envoyer cette candidature par email, avec le CV en pièce jointe ?', 'Envoyer')
            ->displayIf(fn (JobApplication $application) => $application->canBeEmailed());

        $markAsSent = Action::new('markAsSent', 'Marquer comme envoyée', 'fa fa-check')
            ->linkToCrudAction('markAsSent')
            ->renderAsForm()
            ->displayIf(fn (JobApplication $application) => $application->getStatus() === JobApplicationStatus::Draft);

        $sendEmails = Action::new('sendEmails', 'Envoyer par email', 'fa fa-envelope')
            ->linkToCrudAction('sendEmails')
            ->addCssClass('btn btn-success');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $sendEmail)
            ->add(Crud::PAGE_INDEX, $markAsSent)
            ->add(Crud::PAGE_INDEX, $followUp)
            ->add(Crud::PAGE_INDEX, $noResponse)
            ->add(Crud::PAGE_INDEX, $coverLetter)
            ->add(Crud::PAGE_DETAIL, $sendEmail)
            ->add(Crud::PAGE_DETAIL, $markAsSent)
            ->add(Crud::PAGE_DETAIL, $followUp)
            ->add(Crud::PAGE_DETAIL, $noResponse)
            ->add(Crud::PAGE_DETAIL, $coverLetter)
            ->addBatchAction($sendEmails);
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

    /** Aperçu du mail tel qu'il sera envoyé à l'entreprise. */
    #[AdminRoute('/{entityId}/email-preview', name: 'email_preview', options: ['methods' => ['GET']])]
    public function emailPreview(AdminContext $context, ApplicationMailer $mailer): Response
    {
        /** @var JobApplication $application */
        $application = $context->getEntity()->getInstance();

        return $this->render('admin/job_application/email_preview.html.twig', [
            'application' => $application,
            'subject' => $mailer->subject($application),
            'html' => $mailer->renderHtml($application),
        ]);
    }

    #[AdminRoute('/{entityId}/send-email', name: 'send_email', options: ['methods' => ['POST']])]
    public function sendEmail(AdminContext $context, EntityManagerInterface $em, ApplicationMailer $mailer): Response
    {
        /** @var JobApplication $application */
        $application = $context->getEntity()->getInstance();

        if (!$application->canBeEmailed()) {
            $this->addFlash('warning', sprintf('Candidature %s non envoyée : %s.', $application, $this->cannotEmailReason($application)));

            return $this->redirectBack($context);
        }

        try {
            $mailer->send($application);
            $em->flush();
        } catch (TransportExceptionInterface $e) {
            $this->addFlash('danger', sprintf('Échec de l\'envoi à %s : %s', $application->getCompany()->getContactEmail(), $e->getMessage()));

            return $this->redirectBack($context);
        }

        $this->addFlash('success', sprintf(
            'Candidature envoyée à %s (%s). Relance prévue le %s.',
            $application,
            $application->getCompany()->getContactEmail(),
            $application->getFollowUpAt()->format('d/m/Y'),
        ));

        return $this->redirectBack($context);
    }

    /** Envoi groupé des brouillons cochés : ceux qui ne sont pas prêts sont ignorés et listés. */
    #[AdminRoute('/send-emails', name: 'send_emails', options: ['methods' => ['POST']])]
    public function sendEmails(
        AdminContext $context,
        BatchActionDto $batchActionDto,
        EntityManagerInterface $em,
        JobApplicationRepository $repository,
        ApplicationMailer $mailer,
    ): Response {
        if (!$this->isCsrfTokenValid('ea-batch-action-sendEmails-'.JobApplication::class, $batchActionDto->getCsrfToken())) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
        if ($batchActionDto->getEntityFqcn() !== $context->getEntity()->getFqcn()) {
            throw new BadRequestHttpException();
        }

        $sent = 0;
        $skipped = [];
        $failed = [];
        foreach ($repository->findBy(['id' => $batchActionDto->getEntityIds()]) as $application) {
            if (!$application->canBeEmailed()) {
                $skipped[] = sprintf('%s (%s)', $application, $this->cannotEmailReason($application));
                continue;
            }

            try {
                $mailer->send($application);
                // Flush à chaque envoi : une erreur plus loin ne doit pas faire oublier ceux déjà partis
                $em->flush();
                $sent++;
            } catch (TransportExceptionInterface $e) {
                $failed[] = sprintf('%s (%s)', $application, $e->getMessage());
            }
        }

        if ($sent) {
            $this->addFlash('success', sprintf('%d candidature(s) envoyée(s) par email.', $sent));
        }
        if ($skipped) {
            $this->addFlash('warning', 'Ignorée(s) : '.implode(', ', $skipped).'.');
        }
        if ($failed) {
            $this->addFlash('danger', 'Échec d\'envoi : '.implode(', ', $failed).'.');
        }

        return $this->redirectBack($context);
    }

    /** Candidature déposée hors email (formulaire sur le site de l'entreprise...). */
    #[AdminRoute('/{entityId}/mark-as-sent', name: 'mark_as_sent', options: ['methods' => ['POST']])]
    public function markAsSent(AdminContext $context, EntityManagerInterface $em): Response
    {
        /** @var JobApplication $application */
        $application = $context->getEntity()->getInstance();
        if ($application->getStatus() !== JobApplicationStatus::Draft) {
            return $this->redirectBack($context);
        }
        $application->markAsSent();
        $em->flush();

        $this->addFlash('success', sprintf(
            'Candidature %s marquée comme envoyée. Relance prévue le %s.',
            $application,
            $application->getFollowUpAt()->format('d/m/Y'),
        ));

        return $this->redirectBack($context);
    }

    private function cannotEmailReason(JobApplication $application): string
    {
        return match (true) {
            $application->getStatus() !== JobApplicationStatus::Draft => 'déjà envoyée',
            trim((string) $application->getCompany()?->getContactEmail()) === '' => 'pas d\'email de contact',
            default => 'lettre vide',
        };
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
