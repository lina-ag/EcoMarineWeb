<?php

namespace App\Entity;

use App\Repository\DechetRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DechetRepository::class)]
#[ORM\Table(name: 'dechet')]
class Dechet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_dechet', type: 'integer')]
    private ?int $id_dechet = null;

    #[ORM\Column(name: 'type', type: 'string', length: 255)]
    #[Assert\NotBlank]
    private ?string $type = null;

    #[ORM\Column(name: 'quantite', type: 'float')]
    private ?float $quantite = null;

    #[ORM\Column(name: 'zone', type: 'string', length: 255)]
    #[Assert\NotBlank]
    private ?string $zone = null;

    #[ORM\Column(name: 'description', type: 'text')]
    #[Assert\NotBlank]
    private ?string $description = null;

    #[ORM\Column(name: 'date_signalement', type: 'date')]
    private ?\DateTimeInterface $dateSignalement = null;

    #[ORM\Column(name: 'statut', type: 'string', length: 50)]
    private ?string $statut = null;

    #[ORM\Column(name: 'photo', type: 'string', length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $aiTypeSuggestion = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $aiConfidence = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $aiSummary = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $weatherMain = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $weatherWind = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $priorityScore = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $priorityLabel = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $recommendedAction = null;

    public function getId_dechet(): ?int
    {
        return $this->id_dechet;
    }
    public function getIdDechet(): ?int
    {
        return $this->id_dechet;
    }

    public function getType(): ?string
    {
        return $this->type;
    }
    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getQuantite(): ?float
    {
        return $this->quantite;
    }
    public function setQuantite(?float $quantite): self
    {
        $this->quantite = $quantite;
        return $this;
    }

    public function getZone(): ?string
    {
        return $this->zone;
    }
    public function setZone(?string $zone): self
    {
        $this->zone = $zone;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDateSignalement(): ?\DateTimeInterface
    {
        return $this->dateSignalement;
    }
    public function setDateSignalement(?\DateTimeInterface $dateSignalement): self
    {
        $this->dateSignalement = $dateSignalement;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }
    public function setStatut(?string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }
    public function setPhoto(?string $photo): self
    {
        $this->photo = $photo;
        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }
    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }
    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getAiTypeSuggestion(): ?string
    {
        return $this->aiTypeSuggestion;
    }
    public function setAiTypeSuggestion(?string $aiTypeSuggestion): self
    {
        $this->aiTypeSuggestion = $aiTypeSuggestion;
        return $this;
    }

    public function getAiConfidence(): ?float
    {
        return $this->aiConfidence;
    }
    public function setAiConfidence(?float $aiConfidence): self
    {
        $this->aiConfidence = $aiConfidence;
        return $this;
    }

    public function getAiSummary(): ?string
    {
        return $this->aiSummary;
    }
    public function setAiSummary(?string $aiSummary): self
    {
        $this->aiSummary = $aiSummary;
        return $this;
    }

    public function getWeatherMain(): ?string
    {
        return $this->weatherMain;
    }
    public function setWeatherMain(?string $weatherMain): self
    {
        $this->weatherMain = $weatherMain;
        return $this;
    }

    public function getWeatherWind(): ?float
    {
        return $this->weatherWind;
    }
    public function setWeatherWind(?float $weatherWind): self
    {
        $this->weatherWind = $weatherWind;
        return $this;
    }

    public function getPriorityScore(): ?int
    {
        return $this->priorityScore;
    }
    public function setPriorityScore(?int $priorityScore): self
    {
        $this->priorityScore = $priorityScore;
        return $this;
    }

    public function getPriorityLabel(): ?string
    {
        return $this->priorityLabel;
    }
    public function setPriorityLabel(?string $priorityLabel): self
    {
        $this->priorityLabel = $priorityLabel;
        return $this;
    }

    public function getRecommendedAction(): ?string
    {
        return $this->recommendedAction;
    }
    public function setRecommendedAction(?string $recommendedAction): self
    {
        $this->recommendedAction = $recommendedAction;
        return $this;
    }
}
