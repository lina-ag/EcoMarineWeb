<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

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
    #[Assert\NotBlank(message: 'Le nom de la zone est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caracteres.',
        maxMessage: 'Le nom ne doit pas depasser {{ limit }} caracteres.'
    )]
    #[Assert\Regex(
        pattern: '/^[\p{L}\p{N}\s\-\'\.]+$/u',
        message: 'Le nom contient des caracteres non autorises.'
    )]
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
    #[Assert\NotBlank(message: 'La categorie est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 80,
        minMessage: 'La categorie doit contenir au moins {{ limit }} caracteres.',
        maxMessage: 'La categorie ne doit pas depasser {{ limit }} caracteres.'
    )]
    #[Assert\Regex(
        pattern: '/^[\p{L}\p{N}\s\-\'\.]+$/u',
        message: 'La categorie contient des caracteres non autorises.'
    )]
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
    #[Assert\NotBlank(message: 'Le statut est obligatoire.')]
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
