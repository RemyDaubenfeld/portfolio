<?php

namespace App\Entity;

use App\Repository\HeroTypingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'hero_typing')]
#[ORM\Entity(repositoryClass: HeroTypingRepository::class)]
class HeroTyping
{
    #[ORM\Column(name: "id")]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    private ?int $id = null;

    #[ORM\Column(name: "phrase", length: 200)]
    private ?string $phrase = null;

    #[ORM\Column(name: "sort_order", options: ["default" => 0])]
    private ?int $sortOrder = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhrase(): ?string
    {
        return $this->phrase;
    }

    public function setPhrase(string $phrase): static
    {
        $this->phrase = $phrase;

        return $this;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }
}
