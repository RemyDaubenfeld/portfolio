<?php

namespace App\Entity;

use App\Repository\HeroStatRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'hero_stat')]
#[ORM\Entity(repositoryClass: HeroStatRepository::class)]
class HeroStat
{
    #[ORM\Column(name: "id")]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    private ?int $id = null;

    #[ORM\Column(name: "value", length: 20)]
    private ?string $value = null;

    #[ORM\Column(name: "label", length: 100)]
    private ?string $label = null;

    #[ORM\Column(name: "sub", length: 200, nullable: true)]
    private ?string $sub = null;

    #[ORM\Column(name: "sort_order", options: ["default" => 0])]
    private ?int $sortOrder = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getSub(): ?string
    {
        return $this->sub;
    }

    public function setSub(?string $sub): static
    {
        $this->sub = $sub;

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
