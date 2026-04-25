<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\ActiviteEcologiqueRepository;
use App\Repository\ReservationRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route('/reservation')]
final class ReservationController extends AbstractController
{
    #[Route('/activity-date/{id_activite}', name: 'app_reservation_activity_date', methods: ['GET'])]
    public function activityDate(int $id_activite, ActiviteEcologiqueRepository $activiteEcologiqueRepository): JsonResponse
    {
        $activite = $activiteEcologiqueRepository->find($id_activite);

        if (!$activite || !$activite->getDate_activite()) {
            return $this->json(['dates' => []], Response::HTTP_NOT_FOUND);
        }

        $dates = $activiteEcologiqueRepository->findDatesForReservationByName((string) $activite->getNom_activite());
        if (count($dates) === 0) {
            $dates = [$activite->getDate_activite()->format('Y-m-d')];
        }

        return $this->json(['dates' => $dates]);
    }

    #[Route('/activity-data/{id_activite}', name: 'app_reservation_activity_data', methods: ['GET'])]
    public function activityData(int $id_activite, ActiviteEcologiqueRepository $activiteEcologiqueRepository, ReservationRepository $reservationRepository): JsonResponse
    {
        $activite = $activiteEcologiqueRepository->find($id_activite);

        if (!$activite || !$activite->getDate_activite()) {
            return $this->json(['dates' => [], 'capacity' => null], Response::HTTP_NOT_FOUND);
        }

        $dates = $activiteEcologiqueRepository->findDatesForReservationByName((string) $activite->getNom_activite());
        if (count($dates) === 0) {
            $dates = [$activite->getDate_activite()->format('Y-m-d')];
        }

        $totalCapacity = max(0, (int) ($activite->getCapacite() ?? 0));
        $usedCapacity = $reservationRepository->countReservedPeopleForActivity($activite);
        $remainingCapacity = max(0, $totalCapacity - $usedCapacity);

        if ($totalCapacity <= 0 || $remainingCapacity <= 0) {
            $state = 'full';
            $color = '#dc2626';
        } elseif ($usedCapacity / $totalCapacity >= 0.5) {
            $state = 'warning';
            $color = '#d97706';
        } else {
            $state = 'available';
            $color = '#16a34a';
        }

        return $this->json([
            'dates' => $dates,
            'capacity' => [
                'state' => $state,
                'color' => $color,
                'used' => $usedCapacity,
                'total' => $totalCapacity,
                'remaining' => $remainingCapacity,
            ],
        ]);
    }

    #[Route(name: 'app_reservation_index', methods: ['GET'])]
    public function index(Request $request, ReservationRepository $reservationRepository, ChartBuilderInterface $chartBuilder): Response
    {
        $searchTerm = trim((string) $request->query->get('q', ''));
        $searchField = (string) $request->query->get('field', 'all');
        $statusFilter = (string) $request->query->get('status', 'all');
        $personnesFilter = (string) $request->query->get('personnes', 'all');
        $dateFrom = trim((string) $request->query->get('date_from', ''));
        $dateTo = trim((string) $request->query->get('date_to', ''));
        $sort = (string) $request->query->get('sort', 'id_desc');

        $allReservations = $reservationRepository
            ->createQueryBuilder('r')
            ->leftJoin('r.activiteEcologique', 'a')
            ->addSelect('a')
            ->getQuery()
            ->getResult();

        $reservations = array_values(array_filter($allReservations, function (Reservation $reservation) use ($searchTerm, $searchField, $statusFilter, $personnesFilter, $dateFrom, $dateTo): bool {
            return $this->matchesReservationSearch($reservation, $searchTerm, $searchField)
                && $this->matchesReservationStatus($reservation, $statusFilter)
                && $this->matchesReservationPersonnes($reservation, $personnesFilter)
                && $this->matchesReservationDateRange($reservation, $dateFrom, $dateTo);
        }));

        $hasActiveFilters = $searchTerm !== ''
            || $searchField !== 'all'
            || $statusFilter !== 'all'
            || $personnesFilter !== 'all'
            || $dateFrom !== ''
            || $dateTo !== '';

        if ($hasActiveFilters && count($reservations) === 0 && count($allReservations) > 0) {
            $reservations = $allReservations;
            $searchTerm = '';
            $searchField = 'all';
            $statusFilter = 'all';
            $personnesFilter = 'all';
            $dateFrom = '';
            $dateTo = '';

            $this->addFlash('error', 'Les filtres actifs masquaient vos réservations. La liste complète est affichée.');
        }

        $reservationActivityChart = $this->buildReservationActivityChart($reservations, $chartBuilder);

        usort($reservations, function (Reservation $left, Reservation $right) use ($sort): int {
            return $this->compareReservations($left, $right, $sort);
        });

        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 10;
        $totalItems = count($reservations);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $currentPage = min($page, $totalPages);
        $offset = ($currentPage - 1) * $perPage;
        $paginatedReservations = array_slice($reservations, $offset, $perPage);
        $pageStart = $totalItems > 0 ? $offset + 1 : 0;
        $pageEnd = $totalItems > 0 ? min($offset + $perPage, $totalItems) : 0;

        return $this->render('reservation/index.html.twig', [
            'reservations' => $paginatedReservations,
            'search_term' => $searchTerm,
            'search_field' => $searchField,
            'status_filter' => $statusFilter,
            'personnes_filter' => $personnesFilter,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'sort_by' => $sort,
            'result_count' => $totalItems,
            'page' => $currentPage,
            'total_pages' => $totalPages,
            'page_start' => $pageStart,
            'page_end' => $pageEnd,
            'reservationActivityChart' => $reservationActivityChart,
        ]);
    }

