<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class DirectoryEntry
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    // Catégorie (Automobile, Alimentaire, Matériaux, Outils, ...)
    #[ORM\Column(length: 60)]
    #[Assert\NotBlank]
    private string $category;

    // Type (Web, Localisé)
    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    private string $type;

    // Désignation (raison sociale / organisation)
    #[ORM\Column(length: 150, nullable: true)]
    private ?string $designation = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 12, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url]
    private ?string $website = null;

    // ✨ Nouveau : Description libre
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    // Note / 5
    #[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
    #[Assert\Range(min: 0, max: 5)]
    private int $rating = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // --- getters/setters ---
    public function getId(): ?int { return $this->id; }
    public function getCategory(): string { return $this->category; }
    public function setCategory(string $category): self { $this->category = $category; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }

    public function getDesignation(): ?string { return $this->designation; }
    public function setDesignation(?string $designation): self { $this->designation = $designation; return $this; }

    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(?string $lastName): self { $this->lastName = $lastName; return $this; }

    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(?string $firstName): self { $this->firstName = $firstName; return $this; }

    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $address): self { $this->address = $address; return $this; }

    public function getPostalCode(): ?string { return $this->postalCode; }
    public function setPostalCode(?string $postalCode): self { $this->postalCode = $postalCode; return $this; }

    public function getCity(): ?string { return $this->city; }
    public function setCity(?string $city): self { $this->city = $city; return $this; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): self { $this->phone = $phone; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): self { $this->email = $email; return $this; }

    public function getWebsite(): ?string { return $this->website; }
    public function setWebsite(?string $website): self { $this->website = $website; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    public function getRating(): int { return $this->rating; }
    public function setRating(int $rating): self { $this->rating = $rating; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }
}
