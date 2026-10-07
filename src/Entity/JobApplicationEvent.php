<?php

namespace App\Entity;

use App\Enum\JobApplicationEventType;
use App\Enum\JobApplicationStatus;
use Doctrine\ORM\Mapping as ORM;

/**
 * Événement daté dans la vie d'une candidature (création, changement de statut, relance).
 */
#[ORM\Entity]
#[ORM\Index(columns: ['occurred_at'])]
class JobApplicationEvent
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private JobApplication $application;

    #[ORM\Column(length: 30, enumType: JobApplicationEventType::class)]
    private JobApplicationEventType $type;

    /** Statut de la candidature après l'événement. */
    #[ORM\Column(length: 30, enumType: JobApplicationStatus::class)]
    private JobApplicationStatus $status;

    #[ORM\Column]
    private \DateTimeImmutable $occurredAt;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $comment = null;

    public function __construct(
        JobApplication $application,
        JobApplicationEventType $type,
        JobApplicationStatus $status,
        ?\DateTimeImmutable $occurredAt = null,
        ?string $comment = null,
    ) {
        $this->application = $application;
        $this->type = $type;
        $this->status = $status;
        $this->occurredAt = $occurredAt ?? new \DateTimeImmutable();
        $this->comment = $comment;
    }

    /** Libellé lisible ; le statut est affiché à part (badge dans l'admin, texte dans les emails). */
    public function getLabel(): string
    {
        return match ($this->type) {
            JobApplicationEventType::Created => 'Candidature créée',
            JobApplicationEventType::StatusChanged => 'Changement de statut',
            JobApplicationEventType::FollowedUp => $this->comment ?? 'Relance',
        };
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApplication(): JobApplication
    {
        return $this->application;
    }

    public function getType(): JobApplicationEventType
    {
        return $this->type;
    }

    public function getStatus(): JobApplicationStatus
    {
        return $this->status;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }
}
