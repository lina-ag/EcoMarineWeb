<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ActionNettoyageRepository;

#[ORM\Entity(repositoryClass: ActionNettoyageRepository::class)]
#[ORM\Table(name: 'action_nettoyage')]
class ActionNettoyage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $date_action = null;

    public function getDate_action(): ?string
    {
        return $this->date_action;
    }

    public function setDate_action(string $date_action): self
    {
        $this->date_action = $date_action;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $lieu = null;

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(string $lieu): self
    {
        $this->lieu = $lieu;
        return $this;
    }

    public function getIdAction(): ?int
    {
        return $this->id_action;
    }

    public function getDateAction(): ?string
    {
        return $this->date_action;
    }

    public function setDateAction(string $date_action): static
    {
        $this->date_action = $date_action;

        return $this;
    }

}
