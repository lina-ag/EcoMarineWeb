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

    public function findBySearchSorted(string $search, string $sortBy = 'idSurv', string $order = 'ASC'): \Doctrine\ORM\QueryBuilder
    {
        $allowedSort = ['idSurv', 'dateSurv', 'observation'];
        $allowedOrder = ['ASC', 'DESC'];

        $sortBy = \in_array($sortBy, $allowedSort, true) ? $sortBy : 'idSurv';
        $order = \in_array(strtoupper($order), $allowedOrder, true) ? strtoupper($order) : 'ASC';

        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.zone', 'z')
            ->addSelect('z')
            ->orderBy('s.' . $sortBy, $order);

        if ('' !== trim($search)) {
            $qb
                ->andWhere('LOWER(s.observation) LIKE :search OR LOWER(z.nomZone) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower(trim($search)).'%');

            if (\preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($search))) {
                $qb
                    ->orWhere('s.dateSurv = :searchDate')
                    ->setParameter('searchDate', new \DateTimeImmutable(trim($search)));
            }
        }

        return $qb;
    }

    /**
     * @return array<int, array{value: string, text: string}>
     */
    public function findAutocompleteSuggestions(string $search, int $limit = 8): array
    {
        $search = trim($search);
        if ('' === $search) {
            return [];
        }

        $needle = '%'.mb_strtolower($search).'%';

        $zoneRows = $this->createQueryBuilder('s')
            ->select('DISTINCT z.nomZone AS suggestion')
            ->leftJoin('s.zone', 'z')
            ->where('LOWER(z.nomZone) LIKE :search')
            ->setParameter('search', $needle)
            ->setMaxResults($limit)
            ->getQuery()
            ->getScalarResult();

        $observationRows = $this->createQueryBuilder('s')
            ->select('DISTINCT s.observation AS suggestion')
            ->where('LOWER(s.observation) LIKE :search')
            ->setParameter('search', $needle)
            ->setMaxResults($limit)
            ->getQuery()
            ->getScalarResult();

        $suggestions = [];

        foreach ([$zoneRows, $observationRows] as $rows) {
            foreach ($rows as $row) {
                $text = trim((string) ($row['suggestion'] ?? ''));
                if ('' === $text) {
                    continue;
                }

                $suggestions[$text] = [
                    'value' => $text,
                    'text' => $text,
                ];
            }
        }

        return array_slice(array_values($suggestions), 0, $limit);
    }
}
