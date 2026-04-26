<?php

namespace App\Repository;

use App\Entity\Zonep;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ZonepRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Zonep::class);
    }

    public function findFiltered(string $search = '', string $status = '', string $sortBy = 'idZone', string $order = 'ASC'): \Doctrine\ORM\QueryBuilder
    {
        $allowedSort = ['idZone', 'nomZone', 'categorieZone', 'status'];
        $allowedOrder = ['ASC', 'DESC'];
        $allowedStatuses = ['Actif', 'En surveillance', 'En maintenance', 'Inactif'];

        $sortBy = in_array($sortBy, $allowedSort, true) ? $sortBy : 'idZone';
        $order = in_array(strtoupper($order), $allowedOrder, true) ? strtoupper($order) : 'ASC';
        $search = trim($search);
        $status = trim($status);

        $qb = $this->createQueryBuilder('z')
            ->select('z')
            ->orderBy('z.' . $sortBy, $order);

        if ('' !== $search) {
            $qb
                ->andWhere('LOWER(z.nomZone) LIKE :search OR LOWER(z.categorieZone) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        if ('' !== $status && in_array($status, $allowedStatuses, true)) {
            $qb
                ->andWhere('z.status = :status')
                ->setParameter('status', $status);
        }

        return $qb;
    }
}