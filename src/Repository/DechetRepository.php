<?php

namespace App\Repository;

use App\Entity\Dechet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DechetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dechet::class);
    }

    public function findCriticalZones(): array
    {
        $dechets = $this->findAll();
        $zones = [];

        foreach ($dechets as $dechet) {
            $zone = $dechet->getZone() ?: 'Inconnue';
            $score = (int) ($dechet->getPriorityScore() ?? 0);

            if (!isset($zones[$zone])) {
                $zones[$zone] = [
                    'zone' => $zone,
                    'totalSignalements' => 0,
                    'totalScore' => 0,
                    'avgScore' => 0,
                    'criticalCount' => 0,
                    'highCount' => 0,
                ];
            }

            $zones[$zone]['totalSignalements']++;
            $zones[$zone]['totalScore'] += $score;

            if ($score >= 75) {
                $zones[$zone]['criticalCount']++;
            }

            if ($score >= 50) {
                $zones[$zone]['highCount']++;
            }
        }

        $results = [];

        foreach ($zones as $zoneData) {
            $zoneData['avgScore'] = $zoneData['totalSignalements'] > 0
                ? round($zoneData['totalScore'] / $zoneData['totalSignalements'], 1)
                : 0;

            if ($zoneData['criticalCount'] >= 1 || $zoneData['highCount'] >= 2 || $zoneData['avgScore'] >= 55) {
                $results[] = $zoneData;
            }
        }

        usort($results, function ($a, $b) {
            return $b['avgScore'] <=> $a['avgScore'];
        });

        return $results;
    }
}