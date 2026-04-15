<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\SurvzoneRepository;
use App\Entity\Zonep;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SurvzoneRepository::class)]
#[ORM\Table(name: 'survzone')]
class Survzone
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idSurv', type: 'integer')]
    private ?int $idSurv = null;

    public function getIdSurv(): ?int
    {
        return $this->idSurv;
    }

    public function setIdSurv(int $idSurv): self
    {
        $this->idSurv = $idSurv;
        return $this;
    }

    #[ORM\Column(name: 'dateSurv', type: 'date', nullable: false)]
    #[Assert\NotBlank(message: 'La date est obligatoire')]
    #[Assert\LessThanOrEqual(value: 'today', message: 'La date ne peut pas être dans le futur')]
    #[Assert\GreaterThanOrEqual(value: '-10 years', message: 'La date ne peut pas être antérieure à 10 ans')]
    private ?\DateTimeInterface $dateSurv = null;

    public function getDateSurv(): ?\DateTimeInterface
    {
        return $this->dateSurv;
    }

    public function setDateSurv(\DateTimeInterface $dateSurv): self
    {
        $this->dateSurv = $dateSurv;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\NotBlank(message: 'L\'observation est obligatoire')]
    #[Assert\Length(max: 1000, maxMessage: 'L\'observation ne peut pas dépasser {{ limit }} caractères')]
    private ?string $observation = null;

    public function getObservation(): ?string
    {
        return $this->observation;
    }

    public function setObservation(?string $observation): self
    {
        $this->observation = $observation;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Zonep::class)]
    #[ORM\JoinColumn(name: "idZone", referencedColumnName: "idZone", nullable: false)]
    #[Assert\NotBlank(message: 'La zone est obligatoire')]
    private ?Zonep $zone = null;

    public function getZone(): ?Zonep
    {
        return $this->zone;
    }

    public function setZone(?Zonep $zone): self
    {
        $this->zone = $zone;
        return $this;
    }
}