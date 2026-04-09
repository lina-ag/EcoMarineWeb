<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ActiviteEcologiqueRepository;

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
