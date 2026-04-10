<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\DechetRepository;

#[ORM\Entity(repositoryClass: DechetRepository::class)]
#[ORM\Table(name: 'dechet')]
class Dechet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_dechet = null;

    public function getId_dechet(): ?int
    {
        return $this->id_dechet;
    }

    public function setId_dechet(int $id_dechet): self
    {
        $this->id_dechet = $id_dechet;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $type = null;

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    #[ORM\Column(type: 'float', nullable: false)]
    private ?float $quantite = null;

    public function getQuantite(): ?float
    {
        return $this->quantite;
    }

    public function setQuantite(float $quantite): self
    {
        $this->quantite = $quantite;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $zone = null;

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
