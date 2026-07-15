<?php

namespace App\Entity;

use App\Repository\SearchCriteriaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SearchCriteriaRepository::class)]
#[ORM\HasLifecycleCallbacks] 
class SearchCriteria
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $keyWord;

    #[ORM\Column]
    private bool $active = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int 
    { 
        return $this->id; 
    }
    
    public function getKeyWord(): string 
    { 
        return $this->keyWord; 
    }

    public function setKeyWord(string $keyWord): static 
    { 
        $this->keyWord = $keyWord; 
        
        return $this; 
    }
    
    public function isActive(): bool 
    { 
        return $this->active; 
    }
    
    public function setActive(bool $active): static 
    { 
        $this->active = $active; 
        
        return $this; 
    }
    
    public function getCreatedAt(): \DateTimeImmutable 
    { 
        return $this->createdAt; 
    }
}