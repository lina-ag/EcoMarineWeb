<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use App\Repository\MissionDroneRepository;

#[ORM\Entity(repositoryClass: MissionDroneRepository::class)]
#[ORM\Table(name: 'mission_drone')]
class MissionDrone
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_mission = null;

    public function getId_mission(): ?int
    {
        return $this->id_mission;
    }

    public function setId_mission(int $id_mission): self
    {
        $this->id_mission = $id_mission;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    #[Assert\NotBlank(message: "La date de mission ne peut pas \u00eatre vide.")]
    #[Assert\GreaterThanOrEqual('today', message: "La date doit \u00eatre aujourd'hui ou dans le futur.")]
    private ?\DateTimeInterface $date_mission = null;

    public function getDate_mission(): ?\DateTimeInterface
    {
        return $this->date_mission;
    }

    public function setDate_mission(\DateTimeInterface $date_mission): self
    {
        $this->date_mission = $date_mission;
        return $this;
    }

    #[ORM\Column(type: 'time', nullable: false)]
    #[Assert\NotBlank(message: "L'heure de d\u00e9but ne peut pas \u00eatre vide.")]
    private ?\DateTimeInterface $heure_debut = null;

    public function getHeure_debut(): ?\DateTimeInterface
    {
        return $this->heure_debut;
    }

    public function setHeure_debut(\DateTimeInterface $heure_debut): self
    {
        $this->heure_debut = $heure_debut;
        return $this;
    }

    #[ORM\Column(type: 'time', nullable: false)]
    #[Assert\NotBlank(message: "L'heure de fin ne peut pas \u00eatre vide.")]
    private ?\DateTimeInterface $heure_fin = null;

    public function getHeure_fin(): ?\DateTimeInterface
    {
        return $this->heure_fin;
    }

    public function setHeure_fin(\DateTimeInterface $heure_fin): self
    {
        $this->heure_fin = $heure_fin;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: "La zone survol\u00e9e ne peut pas \u00eatre vide.")]
    #[Assert\Choice(choices: ['Nord', 'Nord-Est', 'Est', 'Sud-Est', 'Sud', 'Sud-Ouest', 'Ouest', 'Nord-Ouest'], message: "La zone doit \u00eatre parmi: Nord, Nord-Est, Est, Sud-Est, Sud, Sud-Ouest, Ouest, Nord-Ouest.")]
    private ?string $zone_survolee = null;

    public function getZone_survolee(): ?string
    {
        return $this->zone_survolee;
    }

    public function setZone_survolee(string $zone_survolee): self
    {
        $this->zone_survolee = $zone_survolee;
        return $this;
    }

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\Range(min: 0, max: 1000, notInRangeMessage: "La distance doit \u00eatre entre 0 et 1000 km.")]
    private ?float $distance_parcourue = null;

    public function getDistance_parcourue(): ?float
    {
        return $this->distance_parcourue;
    }

    public function setDistance_parcourue(?float $distance_parcourue): self
    {
        $this->distance_parcourue = $distance_parcourue;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(min: 0, max: 5000, notInRangeMessage: "L'altitude doit \u00eatre entre 0 et 5000 m\u00e8tres.")]
    private ?int $altitude_vol = null;

    public function getAltitude_vol(): ?int
    {
        return $this->altitude_vol;
    }

    public function setAltitude_vol(?int $altitude_vol): self
    {
        $this->altitude_vol = $altitude_vol;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\Choice(choices: ['Bon', 'Mod\u00e9r\u00e9', 'Mauvais'], message: "Les conditions de vol doivent \u00eatre: Bon, Mod\u00e9r\u00e9 ou Mauvais.")]
    private ?string $conditions_vol = null;

    public function getConditions_vol(): ?string
    {
        return $this->conditions_vol;
    }

    public function setConditions_vol(?string $conditions_vol): self
    {
        $this->conditions_vol = $conditions_vol;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 3000, maxMessage: "Les observations ne peuvent pas d\u00e9passer 3000 caract\u00e8res.")]
    private ?string $observations = null;

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function setObservations(?string $observations): self
    {
        $this->observations = $observations;
        return $this;
    }

    #[Assert\Callback]
    public function validateTimes(ExecutionContextInterface $context): void
    {
        if ($this->heure_debut && $this->heure_fin) {
            if ($this->heure_fin <= $this->heure_debut) {
                $context->buildViolation("L'heure de fin doit être postérieure à l'heure de début.")
                    ->atPath('heure_fin')
                    ->addViolation();
            }
        }
    }

    public function getIdMission(): ?int
    {
        return $this->id_mission;
    }

    public function getDateMission(): ?\DateTime
    {
        return $this->date_mission;
    }

    public function setDateMission(\DateTime $date_mission): static
    {
        $this->date_mission = $date_mission;

        return $this;
    }

    public function getHeureDebut(): ?\DateTimeInterface
    {
        return $this->heure_debut;
    }

    public function setHeureDebut(\DateTimeInterface $heure_debut): static
    {
        $this->heure_debut = $heure_debut;

        return $this;
    }

    public function getHeureFin(): ?\DateTimeInterface
    {
        return $this->heure_fin;
    }

    public function setHeureFin(\DateTimeInterface $heure_fin): static
    {
        $this->heure_fin = $heure_fin;

        return $this;
    }

    public function getZoneSurvolee(): ?string
    {
        return $this->zone_survolee;
    }

    public function setZoneSurvolee(string $zone_survolee): static
    {
        $this->zone_survolee = $zone_survolee;

        return $this;
    }

    public function getDistanceParcourue(): ?string
    {
        return $this->distance_parcourue;
    }

    public function setDistanceParcourue(?string $distance_parcourue): static
    {
        $this->distance_parcourue = $distance_parcourue;

        return $this;
    }

    public function getAltitudeVol(): ?int
    {
        return $this->altitude_vol;
    }

    public function setAltitudeVol(?int $altitude_vol): static
    {
        $this->altitude_vol = $altitude_vol;

        return $this;
    }

    public function getConditionsVol(): ?string
    {
        return $this->conditions_vol;
    }

    public function setConditionsVol(?string $conditions_vol): static
    {
        $this->conditions_vol = $conditions_vol;

        return $this;
    }

}
