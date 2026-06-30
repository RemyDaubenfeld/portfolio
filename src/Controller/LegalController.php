<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\LegalRepository;
use App\Repository\UserRepository;

final class LegalController extends AbstractController
{
    #[Route('/mentions-legales', name: 'app_mentions_legales')]
    public function mentions(LegalRepository $legalRepo, UserRepository $userRepo): Response
    {
        return $this->render('legal/mentions.html.twig', [
            'legal' => $legalRepo->findOneBy([]),
            'user'       => $userRepo->findOneBy([]),
        ]);
    }
}
