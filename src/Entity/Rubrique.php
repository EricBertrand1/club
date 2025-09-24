<?php
// src/Entity/Rubrique.php
namespace App\Entity;

use App\Entity\User;
use App\Entity\CoachingTheme;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Rubrique
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $label = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $img = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'rubriques')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    /** @var Collection<int, CoachingTheme> */
    #[ORM\OneToMany(mappedBy: 'rubrique', targetEntity: CoachingTheme::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $themes;

    public function __construct()
    {
        $this->themes = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getLabel(): string { return $this->label; }
    public function setLabel(string $label): self { $this->label = $label; return $this; }

    public function getImg(): ?string { return $this->img; }
    public function setImg(?string $img): self { $this->img = $img; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    /** @return Collection<int, CoachingTheme> */
    public function getThemes(): Collection { return $this->themes; }

    public function addTheme(CoachingTheme $theme): self
    {
        if (!$this->themes->contains($theme)) {
            $this->themes->add($theme);
            $theme->setRubrique($this);
        }
        return $this;
    }

    public function removeTheme(CoachingTheme $theme): self
    {
        if ($this->themes->removeElement($theme)) {
            if ($theme->getRubrique() === $this) {
                $theme->setRubrique(null);
            }
        }
        return $this;
    }
}
