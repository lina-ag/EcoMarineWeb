<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ReservationRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservation')]
class Reservation
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_reservation = null;

    public function getId_reservation(): ?int
    {
        return $this->id_reservation;
    }

    public function setId_reservation(int $id_reservation): self
    {
        $this->id_reservation = $id_reservation;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: 'Le nom doit contenir au moins 3 caracteres',
        maxMessage: 'Le nom ne peut pas depasser 100 caracteres'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-ZÀ-ÿ\s\-]+$/u',
        message: 'Le nom ne doit contenir que des lettres'
    )]
    private ?string $nom = null;

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    #[Assert\NotNull(message: 'La date est obligatoire')]
    #[Assert\GreaterThanOrEqual(
        value: 'today',
        message: 'La date doit etre aujourd\'hui ou dans le futur'
    )]
    private ?\DateTimeInterface $date_reservation = null;

    public function getDate_reservation(): ?\DateTimeInterface
    {
        return $this->date_reservation;
    }

    public function setDate_reservation(\DateTimeInterface $date_reservation): self
    {
        $this->date_reservation = $date_reservation;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire')]
    #[Assert\Email(message: 'Veuillez entrer un email valide')]
    #[Assert\Length(
        max: 180,
        maxMessage: 'L\'email ne peut pas depasser 180 caracteres'
    )]
    private ?string $email = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    #[Assert\NotNull(message: 'Le nombre de personnes est obligatoire')]
    #[Assert\Positive(message: 'Le nombre de personnes doit etre positif')]
    #[Assert\Range(
        min: 1,
        max: 50,
        notInRangeMessage: 'Le nombre de personnes doit etre entre 1 et 50'
    )]
    private ?int $nombre_personnes = null;

    public function getNombre_personnes(): ?int
    {
        return $this->nombre_personnes;
    }

    public function setNombre_personnes(int $nombre_personnes): self
    {
        $this->nombre_personnes = $nombre_personnes;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: ActiviteEcologique::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(name: 'id_activite', referencedColumnName: 'id_activite', nullable: true)]
    #[Assert\NotNull(message: 'Veuillez selectionner une activite')]
    private ?ActiviteEcologique $activiteEcologique = null;

    public function getActiviteEcologique(): ?ActiviteEcologique
    {
        return $this->activiteEcologique;
    }

    public function setActiviteEcologique(?ActiviteEcologique $activiteEcologique): self
    {
        $this->activiteEcologique = $activiteEcologique;
        return $this;
    }

    public function getIdReservation(): ?int
    {
        return $this->id_reservation;
    }

    public function getDateReservation(): ?\DateTime
    {
        return $this->date_reservation;
    }

    public function setDateReservation(\DateTime $date_reservation): static
    {
        $this->date_reservation = $date_reservation;

        return $this;
    }

    public function getNombrePersonnes(): ?int
    {
        return $this->nombre_personnes;
    }

    public function setNombrePersonnes(int $nombre_personnes): static
    {
        $this->nombre_personnes = $nombre_personnes;

        return $this;
    }

    #[Assert\Callback]
    public function validateDateMatchesActivity(ExecutionContextInterface $context): void
    {
        // Validation gérée dans ReservationType via POST_SUBMIT
    }
}