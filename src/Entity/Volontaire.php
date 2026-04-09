<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\VolontaireRepository;

#[ORM\Entity(repositoryClass: VolontaireRepository::class)]
#[ORM\Table(name: 'volontaire')]
class Volontaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_volontaire = null;

    public function getId_volontaire(): ?int
    {
        return $this->id_volontaire;
    }

    public function setId_volontaire(int $id_volontaire): self
    {
        $this->id_volontaire = $id_volontaire;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
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

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $contact = null;

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(?string $contact): self
    {
        $this->contact = $contact;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id_action = null;

    public function getId_action(): ?int
    {
        return $this->id_action;
    }

    public function setId_action(int $id_action): self
    {
        $this->id_action = $id_action;
        return $this;
    }

    public function getIdVolontaire(): ?int
    {
        return $this->id_volontaire;
    }

    public function getIdAction(): ?int
    {
        return $this->id_action;
    }

    public function setIdAction(int $id_action): static
    {
        $this->id_action = $id_action;

        return $this;
    }

}
