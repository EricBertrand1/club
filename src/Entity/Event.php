<?php
// src/Entity/Event.php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $heure = null;

    #[ORM\Column(length: 50)]
    private ?string $texteCourt = null;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $texteLong = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    public function getId(): ?int { return $this->id; }
    public function getDate(): ?\DateTime { return $this->date; }
    public function setDate(\DateTime $date): static { $this->date = $date; return $this; }

    public function getHeure(): ?\DateTime { return $this->heure; }
    public function setHeure(\DateTime $heure): static { $this->heure = $heure; return $this; }

    public function getTexteCourt(): ?string { return $this->texteCourt; }
    public function setTexteCourt(string $texteCourt): static { $this->texteCourt = $texteCourt; return $this; }

    public function getTexteLong(): ?string { return $this->texteLong; }
    public function setTexteLong(?string $texteLong): static { $this->texteLong = $texteLong; return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }
}
