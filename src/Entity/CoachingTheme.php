<?php
// src/Entity/CoachingTheme.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\User;
use App\Entity\Rubrique;

#[ORM\Entity]
class CoachingTheme
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    // Intitulé du thème (modifiable par l’utilisateur ; peut être vide)
    #[ORM\Column(length: 150, nullable: true)]
    private ?string $label = null;

    // Coefficient entre -3 et +3
    #[ORM\Column(type: 'smallint', options: ['unsigned' => false])]
    #[Assert\Range(min: -3, max: 3)]
    private int $coefficient = 0;

    // Position d’affichage (1..5) dans la rubrique
    #[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
    #[Assert\Range(min: 1, max: 5)]
    private int $position = 1;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'themes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Rubrique::class, inversedBy: 'themes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Rubrique $rubrique = null;

    public function getId(): ?int { return $this->id; }

    public function getLabel(): ?string { return $this->label; }
    public function setLabel(?string $label): self { $this->label = $label; return $this; }

    public function getCoefficient(): int { return $this->coefficient; }
    public function setCoefficient(int $coefficient): self { $this->coefficient = $coefficient; return $this; }

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): self { $this->position = $position; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    public function getRubrique(): ?Rubrique { return $this->rubrique; }
    public function setRubrique(?Rubrique $rubrique): self { $this->rubrique = $rubrique; return $this; }
}
