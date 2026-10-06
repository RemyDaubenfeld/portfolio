<?php

namespace App\Entity;

use App\Enum\CompanySource;
use App\Enum\CompanyStatus;
use App\Repository\CompanyRepository;
use App\Service\JobSearch\CompanyNameNormalizer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
#[ORM\Index(columns: ['normalized_name'])]
#[ORM\HasLifecycleCallbacks]
class Company
{
    public const REGIONS = ['Alsace', 'Lorraine', 'Luxembourg', 'Autre'];

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255)]
    private string $normalizedName;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $region = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $workforce = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $remote = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $technologies = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $jobs = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $details = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactEmail = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $applicationUrl = null;

    #[ORM\Column(length: 30, enumType: CompanySource::class)]
    private CompanySource $source = CompanySource::Manual;

    #[ORM\Column(length: 30, enumType: CompanyStatus::class)]
    private CompanyStatus $status = CompanyStatus::ToContact;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /** @var Collection<int, JobOffer> */
    #[ORM\OneToMany(targetEntity: JobOffer::class, mappedBy: 'linkedCompany')]
    private Collection $jobOffers;

    /** @var Collection<int, JobApplication> */
    #[ORM\OneToMany(targetEntity: JobApplication::class, mappedBy: 'company')]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $applications;

    public function __construct()
    {
        $this->jobOffers = new ArrayCollection();
        $this->applications = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->city ? sprintf('%s (%s)', $this->name, $this->city) : $this->name;
    }

    /** Passe l'entreprise en "contactée" si elle n'avait pas encore été démarchée. */
    public function markAsContacted(): void
    {
        if (in_array($this->status, [CompanyStatus::ToQualify, CompanyStatus::ToContact], true)) {
            $this->status = CompanyStatus::Contacted;
        }
    }

    // --- Getters / Setters ---

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
        $this->normalizedName = CompanyNameNormalizer::normalize($name);

        return $this;
    }

    public function getNormalizedName(): string
    {
        return $this->normalizedName;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): static
    {
        $this->region = $region;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getWorkforce(): ?string
    {
        return $this->workforce;
    }

    public function setWorkforce(?string $workforce): static
    {
        $this->workforce = $workforce;

        return $this;
    }

    public function getRemote(): ?string
    {
        return $this->remote;
    }

    public function setRemote(?string $remote): static
    {
        $this->remote = $remote;

        return $this;
    }

    public function getTechnologies(): ?string
    {
        return $this->technologies;
    }

    public function setTechnologies(?string $technologies): static
    {
        $this->technologies = $technologies;

        return $this;
    }

    public function getJobs(): ?string
    {
        return $this->jobs;
    }

    public function setJobs(?string $jobs): static
    {
        $this->jobs = $jobs;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;

        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): static
    {
        $this->contactEmail = $contactEmail;

        return $this;
    }

    public function getApplicationUrl(): ?string
    {
        return $this->applicationUrl;
    }

    public function setApplicationUrl(?string $applicationUrl): static
    {
        $this->applicationUrl = $applicationUrl;

        return $this;
    }

    public function getSource(): CompanySource
    {
        return $this->source;
    }

    public function setSource(CompanySource $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getStatus(): CompanyStatus
    {
        return $this->status;
    }

    public function setStatus(CompanyStatus $status): static
    {
        $this->status = $status;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @return Collection<int, JobOffer> */
    public function getJobOffers(): Collection
    {
        return $this->jobOffers;
    }

    /** @return Collection<int, JobApplication> */
    public function getApplications(): Collection
    {
        return $this->applications;
    }
}
