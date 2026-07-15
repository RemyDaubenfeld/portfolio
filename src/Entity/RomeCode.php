<?php

namespace App\Entity;

use App\Repository\RomeCodeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RomeCodeRepository::class)]
#[ORM\HasLifecycleCallbacks] 
class RomeCode
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private string $code;

    #[ORM\Column(length: 255)]
    private string $wording;

    #[ORM\Column]
    private bool $active = true;

    public function getId(): ?int 
    { 
        return $this->id; 
    }
    
    public function getCode(): string 
    { 
        return $this->code; 
    }
    
    public function setCode(string $code): static 
    { 
        $this->code = $code; 
        
        return $this; 
    }
    
    public function getWording(): string 
    { 
        return $this->wording; 
    }
    
    public function setWording(string $wording): static 
    { 
        $this->wording = $wording; 
        
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
}