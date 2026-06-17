<?php

namespace App\Entity;

use App\Repository\SkillCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'skill_category')]
#[ORM\UniqueConstraint(name: 'name', columns: ['name'])]
#[ORM\Entity(repositoryClass: SkillCategoryRepository::class)]
class SkillCategory
{
    #[ORM\Column(name: "id")]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    private ?int $id = null;

    #[ORM\Column(name: "name", length: 100)]
    private ?string $name = null;

    #[ORM\Column(name: "sort_order", options: ["default" => 0])]
    private ?int $sortOrder = 0;

    #[ORM\OneToMany(targetEntity: Skill::class, mappedBy: 'category', fetch: 'EAGER')]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private Collection $skills;

    public function __construct()
    {
        $this->skills = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }

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

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function getSkills(): Collection
    {
        return $this->skills;
    }
}