<?php

namespace App\Entity;

use App\Repository\NegativeKeywordRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Mot-clé qui fait baisser la pertinence d'une offre (techno hors profil, alternance, poste senior...).
 * Contrairement aux mots-clés de recherche, il n'est pas envoyé à n8n.
 */
#[ORM\Entity(repositoryClass: NegativeKeywordRepository::class)]
#[UniqueEntity(fields: ['keyWord'], message: 'Ce mot-clé existe déjà.')]
class NegativeKeyword
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private string $keyWord;

    #[ORM\Column]
    private bool $active = true;

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
        $this->keyWord = trim($keyWord);

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
