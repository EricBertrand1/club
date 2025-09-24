<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\CoachingTheme;
use App\Entity\Rubrique;

#[ORM\Entity]
#[ORM\Table(name: 'user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct()
    {
        // ... ce que tu as déjà
        $this->themes = new ArrayCollection();
        $this->rubriques = new ArrayCollection();
    }

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: CoachingTheme::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $themes;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Rubrique::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $rubriques;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    private ?int $id = null;

    // ← login
    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $username = '';

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string')]
    private string $password = '';

    public function getId(): ?int { return $this->id; }

    public function getUserIdentifier(): string { return $this->username; }
    public function getUsername(): string { return $this->username; } // BC
    public function setUsername(string $u): self { $this->username = $u; return $this; }

    public function getRoles(): array {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER'; // rôle de base
        return array_unique($roles);
    }
    public function setRoles(array $roles): self { $this->roles = $roles; return $this; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $p): self { $this->password = $p; return $this; }

    public function eraseCredentials(): void {}

    /** @return Collection<int, CoachingTheme> */
    public function getThemes(): Collection
    {
        return $this->themes;
    }

    public function addTheme(CoachingTheme $theme): self
    {
        if (!$this->themes->contains($theme)) {
            $this->themes->add($theme);
            $theme->setUser($this);
        }
        return $this;
    }

    public function removeTheme(CoachingTheme $theme): self
    {
        if ($this->themes->removeElement($theme)) {
            if ($theme->getUser() === $this) {
                $theme->setUser(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, Rubrique> */
    public function getRubriques(): Collection
    {
        return $this->rubriques;
    }

    public function addRubrique(Rubrique $rubrique): self
    {
        if (!$this->rubriques->contains($rubrique)) {
            $this->rubriques->add($rubrique);
            $rubrique->setUser($this);
        }
        return $this;
    }

    public function removeRubrique(Rubrique $rubrique): self
    {
        if ($this->rubriques->removeElement($rubrique)) {
            if ($rubrique->getUser() === $this) {
                $rubrique->setUser(null);
            }
        }
        return $this;
    }

}