    #[Route('/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Votre réservation a été enregistrée avec succès !');

            if ($request->query->get('source') === 'front') {
                return $this->redirect($this->generateUrl('app_home') . '#slide08', Response::HTTP_SEE_OTHER);
            }

            return $this->redirectToRoute('app_reservation_quiz', [
                'id_reservation' => $reservation->getIdReservation(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/quiz/{id_reservation}', name: 'app_reservation_quiz', methods: ['GET', 'POST'])]
    public function quiz(Reservation $reservation, Request $request, HttpClientInterface $httpClient): Response
    {
        $apiKey = $_ENV['8ARNMqo7uXgU5NTweEmWn46Hvewjcp1PtqfXTKDZTj29'] ?? '';
        $questions = [];

        try {
            $response = $httpClient->request('GET', 'https://api.api-ninjas.com/v1/trivia', [
                'headers' => ['X-Api-Key' => $apiKey],
                'query' => ['category' => 'nature', 'limit' => 3],
            ]);
            $questions = $response->toArray();
        } catch (\Throwable) {
            $questions = [];
        }

        if ($request->isMethod('POST')) {
            $answers = $request->request->all('answers');
            $correctCount = 0;

            foreach ($questions as $index => $question) {
                $userAnswer = strtolower(trim($answers[$index] ?? ''));
                $correct = strtolower(trim($question['answer'] ?? ''));
                if ($userAnswer === $correct) {
                    $correctCount++;
                }
            }

            $badge = match(true) {
                $correctCount === count($questions) => 'gold',
                $correctCount >= 2 => 'silver',
                default => 'bronze',
            };

            return $this->render('reservation/quiz_result.html.twig', [
                'reservation' => $reservation,
                'questions' => $questions,
                'answers' => $answers,
                'correct_count' => $correctCount,
                'total' => count($questions),
                'badge' => $badge,
            ]);
        }

        return $this->render('reservation/quiz.html.twig', [
            'reservation' => $reservation,
            'questions' => $questions,
        ]);
    }

    #[Route('/export-all/pdf', name: 'app_reservation_export_pdf', methods: ['GET'])]
    public function exportAllPdf(ReservationRepository $reservationRepository): Response
    {
        $reservations = $reservationRepository
            ->createQueryBuilder('r')
            ->leftJoin('r.activiteEcologique', 'a')
            ->addSelect('a')
            ->orderBy('r.date_reservation', 'DESC')
            ->getQuery()
            ->getResult();

        $html = $this->renderView('reservation/pdf_list.html.twig', [
            'reservations' => $reservations,
            'generatedAt' => new \DateTimeImmutable(),
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="reservations_%s.pdf"', (new \DateTimeImmutable())->format('Y-m-d')),
            ]
        );
    }

    #[Route('/{id_reservation}', name: 'app_reservation_show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id_reservation}/pdf', name: 'app_reservation_pdf', methods: ['GET'])]
    public function exportPdf(Reservation $reservation): Response
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $html = $this->renderView('reservation/pdf.html.twig', [
            'reservation' => $reservation,
            'generatedAt' => new \DateTimeImmutable(),
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = sprintf('reservation_%d.pdf', $reservation->getIdReservation() ?? 0);

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ]
        );
    }

    #[Route('/{id_reservation}/edit', name: 'app_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'La réservation a été mise à jour avec succès.');

            return $this->redirectToRoute('app_reservation_index', [
                'q' => '',
                'field' => 'all',
                'status' => 'all',
                'personnes' => 'all',
                'date_from' => '',
                'date_to' => '',
                'sort' => 'id_desc',
                'page' => 1,
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id_reservation}', name: 'app_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reservation->getId_reservation(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'La réservation a été supprimée avec succès.');
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide. Suppression annulée.');
        }

        return $this->redirectToRoute('app_reservation_index', [
            'q' => '',
            'field' => 'all',
            'status' => 'all',
            'personnes' => 'all',
            'date_from' => '',
            'date_to' => '',
            'sort' => 'id_desc',
            'page' => 1,
        ], Response::HTTP_SEE_OTHER);
    }

    private function buildReservationActivityChart(array $reservations, ChartBuilderInterface $chartBuilder): Chart
    {
        $activityCounts = [];

        foreach ($reservations as $reservation) {
            if (!$reservation instanceof Reservation) {
                continue;
            }

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
                    ],
                ],
            ],
            'cutout' => '62%',
        ]);

        return $chart;
    }

    private function matchesReservationSearch(Reservation $reservation, string $searchTerm, string $searchField): bool
    {
        $tokens = preg_split('/\s+/', $this->normalizeSearchValue($searchTerm), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($tokens === []) {
            return true;
        }

        $searchableParts = [];

        if ($searchField === 'all') {
            $searchableParts = array_filter([
                $this->normalizeSearchValue((string) $reservation->getIdReservation()),
                $this->normalizeSearchValue((string) $reservation->getNom()),
                $this->normalizeSearchValue((string) ($reservation->getActiviteEcologique()?->getNomActivite() ?? '')),
                $this->normalizeSearchValue($reservation->getDateReservation()?->format('Y-m-d') ?? ''),
                $this->normalizeSearchValue((string) $reservation->getEmail()),
                $this->normalizeSearchValue((string) $reservation->getNombrePersonnes()),
            ]);
        } else {
            $fieldValue = match ($searchField) {
                'id' => (string) $reservation->getIdReservation(),
                'nom' => (string) $reservation->getNom(),
                'activite' => (string) ($reservation->getActiviteEcologique()?->getNomActivite() ?? ''),
                'date' => $reservation->getDateReservation()?->format('Y-m-d') ?? '',
                'email' => (string) $reservation->getEmail(),
                'nombre' => (string) $reservation->getNombrePersonnes(),
                default => '',
            };

            $searchableParts = $fieldValue === '' ? [] : [$this->normalizeSearchValue($fieldValue)];
        }

        foreach ($tokens as $token) {
            $matched = false;

            foreach ($searchableParts as $part) {
                if ($part !== '' && str_starts_with($part, $token)) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                return false;
            }
        }

        return true;
    }

    private function matchesReservationStatus(Reservation $reservation, string $statusFilter): bool
    {
        if ($statusFilter === 'all') {
            return true;
        }

        return $this->getReservationStatus($reservation) === $statusFilter;
    }

    private function matchesReservationPersonnes(Reservation $reservation, string $personnesFilter): bool
    {
        if ($personnesFilter === 'all') {
            return true;
        }

        $count = (int) $reservation->getNombrePersonnes();

        return match ($personnesFilter) {
            '1-5' => $count >= 1 && $count <= 5,
            '6-10' => $count >= 6 && $count <= 10,
            '11-50' => $count >= 11 && $count <= 50,
            '50+' => $count > 50,
            default => true,
        };
    }

    private function matchesReservationDateRange(Reservation $reservation, string $dateFrom, string $dateTo): bool
    {
        $reservationDate = $reservation->getDateReservation();

        if (!$reservationDate) {
            return false;
        }

        $reservationDateValue = $reservationDate->format('Y-m-d');

        if ($dateFrom !== '' && $reservationDateValue < $dateFrom) {
            return false;
        }

        if ($dateTo !== '' && $reservationDateValue > $dateTo) {
            return false;
        }

        return true;
    }

    private function compareReservations(Reservation $left, Reservation $right, string $sort): int
    {
        return match ($sort) {
            'id_asc' => ($left->getIdReservation() ?? 0) <=> ($right->getIdReservation() ?? 0),
            'id_desc' => ($right->getIdReservation() ?? 0) <=> ($left->getIdReservation() ?? 0),
            'nom_asc' => strcmp($this->normalizeSearchValue((string) $left->getNom()), $this->normalizeSearchValue((string) $right->getNom())),
            'nom_desc' => strcmp($this->normalizeSearchValue((string) $right->getNom()), $this->normalizeSearchValue((string) $left->getNom())),
            'date_asc' => $this->compareReservationDates($left, $right),
            'nombre_asc' => ($left->getNombrePersonnes() ?? 0) <=> ($right->getNombrePersonnes() ?? 0),
            'nombre_desc' => ($right->getNombrePersonnes() ?? 0) <=> ($left->getNombrePersonnes() ?? 0),
            default => $this->compareReservationDates($right, $left),
        };
    }

    private function compareReservationDates(Reservation $left, Reservation $right): int
    {
        $leftDate = $left->getDateReservation();
        $rightDate = $right->getDateReservation();

        if (!$leftDate && !$rightDate) {
            return 0;
        }

        if (!$leftDate) {
            return -1;
        }

        if (!$rightDate) {
            return 1;
        }

        return $leftDate <=> $rightDate;
    }

    private function getReservationStatus(Reservation $reservation): string
    {
        if (!$reservation->getActiviteEcologique()) {
            return 'cancelled';
        }

        $dateReservation = $reservation->getDateReservation();

        if (!$dateReservation) {
            return 'cancelled';
        }

        return $dateReservation->format('Y-m-d') > (new \DateTimeImmutable('today'))->format('Y-m-d') ? 'pending' : 'confirmed';
    }

    private function normalizeSearchValue(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);

        return $ascii !== false ? $ascii : $normalized;
    }
}