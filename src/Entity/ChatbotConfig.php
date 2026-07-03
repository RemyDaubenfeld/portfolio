<?php

namespace App\Entity;

use App\Repository\ChatbotConfigRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: ChatbotConfigRepository::class)]
#[Vich\Uploadable]
class ChatbotConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name = "Stag'IA'ire";

    #[Vich\UploadableField(mapping: 'chatbot_icon', fileNameProperty: 'iconName')]
    private ?File $iconFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $iconName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $introMessage1 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $introMessage2 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rules = null;

    #[ORM\Column(length: 100)]
    private string $model = 'llama-3.3-70b-versatile';

    #[ORM\Column]
    private float $temperature = 0.7;

    #[ORM\Column]
    private int $maxTokens = 1024;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int 
    { 
        return $this->id; 
    }

    public function getName(): string 
    { 
        return $this->name; 
    }
    
    public function setName(string $name): static 
    { 
        $this->name = $name; 
        
        return $this; 
    }

    public function getIconFile(): ?File 
    { 
        return $this->iconFile; 
    }

    public function setIconFile(?File $iconFile = null): void
    {
        $this->iconFile = $iconFile;
        if (null !== $iconFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }
    
    public function getIconName(): ?string 
    { 
        return $this->iconName; 
    }
    
    public function setIconName(?string $iconName): static 
    { 
        $this->iconName = $iconName; 
        
        return $this; 
    }
    
    public function getIntroMessage1(): ?string 
    { 
        return $this->introMessage1; 
    }
    
    public function setIntroMessage1(?string $introMessage1): static 
    { 
        $this->introMessage1 = $introMessage1; 
        
        return $this; 
    }

    public function getIntroMessage2(): ?string 
    { 
        return $this->introMessage2; 
    }
    
    public function setIntroMessage2(?string $introMessage2): static 
    { 
        $this->introMessage2 = $introMessage2; 
        
        return $this; 
    }

    public function getRules(): ?string 
    { 
        return $this->rules; 
    }
    
    public function setRules(?string $rules): static 
    { 
        $this->rules = $rules; 
        
        return $this; }

    public function getModel(): string 
    { 
        return $this->model; 
    }
    
    public function setModel(string $model): static 
    { 
        $this->model = $model; 
        
        return $this; 
    }

    public function getTemperature(): float 
    { 
        return $this->temperature; 
    }
    
    public function setTemperature(float $temperature): static 
    { 
        $this->temperature = $temperature; 
        
        return $this; 
    }

    public function getMaxTokens(): int 
    { 
        return $this->maxTokens; 
    }
    
    public function setMaxTokens(int $maxTokens): static 
    { 
        $this->maxTokens = $maxTokens; 
        
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

    public function getUpdatedAt(): ?\DateTimeImmutable 
    { 
        return $this->updatedAt; 
    }
    
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static 
    { 
        $this->updatedAt = $updatedAt; return $this; 
    }
}