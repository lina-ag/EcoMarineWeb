<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

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
    private ?string $heure_debut = null;

    public function getHeure_debut(): ?string
    {
        return $this->heure_debut;
    }

    public function setHeure_debut(string $heure_debut): self
    {
        $this->heure_debut = $heure_debut;
        return $this;
    }

    #[ORM\Column(type: 'time', nullable: false)]
    private ?string $heure_fin = null;

    public function getHeure_fin(): ?string
    {
        return $this->heure_fin;
    }

    public function setHeure_fin(string $heure_fin): self
    {
        $this->heure_fin = $heure_fin;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
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

    #[ORM\Column(type: 'decimal', nullable: true)]
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

    public function getHeureDebut(): ?\DateTime
    {
        return $this->heure_debut;
    }

    public function setHeureDebut(\DateTime $heure_debut): static
    {
        $this->heure_debut = $heure_debut;

        return $this;
    }

    public function getHeureFin(): ?\DateTime
    {
        return $this->heure_fin;
    }

    public function setHeureFin(\DateTime $heure_fin): static
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
