<?php

namespace App\Entity;

use App\Enum\JobOfferStatus;
use App\Repository\JobOfferRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobOfferRepository::class)]
#[ORM\HasLifecycleCallbacks]
class JobOffer
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(length: 255)]
    private string $company;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(length: 50)]
    private string $source;

    #[ORM\Column(length: 500)]
    private string $url;

    #[ORM\Column(length: 32, unique: true)]
    private string $hash;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(length: 30, enumType: JobOfferStatus::class)]
    private JobOfferStatus $applicationStatus = JobOfferStatus::ToReview;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // --- Getters / Setters ---

    public function getId(): ?int 
    { 
        return $this->id; 
    }

    public function getTitle(): string 
    { 
        return $this->title; 
    }
    
    public function setTitle(string $title): static 
    { 
        $this->title = $title; 
        
        return $this; 
    }

    public function getCompany(): string 
    { 
        return $this->company; 
    }
    
    public function setCompany(string $company): static 
    { 
        $this->company = $company; 
        
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

    public function getSource(): string 
    {   return $this->source; }
    
    public function setSource(string $source): static 
    { 
        $this->source = $source; 
        
        return $this; 
    }

    public function getUrl(): string 
    { 
        return $this->url; 
    }
    
    public function setUrl(string $url): static 
    { 
        $this->url = $url; $this->hash = md5($url); 
        
        return $this; 
    }

    public function getHash(): string 
    { 
        return $this->hash; 
    }

    public function getPublishedAt(): ?\DateTimeImmutable 
    { 
        return $this->publishedAt; 
    }
    
    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static 
    { 
        $this->publishedAt = $publishedAt; 
        
        return $this; 
    }

    public function getApplicationStatus(): JobOfferStatus 
    { 
        return $this->applicationStatus; 
    }
    
    public function setApplicationStatus(JobOfferStatus $status): static 
    { 
        $this->applicationStatus = $status; 
        
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
    
    public function getNotes(): ?string 
    { 
        return $this->notes; 
    }
    
    public function setNotes(?string $notes): static 
    { 
        $this->notes = $notes; 
        
        return $this; 
    }

    public function getCreatedAt(): \DateTimeImmutable 
    { 
        return $this->createdAt; 
    }
    
    public function setCreatedAt(\DateTimeImmutable $createdAt): static 
    { 
        $this->createdAt = $createdAt; 
        
        return $this; 
    }
}