<?php

namespace App\Repository;

use App\Entity\Survzone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SurvzoneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Survzone::class);
    }

    public function findAllSorted(string $sortBy = 'idSurv', string $order = 'ASC'): \Doctrine\ORM\QueryBuilder
    {
        $allowedSort = ['idSurv', 'dateSurv', 'observation'];
        $allowedOrder = ['ASC', 'DESC'];

        $sortBy = in_array($sortBy, $allowedSort) ? $sortBy : 'idSurv';
        $order = in_array(strtoupper($order), $allowedOrder) ? strtoupper($order) : 'ASC';

        return $this->createQueryBuilder('s')
            ->leftJoin('s.zone', 'z')
            ->addSelect('z')
            ->orderBy('s.' . $sortBy, $order);
    }
}