<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ZonepRepository;

#[ORM\Entity(repositoryClass: ZonepRepository::class)]
#[ORM\Table(name: 'zonep')]
class Zonep
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idZone', type: 'integer')]
    private ?int $idZone = null;

    public function getIdZone(): ?int
    {
        return $this->idZone;
    }

    public function setIdZone(int $idZone): self
    {
        $this->idZone = $idZone;
        return $this;
    }

    #[ORM\Column(name: 'nomZone', type: 'string', nullable: false)]
    private ?string $nomZone = null;

    public function getNomZone(): ?string
    {
        return $this->nomZone;
    }

    public function setNomZone(string $nomZone): self
    {
        $this->nomZone = $nomZone;
        return $this;
    }

    #[ORM\Column(name: 'categorieZone', type: 'string', nullable: false)]
    private ?string $categorieZone = null;

    public function getCategorieZone(): ?string
    {
        return $this->categorieZone;
    }

    public function setCategorieZone(string $categorieZone): self
    {
        $this->categorieZone = $categorieZone;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $status = null;

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

}
