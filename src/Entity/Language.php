<?php

namespace App\Entity;

use App\Repository\LanguageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'language')]
#[ORM\Entity(repositoryClass: LanguageRepository::class)]
class Language
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(name: "id")]
    private ?int $id = null;

    #[ORM\Column(name: "name", length: 100)]
    private ?string $name = null;

    #[ORM\Column(name: "level", length: 50, nullable: true)]
    private ?string $level = null; // ex: "A2", "B1", "Natif"

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

    public function getLevel(): ?string 
    { 
        return $this->level; 
    }
    
    public function setLevel(?string $level): static 
    { 
        $this->level = $level; 
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
        return $this->name . ' (' . $this->level . ')'; 
    }
}