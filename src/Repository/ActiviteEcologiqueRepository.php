<?php

namespace App\Repository;

use App\Entity\ActiviteEcologique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActiviteEcologique>
 */
class ActiviteEcologiqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActiviteEcologique::class);
    }

    /**
     * @return string[] Dates au format Y-m-d pour toutes les occurrences d'une activité (même nom).
     */
    public function findDatesForReservationByName(string $activityName): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select('a.date_activite')
            ->andWhere('a.nom_activite = :name')
            ->setParameter('name', $activityName)
            ->orderBy('a.date_activite', 'ASC')
            ->getQuery()
            ->getResult();

        $dates = [];
        foreach ($rows as $row) {
            $date = $row['date_activite'] ?? null;
            if ($date instanceof \DateTimeInterface) {
                $dates[] = $date->format('Y-m-d');
            }
        }

        return array_values(array_unique($dates));
    }

    //    /**
    //     * @return ActiviteEcologique[] Returns an array of ActiviteEcologique objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ActiviteEcologique
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
