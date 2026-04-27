<?php

namespace App\CleaningBundle\Entity;

use App\CleaningBundle\Repository\VolontaireRepository;
use App\Entity\Utilisateur;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VolontaireRepository::class)]
#[ORM\Table(name: 'volontaire')]
#[UniqueEntity(
    fields: ['nom', 'contact', 'id_action'],
    message: 'Ce volontaire est déjà inscrit dans cette action.'
)]
class Volontaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_volontaire = null;

    #[ORM\Column(type: 'string', length: 100, nullable: false)]
    #[Assert\NotBlank(message: "Le nom est obligatoire.")]
    #[Assert\Length(
        min: 2,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères.",
        max: 100,
        maxMessage: "Le nom ne doit pas dépasser {{ limit }} caractères."
    )]
    #[Assert\Regex(
        pattern: "/^[\p{L}\s'-]+$/u",
        message: "Le nom ne doit contenir que des lettres et des espaces."
    )]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', length: 20, nullable: false)]
    #[Assert\NotBlank(message: "Le contact est obligatoire.")]
    #[Assert\Length(
        min: 8,
        minMessage: "Le contact doit contenir au moins {{ limit }} caractères.",
        max: 20,
        maxMessage: "Le contact ne doit pas dépasser {{ limit }} caractères."
    )]
    #[Assert\Regex(
        pattern: '/^[0-9+\s]+$/',
        message: 'Le contact doit contenir uniquement des chiffres, espaces ou le signe +.'
    )]
    private ?string $contact = null;

    #[ORM\ManyToOne(targetEntity: ActionNettoyage::class, inversedBy: 'volontaires')]
    #[ORM\JoinColumn(name: 'id_action', referencedColumnName: 'id_action', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: "Vous devez choisir une action de nettoyage.")]
    private ?ActionNettoyage $id_action = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id_utilisateur', nullable: true, onDelete: 'SET NULL')]
    private ?Utilisateur $utilisateur = null;

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;
        return $this;
    }
    public function getId_volontaire(): ?int
    {
        return $this->id_volontaire;
    }

    public function getIdVolontaire(): ?int
    {
        return $this->id_volontaire;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(string $contact): self
    {
        $this->contact = $contact;
        return $this;
    }

    public function getId_action(): ?ActionNettoyage
    {
        return $this->id_action;
    }

    public function getIdAction(): ?ActionNettoyage
    {
        return $this->id_action;
    }

    public function setId_action(?ActionNettoyage $id_action): self
    {
        $this->id_action = $id_action;
        return $this;
    }

    public function setIdAction(?ActionNettoyage $id_action): self
    {
        $this->id_action = $id_action;
        return $this;
    }
}
