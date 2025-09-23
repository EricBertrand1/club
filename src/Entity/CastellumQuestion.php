<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class CastellumQuestion
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    // Redondance utile pour filtrer vite par grande catégorie
    #[ORM\Column(length: 3)]
    private string $categoryCode;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private CastellumSubcategory $subcategory;

    // 'base' | 'avancé' | 'expert'
    #[ORM\Column(length: 10)]
    private string $levelQuestion = 'base';

    // Sujet libre (facultatif mais pratique)
    #[ORM\Column(length: 120, nullable: true)]
    private ?string $subject = null;

    // Type (ex: QCM, Vrai/Faux, Court, Image, etc.)
    #[ORM\Column(length: 50)]
    private string $questionType = 'QCM';

    #[ORM\Column(type: 'text')]
    private string $questionText;

    // URL ou chemin local (laisser vide si pas d'image)
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $questionImage = null;

    #[ORM\Column(type: 'text')]
    private string $answerText;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $explanation = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    // Getters/Setters
    public function getId(): ?int { return $this->id; }
    public function getCategoryCode(): string { return $this->categoryCode; }
    public function setCategoryCode(string $c): self { $this->categoryCode = $c; return $this; }
    public function getSubcategory(): CastellumSubcategory { return $this->subcategory; }
    public function setSubcategory(CastellumSubcategory $s): self { $this->subcategory = $s; return $this; }
    public function getLevelQuestion(): string { return $this->levelQuestion; }
    public function setLevelQuestion(string $l): self { $this->levelQuestion = $l; return $this; }
    public function getSubject(): ?string { return $this->subject; }
    public function setSubject(?string $s): self { $this->subject = $s; return $this; }
    public function getQuestionType(): string { return $this->questionType; }
    public function setQuestionType(string $t): self { $this->questionType = $t; return $this; }
    public function getQuestionText(): string { return $this->questionText; }
    public function setQuestionText(string $t): self { $this->questionText = $t; return $this; }
    public function getQuestionImage(): ?string { return $this->questionImage; }
    public function setQuestionImage(?string $i): self { $this->questionImage = $i; return $this; }
    public function getAnswerText(): string { return $this->answerText; }
    public function setAnswerText(string $a): self { $this->answerText = $a; return $this; }
    public function getExplanation(): ?string { return $this->explanation; }
    public function setExplanation(?string $e): self { $this->explanation = $e; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function touch(): void { $this->updatedAt = new \DateTimeImmutable(); }
}
