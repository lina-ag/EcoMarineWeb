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
use App\Repository\SurvzoneRepository;
use App\Repository\ZonepRepository;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
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
        SurvzoneRepository $survzoneRepository,
        ChartBuilderInterface $chartBuilder,
    ): Response {
        $now = new \DateTimeImmutable('now');
        $startCurrentMonth = $now->modify('first day of this month')->setTime(0, 0);
        $startPreviousMonth = $startCurrentMonth->modify('-1 month');

        $totalReservations = $reservationRepository->count([]);
        $totalVisitors = $utilisateurRepository->count([]);
        $totalActivities = $activiteEcologiqueRepository->count([]);
        $totalSpecies = $fauneMarineRepository->count([]);

        $reservationsCurrentMonth = $this->countReservationsBetween($reservationRepository, $startCurrentMonth, $now);
        $reservationsPreviousMonth = $this->countReservationsBetween($reservationRepository, $startPreviousMonth, $startCurrentMonth);
        $newVisitorsCurrentMonth = $this->countUsersCreatedBetween($utilisateurRepository, $startCurrentMonth, $now);
        $newVisitorsPreviousMonth = $this->countUsersCreatedBetween($utilisateurRepository, $startPreviousMonth, $startCurrentMonth);
        $newActivitiesCurrentMonth = $this->countActivitiesBetween($activiteEcologiqueRepository, $startCurrentMonth, $now);

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
        $totalSurveillances = $survzoneRepository->count([]);
        $totalWasteQuantity = $this->sumWasteQuantity($dechetRepository);

        // Nouvelles stats zones
        $zonesByStatus = $this->buildZonesByStatus($zonepRepository);
        $surveillancesByMonth = $this->buildMonthlyCountSeries($survzoneRepository, 's', 'dateSurv');
        $zonesWithoutSurveillance = $this->findZonesWithoutRecentSurveillance($zonepRepository);
        $mostSurveilledZones = $this->buildMostSurveilledZones($survzoneRepository);

        $wasteByZone = $this->buildWasteByZoneChart($dechetRepository);
        $reservationsByMonth = $this->buildMonthlyCountSeries($reservationRepository, 'r', 'date_reservation');
        $observationsByMonth = $this->buildMonthlyCountSeries($observationRepository, 'o', 'date_observation');
        $reservationActivityChart = $this->buildReservationActivityChart($reservationRepository, $chartBuilder);

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
                    ['label' => 'Zones protegees', 'value' => $totalProtectedZones],
                    ['label' => 'Surveillances', 'value' => $totalSurveillances],
                ],
            ],
            [
                'title' => 'Zones Marines',
                'accent' => 'cyan',
                'items' => [
                    ['label' => 'Total zones', 'value' => $totalProtectedZones],
                    ['label' => 'Zones actives', 'value' => $zonesByStatus['Actif'] ?? 0],
                    ['label' => 'En surveillance', 'value' => $zonesByStatus['En surveillance'] ?? 0],
                    ['label' => 'En maintenance', 'value' => $zonesByStatus['En maintenance'] ?? 0],
                    ['label' => 'Inactives', 'value' => $zonesByStatus['Inactif'] ?? 0],
                    ['label' => 'Sans surv. 30j', 'value' => count($zonesWithoutSurveillance)],
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
                'protected_zones' => $totalProtectedZones,
                'surveillances' => $totalSurveillances,
            ],
            'metrics' => [
                'reservations_current_month' => $reservationsCurrentMonth,
                'reservations_growth' => $this->computeGrowthRate($reservationsCurrentMonth, $reservationsPreviousMonth),
                'new_visitors_current_month' => $newVisitorsCurrentMonth,
                'new_visitors_growth' => $this->computeGrowthRate($newVisitorsCurrentMonth, $newVisitorsPreviousMonth),
                'new_activities_current_month' => $newActivitiesCurrentMonth,
                'waste_records' => $totalWasteRecords,
                'drone_missions' => $totalDroneMissions,
                'protected_zones' => $totalProtectedZones,
                'surveillances' => $totalSurveillances,
            ],
            'latest_reservations' => $latestReservations,
            'charts' => [
                'reservations_by_month' => $reservationsByMonth,
                'observations_by_month' => $observationsByMonth,
                'waste_by_zone' => $wasteByZone,
                'surveillances_by_month' => $surveillancesByMonth,
                'zones_by_status' => $zonesByStatus,
                'most_surveilled_zones' => $mostSurveilledZones,
            ],
            'reservationActivityChart' => $reservationActivityChart,
            'domain_blocks' => $domainBlocks,
            'zones_without_surveillance' => $zonesWithoutSurveillance,
        ]);
    }

    private function buildReservationActivityChart(ReservationRepository $reservationRepository, ChartBuilderInterface $chartBuilder): Chart
    {
        $reservations = $reservationRepository->createQueryBuilder('r')
            ->leftJoin('r.activiteEcologique', 'a')
            ->addSelect('a')
            ->getQuery()
            ->getResult();

        $activityCounts = [];

        foreach ($reservations as $reservation) {
            $activityName = trim((string) ($reservation->getActiviteEcologique()?->getNomActivite() ?? 'Sans activité'));
            if ($activityName === '') {
                $activityName = 'Sans activité';
            }

            $activityCounts[$activityName] = ($activityCounts[$activityName] ?? 0) + 1;
        }

        arsort($activityCounts);
        $activityCounts = array_slice($activityCounts, 0, 8, true);

        $chart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $chart->setData([
            'labels' => array_keys($activityCounts),
            'datasets' => [[
                'label' => 'Réservations par activité',
                'data' => array_values($activityCounts),
                'backgroundColor' => [
                    '#0f766e',
                    '#2563eb',
                    '#7c3aed',
                    '#f59e0b',
                    '#14b8a6',
                    '#ef4444',
                    '#8b5cf6',
                    '#22c55e',
                ],
                'borderColor' => '#ffffff',
                'borderWidth' => 2,
                'hoverOffset' => 10,
            ]],
        ]);
        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 16,
                        'color' => '#334155',
                    ],
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(248, 250, 252, 0.96)',
                    'titleColor' => '#0f172a',
                    'bodyColor' => '#334155',
                    'borderColor' => 'rgba(148, 163, 184, 0.4)',
                    'borderWidth' => 1,
                ],
            ],
            'elements' => [
                'arc' => [
                    'borderColor' => 'rgba(255, 255, 255, 0.9)',
                    'borderWidth' => 2,
                ],
            ],
            'cutout' => '62%',
        ]);

        return $chart;
    }

    private function buildZonesByStatus(ZonepRepository $zonepRepository): array
    {
        $rows = $zonepRepository->createQueryBuilder('z')
            ->select('z.status, COUNT(z.idZone) AS total')
            ->groupBy('z.status')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['status']] = (int) $row['total'];
        }
        return $result;
    }

    private function findZonesWithoutRecentSurveillance(ZonepRepository $zonepRepository): array
    {
        $threshold = new \DateTimeImmutable('-30 days');

        $zonesWithRecentSurv = $zonepRepository->createQueryBuilder('z')
            ->select('IDENTITY(s.zone)')
            ->join('App\Entity\Survzone', 's', 'WITH', 's.zone = z.idZone')
            ->where('s.dateSurv >= :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getSingleColumnResult();

        $qb = $zonepRepository->createQueryBuilder('z')
            ->select('z');

        if (!empty($zonesWithRecentSurv)) {
            $qb->where('z.idZone NOT IN (:ids)')
               ->setParameter('ids', $zonesWithRecentSurv);
        }

        return $qb->getQuery()->getResult();
    }

    private function buildMostSurveilledZones(SurvzoneRepository $survzoneRepository): array
    {
        $rows = $survzoneRepository->createQueryBuilder('s')
            ->select('z.nomZone AS nom, COUNT(s.idSurv) AS total')
            ->join('s.zone', 'z')
            ->groupBy('z.idZone')
            ->orderBy('total', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getArrayResult();

        $maxValue = 0;
        foreach ($rows as $row) {
            if ((int)$row['total'] > $maxValue) $maxValue = (int)$row['total'];
        }

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'label' => $row['nom'],
                'value' => (int) $row['total'],
                'height' => $maxValue > 0 ? round(((int)$row['total'] / $maxValue) * 100, 1) : 0.0,
            ];
        }
        return $result;
    }

    private function sumWasteQuantity(DechetRepository $dechetRepository): float
    {
        $value = $dechetRepository->createQueryBuilder('d')
            ->select('COALESCE(SUM(d.quantite), 0)')
            ->getQuery()
            ->getSingleScalarResult();
        return (float) $value;
    }

    private function buildMonthlyCountSeries(object $repository, string $alias, string $dateField, int $months = 6): array
    {
        $now = new \DateTimeImmutable('now');
        $start = $now->modify(sprintf('first day of -%d months', $months - 1))->setTime(0, 0);

        $points = [];
        for ($i = 0; $i < $months; $i++) {
            $monthDate = $start->modify(sprintf('+%d months', $i));
            $points[$monthDate->format('Y-m')] = ['label' => $monthDate->format('m/Y'), 'value' => 0, 'height' => 0.0];
        }

        $rows = $repository->createQueryBuilder($alias)
            ->select(sprintf('%s.%s AS eventDate', $alias, $dateField))
            ->where(sprintf('%s.%s >= :start', $alias, $dateField))
            ->setParameter('start', $start)
            ->getQuery()
            ->getArrayResult();

        foreach ($rows as $row) {
            if (!isset($row['eventDate']) || !$row['eventDate']) continue;
            $eventDate = $row['eventDate'] instanceof \DateTimeInterface
                ? \DateTimeImmutable::createFromInterface($row['eventDate'])
                : new \DateTimeImmutable((string) $row['eventDate']);
            $key = $eventDate->format('Y-m');
            if (isset($points[$key])) $points[$key]['value']++;
        }

        $maxValue = 0;
        foreach ($points as $point) {
            if ($point['value'] > $maxValue) $maxValue = $point['value'];
        }
        foreach ($points as &$point) {
            $point['height'] = $maxValue > 0 ? round(($point['value'] / $maxValue) * 100, 1) : 0.0;
        }
        unset($point);

        return array_values($points);
    }

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
            if ($value > $maxValue) $maxValue = $value;
        }

        $result = [];
        foreach ($rows as $row) {
            $value = (float) $row['totalQty'];
            $result[] = ['label' => (string) ($row['zone'] ?? 'N/A'), 'value' => round($value, 1), 'height' => $maxValue > 0 ? round(($value / $maxValue) * 100, 1) : 0.0];
        }
        return $result;
    }

    private function countReservationsBetween(ReservationRepository $reservationRepository, \DateTimeImmutable $start, \DateTimeImmutable $end): int
    {
        return (int) $reservationRepository->createQueryBuilder('r')
            ->select('COUNT(r.id_reservation)')
            ->where('r.date_reservation >= :start')
            ->andWhere('r.date_reservation < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function countUsersCreatedBetween(UtilisateurRepository $utilisateurRepository, \DateTimeImmutable $start, \DateTimeImmutable $end): int
    {
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

    private function countActivitiesBetween(ActiviteEcologiqueRepository $activiteEcologiqueRepository, \DateTimeImmutable $start, \DateTimeImmutable $end): int
    {
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
        if ($previous <= 0) return $current > 0 ? '+100.0%' : '0.0%';
        $value = (($current - $previous) / $previous) * 100;
        return sprintf('%s%.1f%%', $value >= 0 ? '+' : '', $value);
    }
}