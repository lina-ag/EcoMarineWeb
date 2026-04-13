<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use App\Repository\FauneMarineRepository;

#[ORM\Entity(repositoryClass: FauneMarineRepository::class)]
#[ORM\Table(name: 'faune_marine')]
#[UniqueEntity(fields: ['espece'], message: 'Cette espèce existe déjà dans la base de données.')]
class FauneMarine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_animal = null;

    public function getId_animal(): ?int
    {
        return $this->id_animal;
    }

    public function setId_animal(int $id_animal): self
    {
        $this->id_animal = $id_animal;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false, unique: true)]
    #[Assert\NotBlank(message: "L'espèce ne peut pas être vide.")]
    #[Assert\Length(min: 3, max: 100, minMessage: "L'espèce doit contenir au moins 3 caractères.", maxMessage: "L'espèce ne peut pas dépasser 100 caractères.")]
    #[Assert\Regex(pattern: "/^[a-zA-ZÀ-ÿ\s\-']+$/", message: "L'espèce ne peut contenir que des lettres, espaces et tirets (pas de chiffres).")]
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

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: "L'état ne peut pas être vide.")]
    #[Assert\Choice(choices: ['Sain', 'Blessé', 'Malade', 'Mort', 'Autre'], message: "L'état doit être: Sain, Blessé, Malade, Mort ou Autre.")]
    private ?string $etat = null;

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): self
    {
        $this->etat = $etat;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 3000, maxMessage: "La description ne peut pas dépasser 500 mots (~3000 caractères).")]
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

    public function getIdAnimal(): ?int
    {
        return $this->id_animal;
    }

}
