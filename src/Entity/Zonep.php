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

    #[ORM\OneToMany(mappedBy: 'zone', targetEntity: Survzone::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $survzones;

    public function __construct()
    {
        $this->survzones = new ArrayCollection();
    }

    public function getIdZone(): ?int
    {
        return $this->idZone;
    }

    public function __toString(): string
    {
        return $this->nomZone ?? sprintf('Zone #%d', $this->idZone ?? 0);
    }

    public function setIdZone(int $idZone): self
    {
        $this->idZone = $idZone;
        return $this;
    }

    #[ORM\Column(name: 'nomZone', type: 'string', length: 100, nullable: false)]
    #[Assert\NotBlank(message: 'Le nom de la zone est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom ne doit pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^[\p{L}\p{N}\s\-\'\.]+$/u',
        message: 'Le nom contient des caractères non autorisés.'
    )]
    private ?string $nomZone = null;

    public function getNomZone(): ?string
    {
        return $this->nomZone;
    }

    public function setNomZone(string $nomZone): self
    {
        $this->nomZone = trim($nomZone);
        return $this;
    }

    #[ORM\Column(name: 'categorieZone', type: 'string', length: 80, nullable: false)]
    #[Assert\NotBlank(message: 'La catégorie est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 80,
        minMessage: 'La catégorie doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'La catégorie ne doit pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^[\p{L}\p{N}\s\-\'\.]+$/u',
        message: 'La catégorie contient des caractères non autorisés.'
    )]
    private ?string $categorieZone = null;

    public function getCategorieZone(): ?string
    {
        return $this->categorieZone;
    }

    public function setCategorieZone(string $categorieZone): self
    {
        $this->categorieZone = trim($categorieZone);
        return $this;
    }

    #[ORM\Column(type: 'string', length: 20, nullable: false)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire.')]
    #[Assert\Choice(
        choices: ['Actif', 'En surveillance', 'En maintenance', 'Inactif'],
        message: 'Le statut choisi est invalide.'
    )]
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

    public function getSurvzones(): Collection
    {
        return $this->survzones;
    }

    public function addSurvzone(Survzone $survzone): self
    {
        if (!$this->survzones->contains($survzone)) {
            $this->survzones->add($survzone);
            $survzone->setZone($this);
        }
        return $this;
    }

    public function removeSurvzone(Survzone $survzone): self
    {
        if ($this->survzones->removeElement($survzone)) {
            if ($survzone->getZone() === $this) {
                $survzone->setZone(null);
            }
        }
        return $this;
    }
}