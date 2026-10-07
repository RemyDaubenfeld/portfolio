<?php

namespace App\Entity;

use App\Repository\CoverLetterTemplateRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Corps de lettre de motivation réutilisable (ESN, éditeur, startup...), avec des variables
 * remplacées à partir de la candidature : {entreprise}, {poste}, {ville}.
 */
#[ORM\Entity(repositoryClass: CoverLetterTemplateRepository::class)]
class CoverLetterTemplate
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(type: 'text')]
    private string $body;

    public function __toString(): string
    {
        return $this->name;
    }

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

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): static
    {
        $this->body = $body;

        return $this;
    }
}
