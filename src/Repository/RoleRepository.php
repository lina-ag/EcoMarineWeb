<?php

namespace App\Repository;

use App\Entity\Role;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Role>
 */
class RoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Role::class);
    }

    /**
     * Trouver un rôle par son nom
     */
    public function findByNomRole(string $nomRole): ?Role
    {
        return $this->findOneBy(['nomRole' => $nomRole]);
    }

    /**
     * Récupérer tous les rôles triés par niveau
     */
    public function findAllOrderedByNiveau(): array
    {
        return $this->findBy([], ['niveau' => 'ASC']);
    }
}