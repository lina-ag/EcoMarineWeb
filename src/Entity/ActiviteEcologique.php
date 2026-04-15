<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ActiviteEcologiqueRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ActiviteEcologiqueRepository::class)]
#[ORM\Table(name: 'activite_ecologique')]
class ActiviteEcologique
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_activite = null;

    public function getId_activite(): ?int
    {
        return $this->id_activite;
    }

    public function setId_activite(int $id_activite): self
    {
        $this->id_activite = $id_activite;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire ')]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: 'Le nom doit contenir au moins 3 caracteres',
        maxMessage: 'Le nom ne peut pas depasser 100 caracteres'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-ZÀ-ÿ0-9\s\-]+$/u',
        message: 'Le nom ne doit pas contenir de caracteres speciaux'
    )]
    private ?string $nom_activite = null;

    public function getNom_activite(): ?string
    {
        return $this->nom_activite;
    }

    public function setNom_activite(string $nom_activite): self
    {
        $this->nom_activite = $nom_activite;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    #[Assert\NotNull(message: 'La date est obligatoire')]
    #[Assert\GreaterThanOrEqual(
        value: 'today',
        message: 'La date doit etre aujourd\'hui ou dans le futur'
    )]
    private ?\DateTimeInterface $date_activite = null;

    public function getDate_activite(): ?\DateTimeInterface
    {
        return $this->date_activite;
    }

    public function setDate_activite(\DateTimeInterface $date_activite): self
    {
        $this->date_activite = $date_activite;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    #[Assert\NotNull(message: 'La capacite est obligatoire')]
    #[Assert\Positive(message: 'La capacite doit etre un nombre positif')]
    #[Assert\Range(
        min: 1,
        max: 500,
        notInRangeMessage: 'La capacite doit etre entre 1 et 500'
    )]
    private ?int $capacite = null;

    public function getCapacite(): ?int
    {
        return $this->capacite;
    }

    public function setCapacite(int $capacite): self
    {
        $this->capacite = $capacite;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: 'La description ne peut pas depasser 1000 caracteres'
    )]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'activiteEcologique')]
    private Collection $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        if (!$this->reservations instanceof Collection) {
            $this->reservations = new ArrayCollection();
        }
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->getReservations()->contains($reservation)) {
            $this->getReservations()->add($reservation);
        }
        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        $this->getReservations()->removeElement($reservation);
        return $this;
    }

    public function getIdActivite(): ?int
    {
        return $this->id_activite;
    }

    public function getNomActivite(): ?string
    {
        return $this->nom_activite;
    }

    public function setNomActivite(string $nom_activite): static
    {
        $this->nom_activite = $nom_activite;

        return $this;
    }

    public function getDateActivite(): ?\DateTime
    {
        return $this->date_activite;
    }

    public function setDateActivite(\DateTime $date_activite): static
    {
        $this->date_activite = $date_activite;

        return $this;
    }

}
