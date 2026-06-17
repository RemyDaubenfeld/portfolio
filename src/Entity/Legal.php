<?php

namespace App\Entity;

use App\Repository\LegalRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'legal')]
#[ORM\Entity(repositoryClass: LegalRepository::class)]
class Legal
{
    #[ORM\Column(name: "id")]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    private ?int $id = null;

    #[ORM\Column(name: "host_name", length: 255)]
    private ?string $hostName = null;

    #[ORM\Column(name: "host_address", type: Types::TEXT)]
    private ?string $hostAddress = null;

    #[ORM\Column(name: "host_website", length: 255, nullable: true)]
    private ?string $hostWebsite = null;

    #[ORM\Column(name: "host_phone", length: 20, nullable: true)]
    private ?string $hostPhone = null;

    #[ORM\Column(name: "updated_at", type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHostName(): ?string
    {
        return $this->hostName;
    }

    public function setHostName(string $hostName): static
    {
        $this->hostName = $hostName;
        return $this;
    }

    public function getHostAddress(): ?string
    {
        return $this->hostAddress;
    }

    public function setHostAddress(string $hostAddress): static
    {
        $this->hostAddress = $hostAddress;
        return $this;
    }

    public function getHostWebsite(): ?string
    {
        return $this->hostWebsite;
    }

    public function setHostWebsite(?string $hostWebsite): static
    {
        $this->hostWebsite = $hostWebsite;
        return $this;
    }

    public function getHostPhone(): ?string
    {
        return $this->hostPhone;
    }

    public function setHostPhone(?string $hostPhone): static
    {
        $this->hostPhone = $hostPhone;
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