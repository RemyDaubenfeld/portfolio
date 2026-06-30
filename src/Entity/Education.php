<?php

namespace App\Entity;

use App\Repository\EducationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'education')]
#[ORM\Entity(repositoryClass: EducationRepository::class)]
class Education
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(name: "id")]
    private ?int $id = null;

    #[ORM\Column(name: "title", length: 255)]
    private ?string $title = null;

    #[ORM\Column(name: "institution", length: 255)]
    private ?string $institution = null;

    #[ORM\Column(name: "location", length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(name: "start_date", length: 50)]
    private ?string $startDate = null;

    #[ORM\Column(name: "end_date", length: 50, nullable: true)]
    private ?string $endDate = null;

    #[ORM\Column(name: "description", type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: "sort_order", type: Types::INTEGER, options: ["default" => 0])]
    private int $sortOrder = 0;

    public function getId(): ?int 
    { 
        return $this->id; 
    }

    public function getTitle(): ?string 
    { 
        return $this->title; 
    }
    
    public function setTitle(string $title): static 
    { 
        $this->title = $title; 
        return $this; 
    }

    public function getInstitution(): ?string 
    { 
        return $this->institution; 
    }
    
    public function setInstitution(string $institution): static 
    { 
        $this->institution = $institution; 
        return $this; 
    }

    public function getLocation(): ?string 
    { 
        return $this->location; 
    }
    
    public function setLocation(?string $location): static 
    { 
        $this->location = $location; 
        return $this; 
    }

    public function getStartDate(): ?string 
    { 
        return $this->startDate; 
    }
    
    public function setStartDate(string $startDate): static 
    { 
        $this->startDate = $startDate; 
        return $this; 
    }

    public function getEndDate(): ?string 
    { 
        return $this->endDate; 
    }
    
    public function setEndDate(?string $endDate): static 
    { 
        $this->endDate = $endDate; 
        return $this; 
    }

    public function getDescription(): ?string 
    { 
        return $this->description; 
    }
    
    public function setDescription(?string $description): static 
    { 
        $this->description = $description; 
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
        return $this->title . ' — ' . $this->institution; 
    }
}