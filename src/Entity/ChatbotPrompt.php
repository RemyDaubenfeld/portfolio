<?php

namespace App\Entity;

use App\Repository\ChatbotPromptRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChatbotPromptRepository::class)]
class ChatbotPrompt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $category = '';

    #[ORM\Column(length: 255)]
    private string $context = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $content = '';

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $isActive = true;

    public function getId(): ?int 
    { 
        return $this->id; 
    }

    public function getCategory(): string 
    { 
        return $this->category; 
    }
    
    public function setCategory(string $category): static 
    { 
        $this->category = $category; 
        
        return $this; 
    }

    public function getContext(): string 
    { 
        return $this->context; 
    }
    
    public function setContext(string $context): static 
    { 
        $this->context = $context; 
        
        return $this; 
    }

    public function getContent(): string 
    { 
        return $this->content; 
    }
    
    public function setContent(string $content): static 
    { 
        $this->content = $content; 
        
        return $this; 
    }

    public function getPosition(): int 
    { 
        return $this->position; 
    }
    
    public function setPosition(int $position): static 
    { 
        $this->position = $position; 
        
        return $this; 
    }

    public function isActive(): bool 
    { 
        return $this->isActive; 
    }
    
    public function setIsActive(bool $isActive): static 
    { 
        $this->isActive = $isActive; 
        
        return $this; 
    }
}