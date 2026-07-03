<?php
namespace App\Entity;

use App\Repository\SeoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SeoRepository::class)]
class Seo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Balises meta
    #[ORM\Column(type: 'string', length: 160, nullable: true)]
    private ?string $metaTitle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $metaDescription = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $metaKeywords = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $robotsDirective = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $canonicalUrl = null;
    
    // Open Graph
    #[ORM\Column(type: 'string', length: 160, nullable: true)]
    private ?string $ogTitle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $ogDescription = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $ogImage = null;

    // JSON-LD / Schema.org
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $jsonLd = null;

    // Google Search Console
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $googleVerificationToken = null;

    // Fichiers SEO dynamiques
    #[ORM\Column(type: 'boolean')]
    private bool $sitemapEnabled = true;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $robotsTxt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $llmsTxt = null;

    // ----------------------------------------------------------------
    // Getters / Setters
    // ----------------------------------------------------------------

    public function getId(): ?int 
    { 
        return $this->id; 
    }

    public function getMetaTitle(): ?string 
    { 
        return $this->metaTitle; 
    }
    
    public function setMetaTitle(?string $v): static 
    { 
        $this->metaTitle = $v; 
        
        return $this; 
    }

    public function getMetaDescription(): ?string 
    { 
        return $this->metaDescription; 
    }
    
    public function setMetaDescription(?string $v): static 
    { 
        $this->metaDescription = $v; 
        
        return $this; 
    }

    public function getMetaKeywords(): ?string 
    { 
        return $this->metaKeywords; 
    }
    
    public function setMetaKeywords(?string $v): static 
    { 
        $this->metaKeywords = $v; 
        
        return $this; 
    }

    public function getRobotsDirective(): ?string 
    { 
        return $this->robotsDirective; 
    }
    
    public function setRobotsDirective(?string $v): static 
    { 
        $this->robotsDirective = $v; 
        
        return $this; 
    }

    public function getCanonicalUrl(): ?string 
    { 
        return $this->canonicalUrl; 
    }
    
    public function setCanonicalUrl(?string $v): static 
    { 
        $this->canonicalUrl = $v; 
        
        return $this; 
    }

    public function getOgTitle(): ?string 
    { 
        return $this->ogTitle; 
    }
    
    public function setOgTitle(?string $v): static 
    { 
        $this->ogTitle = $v; 
        
        return $this; 
    }

    public function getOgDescription(): ?string 
    { 
        return $this->ogDescription; 
    }
    
    public function setOgDescription(?string $v): static 
    { 
        $this->ogDescription = $v; 
        
        return $this; 
    }

    public function getOgImage(): ?string 
    { 
        return $this->ogImage; 
    }
    
    public function setOgImage(?string $v): static 
    { 
        $this->ogImage = $v; 
        
        return $this; 
    }

    public function getJsonLd(): ?string 
    { 
        return $this->jsonLd; 
    }
    
    public function setJsonLd(?string $v): static 
    { 
        $this->jsonLd = $v; 
        
        return $this; 
    }

    public function getGoogleVerificationToken(): ?string 
    { 
        return $this->googleVerificationToken; 
    }
    
    public function setGoogleVerificationToken(?string $v): static 
    { 
        $this->googleVerificationToken = $v; 
        
        return $this; 
    }

    public function isSitemapEnabled(): bool 
    { 
        return $this->sitemapEnabled; 
    }
    
    public function setSitemapEnabled(bool $v): static 
    { 
        $this->sitemapEnabled = $v; 
        
        return $this; 
    }

    public function getRobotsTxt(): ?string 
    { 
        return $this->robotsTxt; 
    }
    
    public function setRobotsTxt(?string $v): static 
    { 
        $this->robotsTxt = $v; 
        
        return $this; 
    }

    public function getLlmsTxt(): ?string 
    { 
        return $this->llmsTxt; 
    }
    
    public function setLlmsTxt(?string $v): static 
    { 
        $this->llmsTxt = $v; 
        
        return $this; 
    }
}