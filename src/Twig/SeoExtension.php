<?php
namespace App\Twig;

use App\Entity\Seo;
use App\Repository\SeoRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class SeoExtension extends AbstractExtension implements GlobalsInterface
{
    // Routes des pages publiques éligibles à un canonical auto-généré (doit rester aligné avec les choix "pageKey" de SeoCrudController)
    private const PAGE_ROUTES = ['app_home', 'app_cv', 'app_mentions_legales', 'app_confidentialite'];

    public function __construct(
        private readonly SeoRepository $seoRepository,
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function getGlobals(): array
    {
        $routeName = $this->requestStack->getCurrentRequest()?->attributes->get('_route');

        $seo = ($routeName ? $this->seoRepository->findByPageKey($routeName) : null)
            ?? $this->seoRepository->findByPageKey(null)
            ?? new Seo();

        $canonicalUrl = $seo->getCanonicalUrl() ?? $this->generateCanonicalUrl($routeName);

        return [
            'seo_config' => $seo,
            'seo_canonical_url' => $canonicalUrl,
        ];
    }

    private function generateCanonicalUrl(?string $routeName): ?string
    {
        if (!$routeName || !in_array($routeName, self::PAGE_ROUTES, true)) {
            return null;
        }

        return $this->urlGenerator->generate($routeName, [], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
