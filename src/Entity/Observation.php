<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

use App\Repository\ObservationRepository;
use App\Entity\FauneMarine;

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
    #[Assert\NotBlank(message: "La date d'observation ne peut pas être vide.")]
    #[Assert\LessThanOrEqual('today', message: "La date doit être aujourd'hui ou dans le passé.")]
    #[Assert\GreaterThanOrEqual('-10 years', message: "La date d'observation ne peut pas être antérieure à 10 ans.")]
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

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\Range(min: -5, max: 40, notInRangeMessage: "La température doit être entre -5°C et 40°C pour un environnement marin réaliste.")]
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
    #[Assert\Choice(choices: ['Ensoleillé', 'Nuageux', 'Pluvieux', 'Tempête', 'Brumeux', 'Venteux'], message: "La météo doit être parmi les options proposées.")]
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

    #[ORM\ManyToOne(targetEntity: FauneMarine::class)]
    #[ORM\JoinColumn(name: 'id_animal', referencedColumnName: 'id_animal', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotBlank(message: "L'animal ne peut pas être vide.")]
    private ?FauneMarine $animal = null;

    public function getAnimal(): ?FauneMarine
    {
        return $this->animal;
    }

    public function setAnimal(?FauneMarine $animal): self
    {
        $this->animal = $animal;
        return $this;
    }

    public function getId_animal(): ?int
    {
        return $this->animal?->getId_animal();
    }

    public function setId_animal(int $id_animal): self
    {
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
        return $this->animal?->getId_animal();
    }

    public function setIdAnimal(int $id_animal): static
    {
        return $this;
    }
}
