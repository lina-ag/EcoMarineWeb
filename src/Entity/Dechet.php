<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\DechetRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DechetRepository::class)]
#[ORM\Table(name: 'dechet')]
class Dechet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_dechet = null;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    #[Assert\NotBlank(message: "Le type de déchet est obligatoire.")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Le type de déchet doit contenir au moins 3 caractères.",
        maxMessage: "Le type de déchet ne doit pas dépasser 255 caractères."
    )]
    private ?string $type = null;

    #[ORM\Column(type: 'float', nullable: false)]
    #[Assert\NotNull(message: "La quantité est obligatoire.")]
    #[Assert\Positive(message: "La quantité doit être supérieure à 0.")]
    private ?float $quantite = null;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    #[Assert\NotBlank(message: "La zone est obligatoire.")]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: "La zone doit contenir au moins 2 caractères.",
        maxMessage: "La zone ne doit pas dépasser 255 caractères."
    )]
    private ?string $zone = null;

    public function getId_dechet(): ?int
    {
        return $this->id_dechet;
    }

    public function setId_dechet(int $id_dechet): self
    {
        $this->id_dechet = $id_dechet;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getQuantite(): ?float
    {
        return $this->quantite;
    }

    public function setQuantite(float $quantite): self
    {
        $this->quantite = $quantite;
        return $this;
    }

    public function getZone(): ?string
    {
        return $this->zone;
    }

    public function setZone(string $zone): self
    {
        $this->zone = $zone;
        return $this;
    }

    public function getIdDechet(): ?int
    {
        return $this->id_dechet;
    }
}