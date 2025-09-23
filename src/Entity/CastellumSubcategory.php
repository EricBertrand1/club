<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_castellum_sub', columns: ['code', 'name'])]
class CastellumSubcategory
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    // Code catégorie: '000','100',...'900'
    #[ORM\Column(length: 3)]
    private string $code;

    // Nom de la sous-catégorie (ex: "Les bases de la radio")
    #[ORM\Column(length: 150)]
    private string $name;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function setCode(string $code): self { $this->code = $code; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
