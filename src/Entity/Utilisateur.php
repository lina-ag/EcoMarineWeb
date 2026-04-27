<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UtilisateurRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: 'utilisateur')]
class Utilisateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', name: 'id_utilisateur')]
    private ?int $id_utilisateur = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le nom est obligatoire")]
    #[Assert\Length(min: 2, max: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le prénom est obligatoire")]
    #[Assert\Length(min: 2, max: 100)]
    private ?string $prenom = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: "L'email est obligatoire")]
    #[Assert\Email(message: "L'email '{{ value }}' n'est pas valide")]
    #[Assert\Regex(
        pattern: "/@(outlook|hotmail)\.com$/",
        message: "L'email doit être une adresse Outlook (@outlook.com ou @hotmail.com)"
    )]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le mot de passe est obligatoire")]
    #[Assert\Length(min: 8, minMessage: "Le mot de passe doit avoir au moins {{ limit }} caractères")]
    private ?string $mot_de_passe = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: "Le téléphone est obligatoire")]
    #[Assert\Regex(
        pattern: "/^(2|5|9)[0-9]{7}$/",
        message: "Numéro tunisien valide requis (8 chiffres : commence par 2,5 ou 9)"
    )]
    private ?string $telephone = null;

    #[ORM\ManyToOne(targetEntity: Role::class, inversedBy: 'utilisateurs')]
    #[ORM\JoinColumn(name: 'id_role', referencedColumnName: 'id_role', nullable: true)]
    private ?Role $role = null;

   #[ORM\Column(type: "date")]
   #[Assert\NotBlank(message: "La date de naissance est obligatoire")]
   #[Assert\LessThan(
    value: "today",
    message: "La date de naissance doit être strictement inférieure à la date d'aujourd'hui"
   )]
    private ?\DateTimeInterface $date_naissance = null;

    #[ORM\Column(type: "blob", nullable: true)]
    private $face_encoding = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $face_image = null;

    #[ORM\Column(type: "datetime")]
    private ?\DateTimeInterface $created_at = null;

    // ==================== GETTERS & SETTERS ====================
    

    public function getIdUtilisateur(): ?int
    {
        return $this->id_utilisateur;
    }

    public function setIdUtilisateur(int $id_utilisateur): self
    {
        $this->id_utilisateur = $id_utilisateur;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getMotDePasse(): ?string
    {
        return $this->mot_de_passe;
    }

    public function setMotDePasse(?string $mot_de_passe): self
    {
        $this->mot_de_passe = $mot_de_passe;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): self
    {
        $this->role = $role;
        return $this;
    }

    // 🔥 Méthode pour obtenir le nom du rôle (compatible avec l'ancien code)
    public function getRoleName(): ?string
    {
        return $this->role ? $this->role->getNomRole() : null;
    }


    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->date_naissance;
    }

    public function setDateNaissance(?\DateTimeInterface $date_naissance): self
    {
        $this->date_naissance = $date_naissance;
        return $this;
    }

    public function getFaceEncoding()
    {
        if (is_resource($this->face_encoding)) {
            return stream_get_contents($this->face_encoding) ?: null;
        }
        return $this->face_encoding;
    }

    public function setFaceEncoding($face_encoding): self
    {
        $this->face_encoding = $face_encoding;
        return $this;
    }

    public function getFaceImage(): ?string
    {
        return $this->face_image;
    }

    public function setFaceImage(?string $face_image): self
    {
        $this->face_image = $face_image;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }
}
