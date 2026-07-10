<?php
namespace App\Controller\Admin;

use App\Entity\Seo;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SeoCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Seo::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            ChoiceField::new('pageKey', 'Page')
                ->setChoices([
                    'Global (par défaut)' => null,
                    'Accueil' => 'app_home',
                    'CV' => 'app_cv',
                    'Mentions légales' => 'app_mentions_legales',
                    'Confidentialité' => 'app_confidentialite',
                ])
                ->setHelp('Config appliquée à cette page précise. "Global" sert de secours et alimente le sitemap/robots.txt/llms.txt'),

            // Meta classiques
            TextField::new('metaTitle', 'Titre SEO')
                ->setHelp('Max 160 caractères'),
            TextareaField::new('metaDescription', 'Description')
                ->setHelp('Max 160 caractères, affiché dans les résultats Google'),
            TextField::new('metaKeywords', 'Mots-clés')
                ->setHelp('Quasi ignoré par Google aujourd\'hui, mais bon à remplir')
                ->hideOnIndex(),
            TextField::new('robotsDirective', 'Robots')
                ->setHelp(' balise <meta name="robots"> dans le <head> s\'applique page par page (Ex: index,follow ou noindex,nofollow)')
                ->hideOnIndex(),
            TextField::new('canonicalUrl', 'URL Canonique')
                ->setHelp('Évite le duplicate content en indiquant l\'URL "officielle"')
                ->hideOnIndex(),

            // Open Graph
            TextField::new('ogTitle', 'OG Title')
                ->setHelp('Titre affiché lors d\'un partage sur les réseaux sociaux (si vide, fallback sur metaTitle)')
                ->hideOnIndex(),
            TextareaField::new('ogDescription', 'OG Description')
                ->SetHelp('Description affichée lors d\un partage sur les réseaux sociaux (si vide, fallback sur metaDescription')
                ->hideOnIndex(),
            TextField::new('ogImage', 'OG Image URL')
                ->setHelp('URL absolue vers l\'image de partage (si vide, fallback vers url du portfolio)')
                ->hideOnIndex(),

            // JSON-LD
            TextareaField::new('jsonLd', 'JSON-LD Schema.org')
                ->setHelp('Données structurées au format JSON-LD, aide Google à comprendre qui je suis')
                ->hideOnIndex(),

            // GSC
            TextField::new('googleVerificationToken', 'Token Google Search Console')
                ->setHelp('Prouve à Google que je suis propriétaire du site')
                ->hideOnIndex(),

            // Fichiers dynamiques
            BooleanField::new('sitemapEnabled', 'Sitemap activé'),
            TextareaField::new('robotsTxt', 'Contenu robots.txt')
                ->setHelp('Laissez vide pour utiliser le contenu par défaut')
                ->hideOnIndex(),
            TextareaField::new('llmsTxt', 'Contenu llms.txt')
                ->setHelp('Fichier de contexte pour les LLMs (ChatGPT, Claude, Perplexity...)')
                ->hideOnIndex(),
        ];
    }
}