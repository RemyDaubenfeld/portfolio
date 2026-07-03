<?php
namespace App\Controller;

use App\Repository\SeoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SeoFilesController extends AbstractController
{
    public function __construct(
        private readonly SeoRepository $seoRepository
    ) {}

    #[Route('/sitemap.xml', name: 'sitemap')]
    public function sitemap(): Response
    {
        $seo = $this->seoRepository->findOneBy([]);

        if (!$seo?->isSitemapEnabled()) {
            throw $this->createNotFoundException();
        }

        return new Response(
            $this->renderView('seo/sitemap.xml.twig'),
            200,
            ['Content-Type' => 'application/xml']
        );
    }

    #[Route('/robots.txt', name: 'robots')]
    public function robots(): Response
    {
        $seo = $this->seoRepository->findOneBy([]);
        $content = $seo?->getRobotsTxt()
            ?? "User-agent: *\nAllow: /\nSitemap: https://remy-daubenfeld.fr/sitemap.xml";

        return new Response($content, 200, ['Content-Type' => 'text/plain']);
    }

    #[Route('/llms.txt', name: 'llms')]
    public function llms(): Response
    {
        $seo = $this->seoRepository->findOneBy([]);
        $content = $seo?->getLlmsTxt() ?? '';

        return new Response($content, 200, ['Content-Type' => 'text/plain']);
    }
}