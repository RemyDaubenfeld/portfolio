<?php

namespace App\Entity;

use App\Repository\DepartmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Département ciblé par la recherche d'offres de n8n (France Travail).
 */
#[ORM\Entity(repositoryClass: DepartmentRepository::class)]
#[UniqueEntity(fields: ['code'], message: 'Ce département existe déjà.')]
class Department
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    /** Numéro du département : "57", "2A"... */
    #[ORM\Column(length: 3, unique: true)]
    private string $code;

    #[ORM\Column(length: 100)]
    private string $name;

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
        $this->code = strtoupper(trim($code));

        return $this;
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
