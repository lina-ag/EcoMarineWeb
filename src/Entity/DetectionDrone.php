<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\DetectionDroneRepository;

#[ORM\Entity(repositoryClass: DetectionDroneRepository::class)]
#[ORM\Table(name: 'detection_drone')]
class DetectionDrone
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_detection = null;

    public function getId_detection(): ?int
    {
        return $this->id_detection;
    }

    public function setId_detection(int $id_detection): self
    {
        $this->id_detection = $id_detection;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $espece = null;

    public function getEspece(): ?string
    {
        return $this->espece;
    }

    public function setEspece(string $espece): self
    {
        $this->espece = $espece;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $nombre_individus = null;

    public function getNombre_individus(): ?int
    {
        return $this->nombre_individus;
    }

    public function setNombre_individus(int $nombre_individus): self
    {
        $this->nombre_individus = $nombre_individus;
        return $this;
    }

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $latitude = null;

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $longitude = null;

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $comportement = null;

    public function getComportement(): ?string
    {
        return $this->comportement;
    }

    public function setComportement(?string $comportement): self
    {
        $this->comportement = $comportement;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $confiance_ia = null;

    public function getConfiance_ia(): ?string
    {
        return $this->confiance_ia;
    }

    public function setConfiance_ia(?string $confiance_ia): self
    {
        $this->confiance_ia = $confiance_ia;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $image_path = null;

    public function getImage_path(): ?string
    {
        return $this->image_path;
    }

    public function setImage_path(?string $image_path): self
    {
        $this->image_path = $image_path;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $timestamp = null;

    public function getTimestamp(): ?string
    {
        return $this->timestamp;
    }

    public function setTimestamp(?string $timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    public function getIdDetection(): ?int
    {
        return $this->id_detection;
    }

    public function getIdMission(): ?int
    {
        return $this->id_mission;
    }

    public function setIdMission(int $id_mission): static
    {
        $this->id_mission = $id_mission;

        return $this;
    }

    public function getNombreIndividus(): ?int
    {
        return $this->nombre_individus;
    }

    public function setNombreIndividus(int $nombre_individus): static
    {
        $this->nombre_individus = $nombre_individus;

        return $this;
    }

    public function getConfianceIa(): ?string
    {
        return $this->confiance_ia;
    }

    public function setConfianceIa(?string $confiance_ia): static
    {
        $this->confiance_ia = $confiance_ia;

        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->image_path;
    }

    public function setImagePath(?string $image_path): static
    {
        $this->image_path = $image_path;

        return $this;
    }

}
