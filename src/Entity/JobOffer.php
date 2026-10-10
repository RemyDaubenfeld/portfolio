<?php

namespace App\Entity;

use App\Enum\JobOfferStatus;
use App\Repository\JobOfferRepository;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobOfferRepository::class)]
#[ORM\HasLifecycleCallbacks]
class JobOffer
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $company = null;

    #[ORM\ManyToOne(inversedBy: 'jobOffers')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Company $linkedCompany = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(length: 50)]
    private string $source;

    #[ORM\Column(type: 'text')]
    private string $url;

    #[ORM\Column(length: 32, unique: true)]
    private string $hash;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $externalId = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(length: 30, enumType: JobOfferStatus::class)]
    private JobOfferStatus $applicationStatus = JobOfferStatus::ToReview;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    /** Note de pertinence de 0 à 100, null tant qu'elle n'a pas été calculée. */
    #[ORM\Column(nullable: true)]
    private ?int $relevanceScore = null;

    /** @var list<array{label: string, points: int}>|null détail du calcul de la note */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $relevanceDetails = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    /** Champs calculés automatiquement : les modifier ne compte pas comme une modification de l'offre. */
    private const COMPUTED_FIELDS = ['relevanceScore', 'relevanceDetails', 'linkedCompany'];

    #[ORM\PreUpdate]
    public function onPreUpdate(PreUpdateEventArgs $args): void
    {
        if (array_diff(array_keys($args->getEntityChangeSet()), self::COMPUTED_FIELDS)) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    // --- Getters / Setters ---

    public function getId(): ?int 
    { 
        return $this->id; 
    }

    public function getTitle(): string 
    { 
        return $this->title; 
    }
    
    public function setTitle(string $title): static 
    { 
        $this->title = $title; 
        
        return $this; 
    }

    public function getCompany(): ?string 
    { 
        return $this->company; 
    }
    
    public function setCompany(?string $company): static 
    { 
        $this->company = $company; 
        
        return $this; 
    }

    public function getLinkedCompany(): ?Company
    {
        return $this->linkedCompany;
    }

    public function setLinkedCompany(?Company $linkedCompany): static
    {
        $this->linkedCompany = $linkedCompany;

        return $this;
    }

    public function getLocation(): ?string 
    { 
        return $this->location; 
    }
    
    public function setLocation(?string $location): static 
    { 
        $this->location = $location; 
        
        return $this; 
    }

    public function getSource(): string 
    {   return $this->source; }
    
    public function setSource(string $source): static 
    { 
        $this->source = $source; 
        
        return $this; 
    }

    public function getUrl(): string 
    { 
        return $this->url; 
    }
    
    public function setUrl(string $url): static 
    { 
        $this->url = $url; $this->hash = md5($url); 
        
        return $this; 
    }

    public function getHash(): string 
    { 
        return $this->hash; 
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }


    public function setExternalId(?string $externalId): static
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable 
    { 
        return $this->publishedAt; 
    }
    
    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static 
    { 
        $this->publishedAt = $publishedAt; 
        
        return $this; 
    }

    public function getApplicationStatus(): JobOfferStatus 
    { 
        return $this->applicationStatus; 
    }
    
    public function setApplicationStatus(JobOfferStatus $status): static 
    { 
        $this->applicationStatus = $status; 
        
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
    
    public function getNotes(): ?string 
    { 
        return $this->notes; 
    }
    
    public function setNotes(?string $notes): static 
    { 
        $this->notes = $notes; 
        
        return $this; 
    }

    public function getCreatedAt(): \DateTimeImmutable 
    { 
        return $this->createdAt; 
    }
    
    public function setCreatedAt(\DateTimeImmutable $createdAt): static 
    { 
        $this->createdAt = $createdAt; 
        
        return $this; 
    }

    public function getRelevanceScore(): ?int
    {
        return $this->relevanceScore;
    }

    /** @return list<array{label: string, points: int}> */
    public function getRelevanceDetails(): array
    {
        return $this->relevanceDetails ?? [];
    }

    /** @param list<array{label: string, points: int}> $details */
    public function setRelevance(int $score, array $details): static
    {
        $this->relevanceScore = $score;
        $this->relevanceDetails = $details;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function __toString(): string
    {
        return $this->company ? sprintf('%s — %s', $this->title, $this->company) : $this->title;
    }
}
