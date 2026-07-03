<?php
namespace App\Twig;

use App\Entity\Seo;
use App\Repository\SeoRepository;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class SeoExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly SeoRepository $seoRepository
    ) {}

    public function getGlobals(): array
    {
        return [
            'seo_config' => $this->seoRepository->findOneBy([]) ?? new Seo(),
        ];
    }
}