<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Attribute as Vich;
use Symfony\Component\HttpFoundation\File\File;

#[ORM\Table(name: 'project')]
#[ORM\UniqueConstraint(name: 'slug', columns: ['slug'])]
#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[UniqueEntity('slug', message: 'Ce slug est déjà utilisé par un autre projet.')]
#[Vich\Uploadable]
class Project
{
    #[ORM\Column(name: "id")]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    private ?int $id = null;

    #[ORM\Column(name: "title", length: 200)]
    private ?string $title = null;

    #[ORM\Column(name: "slug", length: 230)]
    private ?string $slug = null;

    #[ORM\Column(name: "description", type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: "in_progress", type: Types::BOOLEAN, options: ["default" => false])]
    private ?bool $inProgress = false;

    #[ORM\Column(name: "image", length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(name: "url", length: 255, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(name: "github_url", length: 255, nullable: true)]
    private ?string $githubUrl = null;

    #[ORM\Column(name: "features", type: Types::JSON, nullable: true)]
    private ?array $features = null;

    #[ORM\Column(name: "links", type: Types::JSON, nullable: true)]
    private ?array $links = null;

    #[ORM\Column(name: "sort_order", options: ["default" => 0])]
    private ?int $sortOrder = 0;

    #[ORM\Column(name: "created_at", type: Types::DATETIME_MUTABLE, nullable: true, options: ["default" => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    #[Assert\Image(maxSize: '5M')]
    #[Vich\UploadableField(mapping: 'media', fileNameProperty: 'image')]
    private ?File $imageFile = null;

    #[ORM\Column(name: "updated_at", type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToMany(targetEntity: Technology::class)]
    #[ORM\JoinTable(name: 'project_technology')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $technologies;

    public function __construct()
    {
        $this->technologies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function isInProgress(): bool
    {
        return $this->inProgress;
    }

    public function setInProgress(bool $inProgress): static
    {
        $this->inProgress = $inProgress;
    
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function getGithubUrl(): ?string
    {
        return $this->githubUrl;
    }

    public function setGithubUrl(?string $githubUrl): static
    {
        $this->githubUrl = $githubUrl;
        return $this;
    }

    public function getFeatures(): ?array
    {
        return $this->features;
    }

    public function setFeatures(?array $features): static
    {
        $this->features = $features;
        return $this;
    }

    public function getLinks(): ?array
    {
        return $this->links;
    }

    public function setLinks(?array $links): static
    {
        $this->links = $links;
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getTechnologies(): Collection
    {
        return $this->technologies;
    }

    public function addTechnology(Technology $technology): static
    {
        if (!$this->technologies->contains($technology)) {
            $this->technologies->add($technology);
        }
        
        return $this;
    }

    public function removeTechnology(Technology $technology): static
    {
        $this->technologies->removeElement($technology);
        
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

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile = null): static
    {
        $this->imageFile = $imageFile;

        if ($imageFile !== null) {
            $this->updatedAt = new \DateTime();
        }

        return $this;
    }

}