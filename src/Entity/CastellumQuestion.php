<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks] // 👈 important
class CastellumQuestion
{
    // --- Timestamps ---
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    // --- Relations / rattachements ---
    #[ORM\ManyToOne(targetEntity: CastellumSubcategory::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?CastellumSubcategory $subcategory = null;

    // Code catégorie (000..900)
    #[ORM\Column(length: 3)]
    private string $categoryCode = '000';

    // Niveau (base/avancé/expert)
    #[ORM\Column(length: 16)]
    private string $levelQuestion = 'base';

    // Type / Sujet
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $questionType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subject = null;

    // Énoncé + réponse + explication
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $questionText = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $answerText = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $explanation = null;

    // Image principale (chemin public ou URL)
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $questionImage = null;

    // --- Nouveaux champs ---

    // Durée max (s)
    #[ORM\Column(type: 'smallint', nullable: true, options: ['unsigned' => true])]
    private ?int $durationSeconds = null;

    // Formation (chapitre / paragraphe)
    #[ORM\Column(length: 120, nullable: true)]
    private ?string $formationChapter = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $formationParagraph = null;

    // Son (chemin public)
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $questionAudio = null;

    // Coordonnées
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $coordX = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $coordY = null;

    // QCM Textes 1..10
    #[ORM\Column(length: 255, nullable: true)] private ?string $qcmText1 = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $qcmText2 = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $qcmText3 = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $qcmText4 = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $qcmText5 = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $qcmText6 = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $qcmText7 = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $qcmText8 = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $qcmText9 = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $qcmText10 = null;

    // QCM Images 1..10 (chemins publics)
    #[ORM\Column(length: 255, nullable: true)] private ?string $qcmImage1 = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $qcmImage2 = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $qcmImage3 = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $qcmImage4 = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $qcmImage5 = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $qcmImage6 = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $qcmImage7 = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $qcmImage8 = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $qcmImage9 = null;

    // Validations
    #[ORM\Column(length: 180, nullable: true)]
    private ?string $validationSignataire1 = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $validationSignataire2 = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $validationSignataire3 = null;

    // --- Getters / Setters ---

    public function getId(): ?int { return $this->id; }

    public function getSubcategory(): ?CastellumSubcategory { return $this->subcategory; }
    public function setSubcategory(?CastellumSubcategory $s): self { $this->subcategory = $s; return $this; }

    public function getCategoryCode(): string { return $this->categoryCode; }
    public function setCategoryCode(string $code): self { $this->categoryCode = $code; return $this; }

    public function getLevelQuestion(): string { return $this->levelQuestion; }
    public function setLevelQuestion(string $l): self { $this->levelQuestion = $l; return $this; }

    public function getQuestionType(): ?string { return $this->questionType; }
    public function setQuestionType(?string $t): self { $this->questionType = $t; return $this; }

    public function getSubject(): ?string { return $this->subject; }
    public function setSubject(?string $s): self { $this->subject = $s; return $this; }

    public function getQuestionText(): ?string { return $this->questionText; }
    public function setQuestionText(?string $t): self { $this->questionText = $t; return $this; }

    public function getAnswerText(): ?string { return $this->answerText; }
    public function setAnswerText(?string $a): self { $this->answerText = $a; return $this; }

    public function getExplanation(): ?string { return $this->explanation; }
    public function setExplanation(?string $e): self { $this->explanation = $e; return $this; }

    public function getQuestionImage(): ?string { return $this->questionImage; }
    public function setQuestionImage(?string $p): self { $this->questionImage = $p; return $this; }

    public function getDurationSeconds(): ?int { return $this->durationSeconds; }
    public function setDurationSeconds(?int $v): self { $this->durationSeconds = $v; return $this; }

    public function getFormationChapter(): ?string { return $this->formationChapter; }
    public function setFormationChapter(?string $v): self { $this->formationChapter = $v; return $this; }

    public function getFormationParagraph(): ?string { return $this->formationParagraph; }
    public function setFormationParagraph(?string $v): self { $this->formationParagraph = $v; return $this; }

    public function getQuestionAudio(): ?string { return $this->questionAudio; }
    public function setQuestionAudio(?string $p): self { $this->questionAudio = $p; return $this; }

    public function getCoordX(): ?int { return $this->coordX; }
    public function setCoordX(?int $v): self { $this->coordX = $v; return $this; }

