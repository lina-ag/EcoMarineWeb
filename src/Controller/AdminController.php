<?php

namespace App\Controller;

use App\Repository\ActiviteEcologiqueRepository;
use App\Repository\ActionNettoyageRepository;
use App\Repository\BiodiversiteRepository;
use App\Repository\DechetRepository;
use App\Repository\DetectionDroneRepository;
use App\Repository\EvenementRepository;
use App\Repository\FauneMarineRepository;
use App\Repository\MissionDroneRepository;
use App\Repository\ObservationRepository;
use App\Repository\ReservationRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\VolontaireRepository;
use App\Repository\ZonePlageRepository;
use App\Repository\ZonepRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(
        ReservationRepository $reservationRepository,
        UtilisateurRepository $utilisateurRepository,
        ActiviteEcologiqueRepository $activiteEcologiqueRepository,
        FauneMarineRepository $fauneMarineRepository,
        EvenementRepository $evenementRepository,
        DechetRepository $dechetRepository,
        BiodiversiteRepository $biodiversiteRepository,
        ObservationRepository $observationRepository,
        MissionDroneRepository $missionDroneRepository,
        DetectionDroneRepository $detectionDroneRepository,
        ActionNettoyageRepository $actionNettoyageRepository,
        VolontaireRepository $volontaireRepository,
        ZonePlageRepository $zonePlageRepository,
        ZonepRepository $zonepRepository,
    ): Response
    {
        $now = new \DateTimeImmutable('now');
        $startCurrentMonth = $now->modify('first day of this month')->setTime(0, 0);
        $startPreviousMonth = $startCurrentMonth->modify('-1 month');

        $totalReservations = $reservationRepository->count([]);
        $totalVisitors = $utilisateurRepository->count([]);
        $totalActivities = $activiteEcologiqueRepository->count([]);
        $totalSpecies = $fauneMarineRepository->count([]);

        $reservationsCurrentMonth = $this->countReservationsBetween(
            $reservationRepository,
            $startCurrentMonth,
            $now
        );
        $reservationsPreviousMonth = $this->countReservationsBetween(
            $reservationRepository,
            $startPreviousMonth,
            $startCurrentMonth
        );

        $newVisitorsCurrentMonth = $this->countUsersCreatedBetween(
            $utilisateurRepository,
            $startCurrentMonth,
            $now
        );
        $newVisitorsPreviousMonth = $this->countUsersCreatedBetween(
            $utilisateurRepository,
            $startPreviousMonth,
            $startCurrentMonth
        );

        $newActivitiesCurrentMonth = $this->countActivitiesBetween(
            $activiteEcologiqueRepository,
            $startCurrentMonth,
            $now
        );

        $latestReservations = $reservationRepository->createQueryBuilder('r')
            ->leftJoin('r.activiteEcologique', 'a')
            ->addSelect('a')
            ->orderBy('r.date_reservation', 'DESC')
            ->addOrderBy('r.id_reservation', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $totalEvents = $evenementRepository->count([]);
        $totalWasteRecords = $dechetRepository->count([]);
        $totalBiodiversity = $biodiversiteRepository->count([]);
        $totalObservations = $observationRepository->count([]);
        $totalDroneMissions = $missionDroneRepository->count([]);
        $totalDroneDetections = $detectionDroneRepository->count([]);
        $totalCleaningActions = $actionNettoyageRepository->count([]);
        $totalVolunteers = $volontaireRepository->count([]);
        $totalBeachZones = $zonePlageRepository->count([]);
        $totalProtectedZones = $zonepRepository->count([]);

        $totalWasteQuantity = $this->sumWasteQuantity($dechetRepository);
        $wasteByZone = $this->buildWasteByZoneChart($dechetRepository);

        $reservationsByMonth = $this->buildMonthlyCountSeries(
            $reservationRepository,
            'r',
            'date_reservation'
        );
        $observationsByMonth = $this->buildMonthlyCountSeries(
            $observationRepository,
            'o',
            'date_observation'
        );

        $domainBlocks = [
            [
                'title' => 'Tourisme',
                'accent' => 'cyan',
                'items' => [
                    ['label' => 'Reservations', 'value' => $totalReservations],
                    ['label' => 'Activites', 'value' => $totalActivities],
                    ['label' => 'Evenements', 'value' => $totalEvents],
                ],
            ],
            [
                'title' => 'Environnement',
                'accent' => 'success',
                'items' => [
                    ['label' => 'Especes suivies', 'value' => $totalSpecies],
                    ['label' => 'Biodiversite', 'value' => $totalBiodiversity],
                    ['label' => 'Observations', 'value' => $totalObservations],
                    ['label' => 'Dechets (kg)', 'value' => number_format($totalWasteQuantity, 1, ',', ' ')],
                ],
            ],
            [
                'title' => 'Drone',
                'accent' => 'purple',
                'items' => [
                    ['label' => 'Missions', 'value' => $totalDroneMissions],
                    ['label' => 'Detections', 'value' => $totalDroneDetections],
                    ['label' => 'Zones surveillees', 'value' => $totalBeachZones + $totalProtectedZones],
                ],
            ],
            [
                'title' => 'Utilisateurs',
                'accent' => 'magenta',
                'items' => [
                    ['label' => 'Comptes', 'value' => $totalVisitors],
                    ['label' => 'Volontaires', 'value' => $totalVolunteers],
                    ['label' => 'Nouveaux ce mois', 'value' => $newVisitorsCurrentMonth],
                ],
            ],
        ];

        return $this->render('admin/index.html.twig', [
            'kpi' => [
                'reservations' => $totalReservations,
                'visitors' => $totalVisitors,
                'waste_quantity' => $totalWasteQuantity,
                'drone_detections' => $totalDroneDetections,
            ],
            'metrics' => [
                'reservations_current_month' => $reservationsCurrentMonth,
                'reservations_growth' => $this->computeGrowthRate($reservationsCurrentMonth, $reservationsPreviousMonth),
                'new_visitors_current_month' => $newVisitorsCurrentMonth,
                'new_visitors_growth' => $this->computeGrowthRate($newVisitorsCurrentMonth, $newVisitorsPreviousMonth),
                'new_activities_current_month' => $newActivitiesCurrentMonth,
                'waste_records' => $totalWasteRecords,
                'drone_missions' => $totalDroneMissions,
            ],
            'latest_reservations' => $latestReservations,
            'charts' => [
                'reservations_by_month' => $reservationsByMonth,
                'observations_by_month' => $observationsByMonth,
                'waste_by_zone' => $wasteByZone,
            ],
            'domain_blocks' => $domainBlocks,
        ]);
    }

    private function sumWasteQuantity(DechetRepository $dechetRepository): float
    {
        $value = $dechetRepository->createQueryBuilder('d')
            ->select('COALESCE(SUM(d.quantite), 0)')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $value;
    }

    /**
     * @return array<int, array{label: string, value: int, height: float}>
     */
    private function buildMonthlyCountSeries(
        object $repository,
        string $alias,
        string $dateField,
        int $months = 6
    ): array {
        $now = new \DateTimeImmutable('now');
        $start = $now->modify(sprintf('first day of -%d months', $months - 1))->setTime(0, 0);

        $points = [];
        for ($i = 0; $i < $months; $i++) {
            $monthDate = $start->modify(sprintf('+%d months', $i));
            $points[$monthDate->format('Y-m')] = [
                'label' => $monthDate->format('m/Y'),
                'value' => 0,
                'height' => 0.0,
            ];
        }

        $rows = $repository->createQueryBuilder($alias)
            ->select(sprintf('%s.%s AS eventDate', $alias, $dateField))
            ->where(sprintf('%s.%s >= :start', $alias, $dateField))
            ->setParameter('start', $start)
            ->getQuery()
            ->getArrayResult();

        foreach ($rows as $row) {
            if (!isset($row['eventDate']) || !$row['eventDate']) {
                continue;
            }

            $eventDate = $row['eventDate'] instanceof \DateTimeInterface
                ? \DateTimeImmutable::createFromInterface($row['eventDate'])
                : new \DateTimeImmutable((string) $row['eventDate']);

            $key = $eventDate->format('Y-m');
            if (isset($points[$key])) {
                $points[$key]['value']++;
            }
        }

        $maxValue = 0;
        foreach ($points as $point) {
            if ($point['value'] > $maxValue) {
                $maxValue = $point['value'];
            }
        }

        foreach ($points as &$point) {
            $point['height'] = $maxValue > 0 ? round(($point['value'] / $maxValue) * 100, 1) : 0.0;
        }
        unset($point);

        return array_values($points);
    }

    /**
     * @return array<int, array{label: string, value: float, height: float}>
     */
    private function buildWasteByZoneChart(DechetRepository $dechetRepository): array
    {
        $rows = $dechetRepository->createQueryBuilder('d')
            ->select('d.zone AS zone, COALESCE(SUM(d.quantite), 0) AS totalQty')
            ->groupBy('d.zone')
            ->orderBy('totalQty', 'DESC')
            ->setMaxResults(6)
            ->getQuery()
            ->getArrayResult();

        $maxValue = 0.0;
        foreach ($rows as $row) {
            $value = (float) $row['totalQty'];
            if ($value > $maxValue) {
                $maxValue = $value;
            }
        }

        $result = [];
        foreach ($rows as $row) {
            $value = (float) $row['totalQty'];
            $result[] = [
                'label' => (string) ($row['zone'] ?? 'N/A'),
                'value' => round($value, 1),
                'height' => $maxValue > 0 ? round(($value / $maxValue) * 100, 1) : 0.0,
            ];
        }

        return $result;
    }

    private function countReservationsBetween(
        ReservationRepository $reservationRepository,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): int {
        return (int) $reservationRepository->createQueryBuilder('r')
            ->select('COUNT(r.id_reservation)')
            ->where('r.date_reservation >= :start')
            ->andWhere('r.date_reservation < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function countUsersCreatedBetween(
        UtilisateurRepository $utilisateurRepository,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): int {
        return (int) $utilisateurRepository->createQueryBuilder('u')
            ->select('COUNT(u.id_utilisateur)')
            ->where('u.created_at IS NOT NULL')
            ->andWhere('u.created_at >= :start')
            ->andWhere('u.created_at < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function countActivitiesBetween(
        ActiviteEcologiqueRepository $activiteEcologiqueRepository,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): int {
        return (int) $activiteEcologiqueRepository->createQueryBuilder('a')
            ->select('COUNT(a.id_activite)')
            ->where('a.date_activite >= :start')
            ->andWhere('a.date_activite < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function computeGrowthRate(int $current, int $previous): string
    {
        if ($previous <= 0) {
            return $current > 0 ? '+100.0%' : '0.0%';
        }

        $value = (($current - $previous) / $previous) * 100;
        $prefix = $value >= 0 ? '+' : '';

        return sprintf('%s%.1f%%', $prefix, $value);
    }
}
