<?php

namespace App\Controller\JobSearch;

use App\Entity\JobOffer;
use App\Entity\RomeCode;
use App\Entity\SearchCriteria;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/job-search', routeName: 'job_search_dashboard')]
class JobSearchDashboardController extends AbstractDashboardController
{   
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Recherche d\'emploi');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkTo(JobOfferCrudController::class, 'Offres d\'emploi', 'fa fa-briefcase');
        yield MenuItem::linkTo(SearchCriteriaCrudController::class, 'Mots-clés', 'fa fa-tags');
        yield MenuItem::linkTo(RomeCodeCrudController::class, 'Codes ROME', 'fa fa-sitemap');
    }
}