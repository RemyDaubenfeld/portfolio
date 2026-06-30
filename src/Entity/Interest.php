<?php

namespace App\Entity;

use App\Repository\InterestRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'interest')]
#[ORM\Entity(repositoryClass: InterestRepository::class)]
class Interest
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(name: "id")]
    private ?int $id = null;

    #[ORM\Column(name: "name", length: 100)]
    private ?string $name = null;

    #[ORM\Column(name: "show_on_portfolio", type: "boolean", options: ["default" => true])]
    private bool $showOnPortfolio = true;

    #[ORM\Column(name: "show_on_cv", type: "boolean", options: ["default" => true])]
    private bool $showOnCv = true;

    #[ORM\Column(name: "sort_order", type: "integer", options: ["default" => 0])]
    private int $sortOrder = 0;

    public function getId(): ?int 
    { 
        return $this->id; 
    }

    public function getName(): ?string 
    { 
        return $this->name; 
    }
    
    public function setName(string $name): static 
    { 
        $this->name = $name; 
        return $this; 
    }

    public function isShowOnPortfolio(): bool 
    { 
        return $this->showOnPortfolio; 
    }
    
    public function setShowOnPortfolio(bool $showOnPortfolio): static 
    { 
        $this->showOnPortfolio = $showOnPortfolio; 
        return $this; 
    }

    public function isShowOnCv(): bool 
    { 
        return $this->showOnCv; 
    }
    
    public function setShowOnCv(bool $showOnCv): static 
    { 
        $this->showOnCv = $showOnCv; 
        return $this; 
    }

    public function getSortOrder(): int 
    { 
        return $this->sortOrder; 
    }
    
    public function setSortOrder(int $sortOrder): static 
    { 
        $this->sortOrder = $sortOrder; 
        return $this; 
    }

    public function __toString(): string 
    { 
        return $this->name ?? ''; 
    }
}