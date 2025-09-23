<?php
// src/Entity/Rubrique.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Rubrique
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length:100)]
    private string $label;

    #[ORM\Column(length:255, nullable:true)]
    private ?string $img = null;

    #[ORM\ManyToOne(inversedBy: 'rubriques')]
    private ?User $user = null;

    #[ORM\OneToMany(mappedBy: 'rubrique', targetEntity: Theme::class, cascade: ['persist', 'remove'])]
    private $themes;
}
