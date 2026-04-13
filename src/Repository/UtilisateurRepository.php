<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 */
class UtilisateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    /**
     * Recherche des utilisateurs selon les critères (nom, prénom, rôle)
     */
    public function searchUsers(?string $search = null, ?string $role = null): array
    {
        $qb = $this->createQueryBuilder('u');
        
        // Filtre par rôle
        if ($role && $role !== 'all') {
            $qb->andWhere('u.role = :role')
               ->setParameter('role', $role);
        }
        
        // Filtre par recherche (nom ou prénom)
        if ($search && !empty($search)) {
            $qb->andWhere('u.nom LIKE :search OR u.prenom LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        return $qb->orderBy('u.id_utilisateur', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
    
    /**
     * Récupérer tous les rôles distincts
     */
    public function getDistinctRoles(): array
    {
        $qb = $this->createQueryBuilder('u')
            ->select('DISTINCT u.role')
            ->orderBy('u.role', 'ASC');
        
        $result = $qb->getQuery()->getResult();
        
        $roles = [];
        foreach ($result as $item) {
            $roles[] = $item['role'];
        }
        
        return $roles;
    }
    
    /**
     * Compter les utilisateurs par rôle
     */
    public function countByRole(string $role): int
    {
        return $this->count(['role' => $role]);
    }
    
    /**
     * Récupérer les statistiques complètes
     */
    public function getStats(): array
    {
        $total = $this->count([]);
        $admin = $this->countByRole('admin');
        $chercheur = $this->countByRole('chercheur');
        $utilisateur = $this->countByRole('utilisateur');
        
        return [
            'total' => $total,
            'admin' => $admin,
            'chercheur' => $chercheur,
            'utilisateur' => $utilisateur,
        ];
    }
}