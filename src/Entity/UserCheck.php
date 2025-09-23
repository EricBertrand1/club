<?php
namespace App\Entity;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\UniqueConstraint(columns: ['user_id','day','theme_id'])]
class UserCheck
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    // Date du jour (sans heure)
    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $day;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?UserTheme $theme = null;

    #[ORM\Column]
    private bool $checked = false;

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    public function getDay(): \DateTimeInterface { return $this->day; }
    public function setDay(\DateTimeInterface $day): self { $this->day = $day; return $this; }

    public function getTheme(): ?UserTheme { return $this->theme; }
    public function setTheme(?UserTheme $theme): self { $this->theme = $theme; return $this; }

    public function isChecked(): bool { return $this->checked; }
    public function setChecked(bool $checked): self { $this->checked = $checked; return $this; }
}
