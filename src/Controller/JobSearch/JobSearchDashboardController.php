<?php

namespace App\Controller\JobSearch;

use App\Enum\CompanyStatus;
use App\Enum\JobApplicationStatus;
use App\Enum\JobOfferStatus;
use App\Repository\CompanyRepository;
use App\Repository\JobApplicationRepository;
use App\Repository\JobOfferRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/job-search', routeName: 'job_search_dashboard')]
class JobSearchDashboardController extends AbstractDashboardController
{
    public function __construct(
        private JobOfferRepository $offerRepository,
        private JobApplicationRepository $applicationRepository,
        private CompanyRepository $companyRepository,
    ) {
    }

    public function index(): Response
    {
        $applicationStats = $this->applicationRepository->getStats();
        $companyCounts = $this->companyRepository->countByStatus();

        return $this->render('admin/job_search_dashboard.html.twig', [
            'applications' => $applicationStats,
            'responseRate' => $applicationStats['sent'] > 0
                ? round($applicationStats['answered'] / $applicationStats['sent'] * 100)
                : null,
            'offersToReview' => $this->offerRepository->countByApplicationStatus(JobOfferStatus::ToReview),
            'offersLast7Days' => $this->offerRepository->countCreatedSince(new \DateTimeImmutable('-7 days')),
            'companiesTotal' => array_sum($companyCounts),
            'companiesByStatus' => array_map(fn (CompanyStatus $status) => [
                'status' => $status,
                'total' => $companyCounts[$status->value],
            ], CompanyStatus::cases()),
            'companiesByRegion' => $this->companyRepository->countByRegion(),
            'followUpsDue' => $this->applicationRepository->findFollowUpsDue(),
            'awaitingApplications' => $this->applicationRepository->findAwaitingResponse(),
            'interviews' => $this->applicationRepository->findByStatus(JobApplicationStatus::Interview),
            'latestOffers' => $this->offerRepository->findLatestToReview(8),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Recherche d\'emploi')
            ->renderContentMaximized();
    }

    public function configureCrud(): Crud
    {
        return Crud::new()
            ->setDateFormat('dd/MM/yyyy')
            ->setDateTimeFormat('dd/MM/yyyy HH:mm');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-gauge');
        yield MenuItem::section('Suivi');
        yield MenuItem::linkTo(JobOfferCrudController::class, 'Offres d\'emploi', 'fa fa-briefcase');
        yield MenuItem::linkTo(JobApplicationCrudController::class, 'Candidatures', 'fa fa-paper-plane');
        yield MenuItem::linkTo(CompanyCrudController::class, 'Entreprises', 'fa fa-building');
        yield MenuItem::linkTo(CoverLetterTemplateCrudController::class, 'Modèles de lettre', 'fa fa-file-lines');
        yield MenuItem::section('Configuration n8n');
        yield MenuItem::linkTo(SearchCriteriaCrudController::class, 'Mots-clés', 'fa fa-tags');
        yield MenuItem::linkTo(RomeCodeCrudController::class, 'Codes ROME', 'fa fa-sitemap');
    }
}
