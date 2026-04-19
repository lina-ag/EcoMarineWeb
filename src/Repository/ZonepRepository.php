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

        $sortBy = in_array($sortBy, $allowedSort) ? $sortBy : 'idZone';
        $order = in_array(strtoupper($order), $allowedOrder) ? strtoupper($order) : 'ASC';

        $qb = $this->createQueryBuilder('z')
            ->select('z')
            ->orderBy('z.' . $sortBy, $order);

        if (!empty($search)) {
            $qb->andWhere('z.nomZone LIKE :search OR z.categorieZone LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if (!empty($status)) {
            $qb->andWhere('z.status = :status')
               ->setParameter('status', $status);
        }

        return $qb;
    }
}