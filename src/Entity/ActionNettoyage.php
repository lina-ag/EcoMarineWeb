<?php

namespace App\Entity;

use App\Repository\ActionNettoyageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ActionNettoyageRepository::class)]
#[ORM\Table(name: 'action_nettoyage')]
class ActionNettoyage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_action', type: 'integer')]
    private ?int $id_action = null;

    #[ORM\Column(name: 'date_action', type: 'date')]
    #[Assert\NotBlank(message: "La date de l’action est obligatoire.")]
    #[Assert\GreaterThanOrEqual('today', message: "La date doit être aujourd’hui ou dans le futur.")]
    private ?\DateTimeInterface $date_action = null;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    #[Assert\NotBlank(message: "Le lieu est obligatoire.")]
    #[Assert\Length(
        min: 3,
        minMessage: "Le lieu doit contenir au moins {{ limit }} caractères.",
        max: 255,
        maxMessage: "Le lieu ne doit pas dépasser {{ limit }} caractères."
    )]
    #[Assert\Regex(
        pattern: "/^[\p{L}\s'\-0-9,]+$/u",
        message: "Le lieu contient des caractères non autorisés."
    )]
    private ?string $lieu = null;


    // #[ORM\Column(name: 'limite_benevoles', type: 'integer', nullable: true)]
    // #[Assert\Positive(message: "La limite de bénévoles doit être un nombre positif.")]
    // #[Assert\Range(
    //     min: 1,
    //     max: 1000,
    //     notInRangeMessage: "La limite doit être entre {{ min }} et {{ max }}."
    // )]

    #[ORM\Column(name: 'limite_benevoles', type: 'integer')]
    #[Assert\NotBlank(message: "La limite de bénévoles est obligatoire.")]
    #[Assert\Positive(message: "La limite de bénévoles doit être un nombre positif.")]
    #[Assert\Range(
        min: 1,
        max: 1000,
        notInRangeMessage: "La limite doit être entre {{ min }} et {{ max }}."
    )]

    private ?int $limiteBenevoles = null;

    #[ORM\OneToMany(mappedBy: 'id_action', targetEntity: Volontaire::class, orphanRemoval: false)]
    private Collection $volontaires;

    public function __construct()
    {
        $this->volontaires = new ArrayCollection();
    }

    public function getId_action(): ?int
    {
        return $this->id_action;
    }

    public function getIdAction(): ?int
    {
        return $this->id_action;
    }

    public function getDate_action(): ?\DateTimeInterface
    {
        return $this->date_action;
    }

    public function getDateAction(): ?\DateTimeInterface
    {
        return $this->date_action;
    }

    public function setDate_action(\DateTimeInterface $date_action): self
    {
        $this->date_action = $date_action;
        return $this;
    }

    public function setDateAction(\DateTimeInterface $date_action): self
    {
        $this->date_action = $date_action;
        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(string $lieu): self
    {
        $this->lieu = $lieu;
        return $this;
    }


    public function getLimiteBenevoles(): ?int
    {
        return $this->limiteBenevoles;
    }

    public function setLimiteBenevoles(int $limiteBenevoles): self
    {
        $this->limiteBenevoles = $limiteBenevoles;
        return $this;
    }

    /**
     * @return Collection<int, Volontaire>
     */
    public function getVolontaires(): Collection
    {
        return $this->volontaires;
    }

    public function addVolontaire(Volontaire $volontaire): self
    {
        if (!$this->volontaires->contains($volontaire)) {
            $this->volontaires->add($volontaire);
            $volontaire->setId_action($this);
        }

        return $this;
    }

    public function removeVolontaire(Volontaire $volontaire): self
    {
        if ($this->volontaires->removeElement($volontaire)) {
            if ($volontaire->getId_action() === $this) {
                $volontaire->setId_action(null);
            }
        }

        return $this;
    }

    public function getNombreVolontaires(): int
    {
        return $this->volontaires->count();
    }

    public function estComplete(): bool
    {
        if ($this->limiteBenevoles === null) {
            return false;
        }

        return $this->getNombreVolontaires() >= $this->limiteBenevoles;
    }

    public function __toString(): string
    {
        $date = $this->date_action ? $this->date_action->format('Y-m-d') : 'Sans date';
        return $this->lieu . ' - ' . $date;
    }
}
