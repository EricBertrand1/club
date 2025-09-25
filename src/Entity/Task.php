<?php

namespace App\Entity;

use App\Entity\User;
use App\Repository\TaskRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
class Task
{
    public const STATUS_NOT_STARTED = 'non_commence';
    public const STATUS_IN_PROGRESS = 'en_cours';
    public const STATUS_DONE        = 'termine';

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tasks', targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Project $project = null;

    #[ORM\Column(length: 150)]
    private string $name = '';

    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'task_user')]
    private Collection $actors;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $hoursPlanned = 1;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_NOT_STARTED;

    #[ORM\Column(type: 'smallint', options: ['unsigned' => true])]
    private int $position = 1; // ordre dans la ligne

    public function __construct() { $this->actors = new ArrayCollection(); }

    // Getters/Setters
    public function getId(): ?int { return $this->id; }
    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): self { $this->project = $project; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    /** @return Collection<int, User> */
    public function getActors(): Collection { return $this->actors; }
    public function addActor(User $user): self { if (!$this->actors->contains($user)) $this->actors->add($user); return $this; }
    public function removeActor(User $user): self { $this->actors->removeElement($user); return $this; }

    public function getStartDate(): ?\DateTimeInterface { return $this->startDate; }
    public function setStartDate(?\DateTimeInterface $d): self { $this->startDate = $d; return $this; }

    public function getEndDate(): ?\DateTimeInterface { return $this->endDate; }
    public function setEndDate(?\DateTimeInterface $d): self { $this->endDate = $d; return $this; }

    public function getHoursPlanned(): int { return $this->hoursPlanned; }
    public function setHoursPlanned(int $h): self { $this->hoursPlanned = max(1, $h); return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $s): self { $this->status = $s; return $this; }

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $p): self { $this->position = $p; return $this; }
}
