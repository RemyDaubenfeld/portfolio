<?php

namespace App\Entity;

use App\Repository\TechnologyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Table(name: 'technology')]
#[ORM\UniqueConstraint(name: 'name', columns: ['name'])]
#[ORM\Entity(repositoryClass: TechnologyRepository::class)]
#[UniqueEntity('name', message: 'Cette technologie existe déjà.')]
#[Vich\Uploadable]
class Technology
{
    #[ORM\Column(name: "id")]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    private ?int $id = null;

    #[ORM\Column(name: "name", length: 100)]
    private ?string $name = null;

    #[ORM\Column(name: "icon", length: 255, nullable: true)]
    private ?string $icon = null;

    #[Assert\Image(maxSize: '5M')]
    #[Vich\UploadableField(mapping: 'media', fileNameProperty: 'icon')]
    private ?File $iconFile = null;

    #[ORM\Column(name: "updated_at", type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

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

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIconFile(): ?File
    {
        return $this->iconFile;
    }

    public function setIconFile(?File $iconFile = null): static
    {
        $this->iconFile = $iconFile;

        if ($iconFile !== null) {
            $this->updatedAt = new \DateTime();
        }

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}