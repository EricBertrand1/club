<?php
// src/Entity/CoachingTheme.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class CoachingTheme
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length:50)]
    private string $section; // ex: "corps", "esprit", "intellect", "habitudes"

    #[ORM\Column(length:100, nullable:true)]
    private ?string $label = null;

    #[ORM\Column]
    private int $position; // 1 à 5

    #[ORM\ManyToOne(inversedBy: 'themes')]
    private ?User $user = null;

    // getters / setters …
}
