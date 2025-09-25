<?php

namespace App\Entity;

use App\Entity\CastellumSubcategory;
use App\Repository\FormationPageRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: FormationPageRepository::class)]
#[ORM\Table(name: 'formation_page')]
#[ORM\UniqueConstraint(name: 'uniq_formation_sub', columns: ['subcategory_id'])]
class FormationPage
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    // 1 page par sous-catégorie Castellum
    #[ORM\ManyToOne(targetEntity: CastellumSubcategory::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?CastellumSubcategory $subcategory = null;

    // JSON de blocs (voir format dans le template d'édition)
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contentJson = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
        $this->contentJson = json_encode([]);
    }

    public function getId(): ?int { return $this->id; }

    public function getSubcategory(): ?CastellumSubcategory { return $this->subcategory; }
    public function setSubcategory(CastellumSubcategory $s): self { $this->subcategory = $s; return $this; }

    public function getContentJson(): ?string { return $this->contentJson; }
    public function setContentJson(?string $json): self { $this->contentJson = $json; $this->touch(); return $this; }

    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function touch(): void { $this->updatedAt = new \DateTimeImmutable(); }
}
