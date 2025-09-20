<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
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
}
