<?php

namespace App\Controller\Admin;

use App\Controller\Admin\HeroStatCrudController;
use App\Controller\Admin\HeroTypingCrudController;
use App\Controller\Admin\LegalCrudController;
use App\Controller\Admin\ProjectCrudController;
use App\Controller\Admin\SettingCrudController;
use App\Controller\Admin\SkillCategoryCrudController;
use App\Controller\Admin\SkillCrudController;
use App\Controller\Admin\TechnologyCrudController;
use App\Controller\Admin\UserCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Portfolio Admin')
            ->setFaviconPath('favicon.ico')
            ->renderContentMaximized();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::section('Contenu');
        yield MenuItem::linkTo(UserCrudController::class, 'Profil', 'fa fa-user');
        yield MenuItem::linkTo(ProjectCrudController::class, 'Projets', 'fa fa-code');

        yield MenuItem::section('Skills');
        yield MenuItem::linkTo(SkillCategoryCrudController::class, 'Catégories', 'fa fa-folder');
        yield MenuItem::linkTo(SkillCrudController::class, 'Skills', 'fa fa-star');
        yield MenuItem::linkTo(TechnologyCrudController::class, 'Technologies', 'fa fa-wrench');

        yield MenuItem::section('Hero');
        yield MenuItem::linkTo(HeroStatCrudController::class, 'Stats', 'fa fa-chart-bar');
        yield MenuItem::linkTo(HeroTypingCrudController::class, 'Phrases (typing)', 'fa fa-keyboard');

        yield MenuItem::section('Divers');
        yield MenuItem::linkTo(SettingCrudController::class, 'Paramètres', 'fa fa-cog');
        yield MenuItem::linkTo(LegalCrudController::class, 'Mentions légales', 'fa fa-scale-balanced');
    }
}