<?php

namespace App\Controller\JobSearch;

use App\Entity\Company;
use App\Entity\JobApplication;
use App\Enum\CompanySource;
use App\Enum\CompanyStatus;
use App\Enum\JobApplicationStatus;
use App\Enum\JobApplicationType;
use App\Repository\CompanyRepository;
use App\Repository\CoverLetterTemplateRepository;
use App\Service\JobSearch\CoverLetterGenerator;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
        $toContact = fn (Company $company) => in_array($company->getStatus(), [CompanyStatus::ToQualify, CompanyStatus::ToContact], true);

        $prepare = Action::new('prepareApplication', 'Préparer une candidature', 'fa fa-file-pen')
            ->linkToCrudAction('prepareApplication')
            ->asSuccessAction()
            ->displayIf(fn (Company $company) => !$company->hasOpenApplication());

        $spontaneous = Action::new('spontaneous', 'Candidature envoyée (hors email)', 'fa fa-paper-plane')
            ->linkToCrudAction('spontaneous')
            ->renderAsForm()
            ->displayIf($toContact);

        $prepareBatch = Action::new('prepareApplications', 'Préparer des candidatures spontanées', 'fa fa-file-pen')
            ->linkToCrudAction('prepareApplications')
            ->addCssClass('btn btn-success');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $prepare)
            ->add(Crud::PAGE_INDEX, $spontaneous)
            ->add(Crud::PAGE_DETAIL, $prepare)
            ->add(Crud::PAGE_DETAIL, $spontaneous)
            ->addBatchAction($prepareBatch);
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
        yield EmailField::new('contactEmail', 'Email de contact');
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

    /** Choix du modèle de lettre pour les entreprises cochées dans la liste. */
    #[AdminRoute('/prepare-applications', name: 'prepare_applications', options: ['methods' => ['POST']])]
    public function prepareApplications(
        AdminContext $context,
        BatchActionDto $batchActionDto,
        CompanyRepository $companyRepository,
        CoverLetterTemplateRepository $templateRepository,
    ): Response {
        if (!$this->isCsrfTokenValid('ea-batch-action-prepareApplications-'.Company::class, $batchActionDto->getCsrfToken())) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
        if ($batchActionDto->getEntityFqcn() !== $context->getEntity()->getFqcn()) {
            throw new BadRequestHttpException();
        }

        return $this->renderPreparePage($companyRepository->findBy(['id' => $batchActionDto->getEntityIds()], ['name' => 'ASC']), $templateRepository);
    }

    /** Même page pour une seule entreprise (candidature via leur site ou par email). */
    #[AdminRoute('/{entityId}/prepare-application', name: 'prepare_application', options: ['methods' => ['GET']])]
    public function prepareApplication(AdminContext $context, CoverLetterTemplateRepository $templateRepository): Response
    {
        return $this->renderPreparePage([$context->getEntity()->getInstance()], $templateRepository);
    }

    /**
     * Crée une candidature spontanée en brouillon, lettre pré-remplie, pour chaque entreprise
     * sans candidature en cours. Les brouillons sont ensuite relus puis envoyés depuis les candidatures.
     */
    #[AdminRoute('/create-applications', name: 'create_applications', options: ['methods' => ['POST']])]
    public function createApplications(
        Request $request,
        EntityManagerInterface $em,
        CompanyRepository $companyRepository,
        CoverLetterTemplateRepository $templateRepository,
        CoverLetterGenerator $generator,
        AdminUrlGeneratorInterface $adminUrlGenerator,
    ): Response {
        if (!$this->isCsrfTokenValid('prepare_applications', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $template = $templateRepository->find($request->request->getInt('template'));
        $companies = $companyRepository->findBy(['id' => array_map('intval', $request->request->all('ids'))]);
        if (!$template || !$companies) {
            throw new BadRequestHttpException('Modèle de lettre ou entreprises manquants.');
        }

        $created = [];
        $skipped = [];
        foreach ($companies as $company) {
            if ($company->hasOpenApplication()) {
                $skipped[] = $company->getName();
                continue;
            }

            $application = (new JobApplication())
                ->setCompany($company)
                ->setType(JobApplicationType::Spontaneous);
            $application->setCoverLetter($generator->fill($template, $application));
            $em->persist($application);
            $created[] = $application;
        }
        $em->flush();

        if ($created) {
            $this->addFlash('success', sprintf('%d brouillon(s) de candidature créé(s). Relisez les lettres puis envoyez-les.', count($created)));
        }
        if ($skipped) {
            $this->addFlash('warning', sprintf('Candidature déjà en cours, ignorée(s) : %s.', implode(', ', $skipped)));
        }

        $url = $adminUrlGenerator->setController(JobApplicationCrudController::class);
        if (count($created) === 1) {
            // Une seule entreprise : on va directement à la relecture de la lettre
            $url->setAction('coverLetter')->setEntityId($created[0]->getId());
        } else {
            $url->setAction(Action::INDEX)->set('filters', ['status' => ['comparison' => '=', 'value' => JobApplicationStatus::Draft->value]]);
        }

        return $this->redirect($url->generateUrl());
    }

    /** @param Company[] $companies */
    private function renderPreparePage(array $companies, CoverLetterTemplateRepository $templateRepository): Response
    {
        return $this->render('admin/company/prepare_applications.html.twig', [
            'companies' => $companies,
            'templates' => $templateRepository->findBy([], ['name' => 'ASC']),
        ]);
    }
}
