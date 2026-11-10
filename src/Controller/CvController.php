<?php

namespace App\Controller;

use App\Service\CvPdfGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CvController extends AbstractController
{
    #[Route('/cv', name: 'app_cv')]
    public function index(CvPdfGenerator $cv): Response
    {
        return $this->render('cv/index.html.twig', $cv->getData());
    }

    #[Route('/cv/download', name: 'app_cv_download')]
    public function download(CvPdfGenerator $cv): Response
    {
        return new Response(
            $cv->render(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $cv->filename() . '"',
            ]
        );
    }
}
