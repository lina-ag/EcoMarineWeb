<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\FauneMarineRepository;

#[ORM\Entity(repositoryClass: FauneMarineRepository::class)]
#[ORM\Table(name: 'faune_marine')]
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

    #[ORM\Column(type: 'string', nullable: false)]
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
