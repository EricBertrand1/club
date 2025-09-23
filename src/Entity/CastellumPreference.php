<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;

#[ORM\Entity]
#[ORM\Table(name: 'castellum_preference')]
#[ORM\UniqueConstraint(name: 'uniq_castellum_pref_user', columns: ['user_id'])]
class CastellumPreference
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    // Codes catégories cochées, ex: ["400","600"]
    #[ORM\Column(type: 'json')]
    private array $categories = [];

    // IDs de sous-catégories cochées, ex: [12, 57, ...]
    #[ORM\Column(type: 'json')]
    private array $subcategories = [];

    // 'base' | 'avancé' | 'expert'
    #[ORM\Column(length: 10)]
    private string $level = 'base';

    // 10..100
    #[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
    private int $count = 20;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }

    public function getCategories(): array { return $this->categories; }
    public function setCategories(array $categories): self { $this->categories = array_values(array_unique($categories)); return $this; }

    public function getSubcategories(): array { return $this->subcategories; }
    public function setSubcategories(array $subcategories): self {
        $this->subcategories = array_values(array_unique(array_map('intval', $subcategories)));
        return $this;
    }

    public function getLevel(): string { return $this->level; }
    public function setLevel(string $level): self { $this->level = $level; return $this; }

    public function getCount(): int { return $this->count; }
    public function setCount(int $count): self { $this->count = $count; return $this; }

    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function touch(): self { $this->updatedAt = new \DateTimeImmutable(); return $this; }
}
