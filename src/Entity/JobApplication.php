<?php

namespace App\Entity;

use App\Enum\JobApplicationEventType;
use App\Enum\JobApplicationStatus;
use App\Enum\JobApplicationType;
use App\Repository\JobApplicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobApplicationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class JobApplication
{
    /** Délai par défaut avant de relancer une entreprise silencieuse. */
    public const FOLLOW_UP_DELAY = '+10 days';

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'applications')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Company $company = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?JobOffer $offer = null;

    #[ORM\Column(length: 30, enumType: JobApplicationType::class)]
    private JobApplicationType $type = JobApplicationType::Spontaneous;

    #[ORM\Column(length: 30, enumType: JobApplicationStatus::class)]
    private JobApplicationStatus $status = JobApplicationStatus::Draft;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    /** Date à laquelle la prochaine relance est prévue. */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $followUpAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastFollowedUpAt = null;

    #[ORM\Column]
    private int $followUpCount = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cvVersion = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    /** Corps de la lettre de motivation (paragraphes entre la formule d'appel et la formule de politesse). */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $coverLetter = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /** @var Collection<int, JobApplicationEvent> */
    #[ORM\OneToMany(targetEntity: JobApplicationEvent::class, mappedBy: 'application', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['occurredAt' => 'ASC', 'id' => 'ASC'])]
    private Collection $events;

    public function __construct()
    {
        $this->events = new ArrayCollection();
    }

    /** Crée une candidature envoyée à partir d'une offre. */
    public static function fromOffer(JobOffer $offer): self
    {
        $application = (new self())
            ->setType(JobApplicationType::JobPosting)
            ->setOffer($offer)
            ->setCompany($offer->getLinkedCompany());
        $application->setStatus(JobApplicationStatus::Sent);

        return $application;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->applyStatusDefaults();

        if ($this->status !== JobApplicationStatus::Draft) {
            $this->company?->markAsContacted();
        }

        $this->recordCreation();
    }

    /**
     * Historique initial : la création est datée du jour d'envoi. Si la candidature est saisie
     * directement à un statut plus avancé (refus, entretien...), ce statut est ajouté ensuite.
     */
    private function recordCreation(): void
    {
        if ($this->status === JobApplicationStatus::Draft) {
            $this->addEvent(JobApplicationEventType::Created, JobApplicationStatus::Draft, $this->createdAt);

            return;
        }

        $this->addEvent(JobApplicationEventType::Created, JobApplicationStatus::Sent, $this->sentAt);
        if ($this->status !== JobApplicationStatus::Sent) {
            $this->addEvent(JobApplicationEventType::StatusChanged, $this->status);
        }
    }

    private function addEvent(
        JobApplicationEventType $type,
        JobApplicationStatus $status,
        ?\DateTimeImmutable $occurredAt = null,
        ?string $comment = null,
    ): void {
        $this->events->add(new JobApplicationEvent($this, $type, $status, $occurredAt, $comment));
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
        $this->applyStatusDefaults();
    }

    /** Renseigne la date d'envoi et planifie la relance si elles n'ont pas été saisies. */
    private function applyStatusDefaults(): void
    {
        if ($this->status !== JobApplicationStatus::Draft && $this->sentAt === null) {
            $this->sentAt = new \DateTimeImmutable();
        }

        if ($this->status->isAwaitingResponse() && $this->followUpAt === null) {
            $this->followUpAt = ($this->lastFollowedUpAt ?? $this->sentAt)->modify(self::FOLLOW_UP_DELAY);
        }

        if (!$this->status->isAwaitingResponse()) {
            $this->followUpAt = null;
        }
    }

    /** Enregistre une relance et planifie la suivante. */
    public function followUp(): void
    {
        $now = new \DateTimeImmutable();
        $this->lastFollowedUpAt = $now;
        $this->followUpCount++;
        $this->followUpAt = $now->modify(self::FOLLOW_UP_DELAY);
        $this->applyStatus(JobApplicationStatus::FollowedUp);
        $this->addEvent(JobApplicationEventType::FollowedUp, $this->status, $now, sprintf('Relance n°%d', $this->followUpCount));
    }

    public function isFollowUpDue(): bool
    {
        return $this->status->isAwaitingResponse()
            && $this->followUpAt !== null
            && $this->followUpAt <= new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        $target = $this->company?->getName() ?? $this->offer?->getCompany() ?? 'Entreprise inconnue';

        return $this->offer ? sprintf('%s — %s', $target, $this->offer->getTitle()) : $target;
    }

    // --- Getters / Setters ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getOffer(): ?JobOffer
    {
        return $this->offer;
    }

    public function setOffer(?JobOffer $offer): static
    {
        $this->offer = $offer;

        return $this;
    }

    public function getType(): JobApplicationType
    {
        return $this->type;
    }

    public function setType(JobApplicationType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getStatus(): JobApplicationStatus
    {
        return $this->status;
    }

    /** Les changements de statut d'une candidature déjà enregistrée sont ajoutés à son historique. */
    public function setStatus(JobApplicationStatus $status): static
    {
        if ($status !== $this->status && $this->id !== null) {
            $this->addEvent(JobApplicationEventType::StatusChanged, $status);
        }

        $this->applyStatus($status);

        return $this;
    }

    /** Le statut est répercuté sur l'offre liée pour garder les deux vues cohérentes. */
    private function applyStatus(JobApplicationStatus $status): void
    {
        $this->status = $status;

        if ($offerStatus = $status->toJobOfferStatus()) {
            $this->offer?->setApplicationStatus($offerStatus);
        }
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getFollowUpAt(): ?\DateTimeImmutable
    {
        return $this->followUpAt;
    }

    public function setFollowUpAt(?\DateTimeImmutable $followUpAt): static
    {
        $this->followUpAt = $followUpAt;

        return $this;
    }

    public function getLastFollowedUpAt(): ?\DateTimeImmutable
    {
        return $this->lastFollowedUpAt;
    }

    public function setLastFollowedUpAt(?\DateTimeImmutable $lastFollowedUpAt): static
    {
        $this->lastFollowedUpAt = $lastFollowedUpAt;

        return $this;
    }

    public function getFollowUpCount(): int
    {
        return $this->followUpCount;
    }

    public function setFollowUpCount(int $followUpCount): static
    {
        $this->followUpCount = $followUpCount;

        return $this;
    }

    public function getCvVersion(): ?string
    {
        return $this->cvVersion;
    }

    public function setCvVersion(?string $cvVersion): static
    {
        $this->cvVersion = $cvVersion;

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

    public function getCoverLetter(): ?string
    {
        return $this->coverLetter;
    }

    public function setCoverLetter(?string $coverLetter): static
    {
        $this->coverLetter = $coverLetter;

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

    /** @return Collection<int, JobApplicationEvent> */
    public function getEvents(): Collection
    {
        return $this->events;
    }
}
