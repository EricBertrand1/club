<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;


#[ORM\Entity]
#[ORM\Table(name: 'objet')]
class Objet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idObjet', type: 'integer')]
    private ?int $idObjet = null;

    #[ORM\Column(name: 'titreObjet', length: 255)]
    private string $titreObjet = '';

    // Toutes catégories du Bon Coin sauf Immobilier (on stocke la valeur choisie)
    #[ORM\Column(name: 'categorie', length: 100)]
    private string $categorie = '';

    #[ORM\Column(name: 'imageObjet1', length: 255, nullable: true)]
    private ?string $imageObjet1 = null;

    #[ORM\Column(name: 'imageObjet2', length: 255, nullable: true)]
    private ?string $imageObjet2 = null;

    #[ORM\Column(name: 'imageObjet3', length: 255, nullable: true)]
    private ?string $imageObjet3 = null;

    #[ORM\Column(name: 'auteur', length: 100)]
    private string $auteur = '';

    // Date de publication
    #[ORM\Column(name: 'date', type: Types::DATE_MUTABLE)]
    private \DateTime $date;

    // Prix en écu (entier)
    #[ORM\Column(name: 'prix', type: 'integer')]
    private int $prix = 0;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    public function __construct()
    {
        $this->date = new \DateTime('today');
    }

    public function getIdObjet(): ?int { return $this->idObjet; }

    public function getTitreObjet(): string { return $this->titreObjet; }
    public function setTitreObjet(string $t): self { $this->titreObjet = $t; return $this; }

    public function getCategorie(): string { return $this->categorie; }
    public function setCategorie(string $c): self { $this->categorie = $c; return $this; }

    public function getImageObjet1(): ?string { return $this->imageObjet1; }
    public function setImageObjet1(?string $f): self { $this->imageObjet1 = $f; return $this; }

    public function getImageObjet2(): ?string { return $this->imageObjet2; }
    public function setImageObjet2(?string $f): self { $this->imageObjet2 = $f; return $this; }

    public function getImageObjet3(): ?string { return $this->imageObjet3; }
    public function setImageObjet3(?string $f): self { $this->imageObjet3 = $f; return $this; }

    public function getAuteur(): string { return $this->auteur; }
    public function setAuteur(string $a): self { $this->auteur = $a; return $this; }

    public function getDate(): \DateTime { return $this->date; }
    public function setDate(\DateTime $d): self { $this->date = $d; return $this; }

    public function getPrix(): int { return $this->prix; }
    public function setPrix(int $p): self { $this->prix = $p; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $d): self { $this->description = $d; return $this; }
}
