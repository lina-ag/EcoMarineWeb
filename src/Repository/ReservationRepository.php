<?php

namespace App\Repository;

use App\Entity\ActiviteEcologique;
use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function countReservedPeopleForActivity(ActiviteEcologique $activity, ?int $excludedReservationId = null): int
    {
        $queryBuilder = $this->createQueryBuilder('r')
            ->select('COALESCE(SUM(r.nombre_personnes), 0)')
            ->andWhere('r.activiteEcologique = :activity')
            ->setParameter('activity', $activity);

        if ($excludedReservationId !== null) {
            $queryBuilder
                ->andWhere('r.id_reservation <> :excludedReservationId')
                ->setParameter('excludedReservationId', $excludedReservationId);
        }

        return (int) $queryBuilder
            ->getQuery()
            ->getSingleScalarResult();
    }

    //    /**
    //     * @return Reservation[] Returns an array of Reservation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Reservation
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
