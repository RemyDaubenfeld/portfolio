<?php

namespace App\Entity;

use App\Repository\SettingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[UniqueEntity(fields: ['key'], message: 'Cette clé de paramètre est déjà utilisée.')]
#[ORM\Table(name: 'setting')]
#[ORM\Entity(repositoryClass: SettingRepository::class)]
class Setting
{
    #[ORM\Column(name: "setting_key", length: 100, options: ["quoted" => true])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "NONE")]
    private ?string $key = null;

    #[ORM\Column(name: "value", type: Types::TEXT)]
    private ?string $value = null;

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }
}
