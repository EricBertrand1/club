<?php
namespace App\Entity;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Report
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    // "Signaler un contenu inaproprié" | "Signaler un bug" | "Suggérer une amélioration du site"
    #[ORM\Column(length: 80)]
    private string $type;

    // "Général" | "Agenda" | ... | "Autre"
    #[ORM\Column(length: 50)]
    private string $context;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne]
    private ?User $user = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }

    public function getContext(): string { return $this->context; }
    public function setContext(string $context): self { $this->context = $context; return $this; }

    public function getMessage(): string { return $this->message; }
    public function setMessage(string $message): self { $this->message = $message; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }
}