    public function getCoordY(): ?int { return $this->coordY; }
    public function setCoordY(?int $v): self { $this->coordY = $v; return $this; }

    public function getQcmText1(): ?string { return $this->qcmText1; }
    public function setQcmText1(?string $v): self { $this->qcmText1 = $v; return $this; }
    public function getQcmText2(): ?string { return $this->qcmText2; }
    public function setQcmText2(?string $v): self { $this->qcmText2 = $v; return $this; }
    public function getQcmText3(): ?string { return $this->qcmText3; }
    public function setQcmText3(?string $v): self { $this->qcmText3 = $v; return $this; }
    public function getQcmText4(): ?string { return $this->qcmText4; }
    public function setQcmText4(?string $v): self { $this->qcmText4 = $v; return $this; }
    public function getQcmText5(): ?string { return $this->qcmText5; }
    public function setQcmText5(?string $v): self { $this->qcmText5 = $v; return $this; }
    public function getQcmText6(): ?string { return $this->qcmText6; }
    public function setQcmText6(?string $v): self { $this->qcmText6 = $v; return $this; }
    public function getQcmText7(): ?string { return $this->qcmText7; }
    public function setQcmText7(?string $v): self { $this->qcmText7 = $v; return $this; }
    public function getQcmText8(): ?string { return $this->qcmText8; }
    public function setQcmText8(?string $v): self { $this->qcmText8 = $v; return $this; }
    public function getQcmText9(): ?string { return $this->qcmText9; }
    public function setQcmText9(?string $v): self { $this->qcmText9 = $v; return $this; }
    public function getQcmText10(): ?string { return $this->qcmText10; }
    public function setQcmText10(?string $v): self { $this->qcmText10 = $v; return $this; }

    public function getQcmImage1(): ?string { return $this->qcmImage1; }
    public function setQcmImage1(?string $v): self { $this->qcmImage1 = $v; return $this; }
    public function getQcmImage2(): ?string { return $this->qcmImage2; }
    public function setQcmImage2(?string $v): self { $this->qcmImage2 = $v; return $this; }
    public function getQcmImage3(): ?string { return $this->qcmImage3; }
    public function setQcmImage3(?string $v): self { $this->qcmImage3 = $v; return $this; }
    public function getQcmImage4(): ?string { return $this->qcmImage4; }
    public function setQcmImage4(?string $v): self { $this->qcmImage4 = $v; return $this; }
    public function getQcmImage5(): ?string { return $this->qcmImage5; }
    public function setQcmImage5(?string $v): self { $this->qcmImage5 = $v; return $this; }
    public function getQcmImage6(): ?string { return $this->qcmImage6; }
    public function setQcmImage6(?string $v): self { $this->qcmImage6 = $v; return $this; }
    public function getQcmImage7(): ?string { return $this->qcmImage7; }
    public function setQcmImage7(?string $v): self { $this->qcmImage7 = $v; return $this; }
    public function getQcmImage8(): ?string { return $this->qcmImage8; }
    public function setQcmImage8(?string $v): self { $this->qcmImage8 = $v; return $this; }
    public function getQcmImage9(): ?string { return $this->qcmImage9; }
    public function setQcmImage9(?string $v): self { $this->qcmImage9 = $v; return $this; }

    public function getValidationSignataire1(): ?string { return $this->validationSignataire1; }
    public function setValidationSignataire1(?string $v): self { $this->validationSignataire1 = $v; return $this; }
    public function getValidationSignataire2(): ?string { return $this->validationSignataire2; }
    public function setValidationSignataire2(?string $v): self { $this->validationSignataire2 = $v; return $this; }
    public function getValidationSignataire3(): ?string { return $this->validationSignataire3; }
    public function setValidationSignataire3(?string $v): self { $this->validationSignataire3 = $v; return $this; }

    // --- updatedAt accessors & lifecycle ---

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function nowParis(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
    }

    public function touch(): self
    {
        $this->updatedAt = $this->nowParis();   // au lieu de new \DateTimeImmutable()
        return $this;
    }

    #[ORM\PrePersist]
    public function _onCreate(): void
    {
        $this->updatedAt = $this->nowParis();
    }

    #[ORM\PreUpdate]
    public function _onUpdate(): void
    {
        $this->updatedAt = $this->nowParis();
    }

}
