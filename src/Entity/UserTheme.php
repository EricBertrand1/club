<?php
namespace App\Entity;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\UniqueConstraint(columns: ['user_id','section','position'])]
class UserTheme
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    // "corps" | "esprit" | "intellect" | "habitudes"
    #[ORM\Column(length: 32)]
    private string $section;

    // 1..5
    #[ORM\Column]
    private int $position;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $label = null;

    // -3..3
    #[ORM\Column]
    private int $coefficient = 0;

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    public function getSection(): string { return $this->section; }
    public function setSection(string $section): self { $this->section = $section; return $this; }

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): self { $this->position = $position; return $this; }

    public function getLabel(): ?string { return $this->label; }
    public function setLabel(?string $label): self { $this->label = $label; return $this; }

    public function getCoefficient(): int { return $this->coefficient; }
    public function setCoefficient(int $coefficient): self { $this->coefficient = $coefficient; return $this; }
}
