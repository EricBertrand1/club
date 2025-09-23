<?php
// src/Entity/Theme.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Theme
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length:100, nullable:true)]
    private ?string $label = null;

    #[ORM\ManyToOne(inversedBy: 'themes')]
    private ?Rubrique $rubrique = null;
}
