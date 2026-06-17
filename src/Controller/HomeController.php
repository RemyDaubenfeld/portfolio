<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserRepository;
use App\Repository\HeroStatRepository;
use App\Repository\SkillCategoryRepository;
use App\Repository\ProjectRepository;
use App\Repository\HeroTypingRepository;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        UserRepository $userRepo, 
        HeroStatRepository $heroStatRepo, 
        SkillCategoryRepository $skillCatRepo, 
        ProjectRepository $projectRepo,
        HeroTypingRepository $heroTypingRepo
        ): Response
    {
        return $this->render('home/index.html.twig', [
            'user'       => $userRepo->findOneBy([]),
            'hero_stats' => $heroStatRepo->findBy([], ['sortOrder' => 'ASC']),
            'skills_cats'  => $skillCatRepo->findBy([], ['sortOrder' => 'ASC']),
            'projects'   => $projectRepo->findBy([], ['sortOrder' => 'ASC']),
            'hero_typing'  => $heroTypingRepo->findBy([], ['sortOrder' => 'ASC']),
        ]);
    }
}
