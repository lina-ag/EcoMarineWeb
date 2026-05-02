<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

use App\Repository\PredictionEchouageRepository;

#[ORM\Entity(repositoryClass: PredictionEchouageRepository::class)]
#[ORM\Table(name: 'prediction_echouage')]
class PredictionEchouage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_prediction = null;

    public function getId_prediction(): ?int
    {
        return $this->id_prediction;
    }

    public function setId_prediction(int $id_prediction): self
    {
        $this->id_prediction = $id_prediction;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    #[Assert\NotBlank(message: "La date de prédiction ne peut pas être vide.")]
    #[Assert\GreaterThanOrEqual('today', message: "La date doit être aujourd'hui ou dans le futur.")]
    #[Assert\LessThanOrEqual('+1 year', message: "La date de prédiction ne peut pas dépasser un an dans le futur.")]
    private ?\DateTimeInterface $date_prediction = null;

    public function getDate_prediction(): ?\DateTimeInterface
    {
        return $this->date_prediction;
    }

    public function setDate_prediction(\DateTimeInterface $date_prediction): self
    {
        $this->date_prediction = $date_prediction;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: "La zone ne peut pas être vide.")]
    #[Assert\Choice(choices: ['Nord', 'Nord-Est', 'Est', 'Sud-Est', 'Sud', 'Sud-Ouest', 'Ouest', 'Nord-Ouest'], message: "La zone doit \u00eatre parmi: Nord, Nord-Est, Est, Sud-Est, Sud, Sud-Ouest, Ouest, Nord-Ouest.")]
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

    #[ORM\Column(type: 'integer', nullable: false)]
    #[Assert\NotBlank(message: "Le niveau de risque ne peut pas être vide.")]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: "Le niveau de risque doit être entre 1 et 5.")]
    private ?int $niveau_risque = null;

    public function getNiveau_risque(): ?int
    {
        return $this->niveau_risque;
    }

    public function setNiveau_risque(int $niveau_risque): self
    {
        $this->niveau_risque = $niveau_risque;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\Length(max: 100, maxMessage: "L'espèce concernée ne peut pas dépasser 100 caractères.")]
    private ?string $espece_concernee = null;

    public function getEspece_concernee(): ?string
    {
        return $this->espece_concernee;
    }

    public function setEspece_concernee(?string $espece_concernee): self
    {
        $this->espece_concernee = $espece_concernee;
        return $this;
    }

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\Range(min: -50, max: 50, notInRangeMessage: "La température doit être entre -50°C et 50°C.")]
    private ?float $temperature_eau = null;

    public function getTemperature_eau(): ?float
    {
        return $this->temperature_eau;
    }

    public function setTemperature_eau(?float $temperature_eau): self
    {
        $this->temperature_eau = $temperature_eau;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\Choice(choices: ['Ensoleillé', 'Nuageux', 'Pluvieux', 'Tempête', 'Brumeux', 'Venteux'], message: "Les conditions météo doivent être: Ensoleillé, Nuageux, Pluvieux, Tempête, Brumeux ou Venteux.")]
    private ?string $conditions_meteo = null;

    public function getConditions_meteo(): ?string
    {
        return $this->conditions_meteo;
    }

    public function setConditions_meteo(?string $conditions_meteo): self
    {
        $this->conditions_meteo = $conditions_meteo;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 3000, maxMessage: "Les recommandations ne peuvent pas dépasser 3000 caractères.")]
    private ?string $recommandations = null;

    public function getRecommandations(): ?string
    {
        return $this->recommandations;
    }

    public function setRecommandations(?string $recommandations): self
    {
        $this->recommandations = $recommandations;
        return $this;
    }

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\NotBlank(message: "La latitude ne peut pas être vide.")]
    #[Assert\Range(min: -90, max: 90, notInRangeMessage: "La latitude doit être entre -90 et 90.")]
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
    #[Assert\NotBlank(message: "La longitude ne peut pas être vide.")]
    #[Assert\Range(min: -180, max: 180, notInRangeMessage: "La longitude doit être entre -180 et 180.")]
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

    public function getIdPrediction(): ?int
    {
        return $this->id_prediction;
    }

    public function getDatePrediction(): ?\DateTime
    {
        return $this->date_prediction;
    }

    public function setDatePrediction(\DateTime $date_prediction): static
    {
        $this->date_prediction = $date_prediction;

        return $this;
    }

    public function getNiveauRisque(): ?int
    {
        return $this->niveau_risque;
    }

    public function setNiveauRisque(int $niveau_risque): static
    {
        $this->niveau_risque = $niveau_risque;

        return $this;
    }

    public function getEspeceConcernee(): ?string
    {
        return $this->espece_concernee;
    }

    public function setEspeceConcernee(?string $espece_concernee): static
    {
        $this->espece_concernee = $espece_concernee;

        return $this;
    }

    public function getTemperatureEau(): ?string
    {
        return $this->temperature_eau;
    }

    public function setTemperatureEau(?string $temperature_eau): static
    {
        $this->temperature_eau = $temperature_eau;

        return $this;
    }

    public function getConditionsMeteo(): ?string
    {
        return $this->conditions_meteo;
    }

    public function setConditionsMeteo(?string $conditions_meteo): static
    {
        $this->conditions_meteo = $conditions_meteo;

        return $this;
    }

}
