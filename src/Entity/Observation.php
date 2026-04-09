<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ObservationRepository;

#[ORM\Entity(repositoryClass: ObservationRepository::class)]
#[ORM\Table(name: 'observation')]
class Observation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_observation = null;

    public function getId_observation(): ?int
    {
        return $this->id_observation;
    }

    public function setId_observation(int $id_observation): self
    {
        $this->id_observation = $id_observation;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $date_observation = null;

    public function getDate_observation(): ?\DateTimeInterface
    {
        return $this->date_observation;
    }

    public function setDate_observation(\DateTimeInterface $date_observation): self
    {
        $this->date_observation = $date_observation;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?float $temperature = null;

    public function getTemperature(): ?float
    {
        return $this->temperature;
    }

    public function setTemperature(?float $temperature): self
    {
        $this->temperature = $temperature;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $meteo = null;

    public function getMeteo(): ?string
    {
        return $this->meteo;
    }

    public function setMeteo(?string $meteo): self
    {
        $this->meteo = $meteo;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id_animal = null;

    public function getId_animal(): ?int
    {
        return $this->id_animal;
    }

    public function setId_animal(int $id_animal): self
    {
        $this->id_animal = $id_animal;
        return $this;
    }

    public function getIdObservation(): ?int
    {
        return $this->id_observation;
    }

    public function getDateObservation(): ?\DateTime
    {
        return $this->date_observation;
    }

    public function setDateObservation(\DateTime $date_observation): static
    {
        $this->date_observation = $date_observation;

        return $this;
    }

    public function getIdAnimal(): ?int
    {
        return $this->id_animal;
    }

    public function setIdAnimal(int $id_animal): static
    {
        $this->id_animal = $id_animal;

        return $this;
    }

}
