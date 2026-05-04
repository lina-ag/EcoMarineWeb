<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\ActiviteEcologiqueRepository;
use App\Repository\ReservationRepository;
use App\Service\GroqReportService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

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
        $perPage = 3;
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
    public function new(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Votre réservation a été enregistrée avec succès !');

            try {
                $activityName = 'Activite EcoMarine';
                $activity = $reservation->getActiviteEcologique();
                if ($activity !== null) {
                    if (method_exists($activity, 'getNomActivite')) {
                        $activityName = (string) $activity->getNomActivite();
                    } elseif (method_exists($activity, 'getNom_activite')) {
                        $activityName = (string) $activity->getNom_activite();
                    }
                }

                $fromEmail = trim((string) ($_ENV['MAIL_FROM'] ?? $_SERVER['MAIL_FROM'] ?? $_ENV['MAILER_USER'] ?? $_SERVER['MAILER_USER'] ?? ''));

                if ($fromEmail === '') {
                    $fromEmail = 'ecomarine.reservation@outlook.com';
                }

                $email = (new Email())
                    ->from($fromEmail)
                    ->to((string) $reservation->getEmail())
                    ->subject('Confirmation de votre réservation EcoMarine')
                    ->html($this->renderView('emails/reservation_confirmation.html.twig', [
                        'reservation' => $reservation,
                        'activityName' => $activityName,
                        'date' => $reservation->getDateReservation()?->format('d/m/Y') ?? '',
                        'personnes' => (int) ($reservation->getNombrePersonnes() ?? 0),
                    ]));

                $mailer->send($email);
            } catch (\Throwable $e) {
                error_log('Email sending error: ' . $e->getMessage());
                $this->addFlash('error', 'Erreur email: ' . $e->getMessage());
            }

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
public function quiz(Reservation $reservation, Request $request, EntityManagerInterface $entityManager): Response
{
    if (!$request->hasSession()) {
        $this->addFlash('error', 'Session indisponible. Veuillez réessayer.');
        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }

    $session = $request->getSession();
    $reservationId = (int) ($reservation->getIdReservation() ?? 0);
    $sessionKey = sprintf('reservation_quiz_questions_%d', $reservationId);

    if ($request->isMethod('POST')) {
        $questions = $session->get($sessionKey, []);

        if (!is_array($questions) || count($questions) === 0) {
            $this->addFlash('error', 'Votre session a expiré. Veuillez refaire le quiz.');
            return $this->redirectToRoute('app_reservation_quiz', [
                'id_reservation' => $reservation->getIdReservation(),
            ], Response::HTTP_SEE_OTHER);
        }

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

        // Enregistrer le badge dans la réservation
        $reservation->setQuizBadge($badge);
        $entityManager->persist($reservation);
        $entityManager->flush();

        $session->remove($sessionKey);

        // Quiz result email removed (GroqMailService)

        return $this->render('reservation/quiz_result.html.twig', [
            'reservation' => $reservation,
            'questions' => $questions,
            'answers' => $answers,
            'correct_count' => $correctCount,
            'total' => count($questions),
            'badge' => $badge,
        ]);
    }

    // À chaque GET, générer 3 questions aléatoires
    $questions = $this->buildRandomEcoQuizQuestions(3);
    
    // Les stocker en session pour validation lors du POST
    $session->set($sessionKey, $questions);

    return $this->render('reservation/quiz.html.twig', [
        'reservation' => $reservation,
        'questions' => $questions,
    ]);
}
    #[Route('/export-all/pdf', name: 'app_reservation_export_pdf', methods: ['GET'])]
    public function exportAllPdf(ReservationRepository $reservationRepository, GroqReportService $groqReportService): Response
    {
        $reservations = $reservationRepository
            ->createQueryBuilder('r')
            ->leftJoin('r.activiteEcologique', 'a')
            ->addSelect('a')
            ->orderBy('r.date_reservation', 'DESC')
            ->getQuery()
            ->getResult();

        $report = null;
        try {
            $reportPayload = $this->buildReservationReportPayload($reservations);
            $report = $groqReportService->generateReport($reportPayload['activities'], $reportPayload['reservations']);
        } catch (\Throwable $exception) {
            $report = 'Rapport IA indisponible pour cet export PDF.';
        }

        $html = $this->renderView('reservation/pdf_list.html.twig', [
            'reservations' => $reservations,
            'generatedAt' => new \DateTimeImmutable(),
            'report' => $report,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);

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

    #[Route('/report/ai', name: 'app_reservation_report_ai', methods: ['GET'])]
    public function reportAi(
        Request $request,
        ReservationRepository $reservationRepository,
        GroqReportService $groqReportService
    ): JsonResponse {
        $searchTerm = trim((string) $request->query->get('q', ''));
        $searchField = (string) $request->query->get('field', 'all');
        $statusFilter = (string) $request->query->get('status', 'all');
        $personnesFilter = (string) $request->query->get('personnes', 'all');
        $dateFrom = trim((string) $request->query->get('date_from', ''));
        $dateTo = trim((string) $request->query->get('date_to', ''));
        $sort = (string) $request->query->get('sort', 'id_desc');

        $queryBuilder = $this->buildFilteredReservationQueryBuilder(
            $reservationRepository,
            $searchTerm,
            $searchField,
            $statusFilter,
            $personnesFilter,
            $dateFrom,
            $dateTo
        );
        $this->applyReservationSort($queryBuilder, $sort);

        /** @var array<int, Reservation> $reservations */
        $reservations = $queryBuilder->getQuery()->getResult();

        $reportPayload = $this->buildReservationReportPayload($reservations);

        try {
            return $this->json([
                'success' => true,
                'report' => $groqReportService->generateReport($reportPayload['activities'], $reportPayload['reservations']),
            ]);
        } catch (\Throwable $exception) {
            return $this->json([
                'success' => false,
                'error' => $this->normalizeGroqErrorMessage($exception->getMessage()),
            ], Response::HTTP_BAD_GATEWAY);
        }
    }

    #[Route('/report/ai/page', name: 'app_reservation_report_ai_page', methods: ['GET'])]
    public function reportAiPage(
        Request $request,
        ReservationRepository $reservationRepository,
        GroqReportService $groqReportService
    ): Response {
        $searchTerm = trim((string) $request->query->get('q', ''));
        $searchField = (string) $request->query->get('field', 'all');
        $statusFilter = (string) $request->query->get('status', 'all');
        $personnesFilter = (string) $request->query->get('personnes', 'all');
        $dateFrom = trim((string) $request->query->get('date_from', ''));
        $dateTo = trim((string) $request->query->get('date_to', ''));
        $sort = (string) $request->query->get('sort', 'id_desc');

        $queryBuilder = $this->buildFilteredReservationQueryBuilder(
            $reservationRepository,
            $searchTerm,
            $searchField,
            $statusFilter,
            $personnesFilter,
            $dateFrom,
            $dateTo
        );
        $this->applyReservationSort($queryBuilder, $sort);

        /** @var array<int, Reservation> $reservations */
        $reservations = $queryBuilder->getQuery()->getResult();

        $reportPayload = $this->buildReservationReportPayload($reservations);

        try {
            $report = $groqReportService->generateReport($reportPayload['activities'], $reportPayload['reservations']);
        } catch (\Throwable $exception) {
            $report = 'Rapport IA indisponible pour le moment.';
        }

        return $this->render('reservation/report_ai.html.twig', [
            'reservations' => $reservations,
            'generatedAt' => new \DateTimeImmutable(),
            'report' => $report,
            'filters' => [
                'q' => $searchTerm,
                'field' => $searchField,
                'status' => $statusFilter,
                'personnes' => $personnesFilter,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'sort' => $sort,
            ],
        ]);
    }

    #[Route('/report/ai/pdf', name: 'app_reservation_report_ai_pdf', methods: ['GET'])]
    public function reportAiPdf(
        Request $request,
        ReservationRepository $reservationRepository,
        GroqReportService $groqReportService
    ): Response {
        $searchTerm = trim((string) $request->query->get('q', ''));
        $searchField = (string) $request->query->get('field', 'all');
        $statusFilter = (string) $request->query->get('status', 'all');
        $personnesFilter = (string) $request->query->get('personnes', 'all');
        $dateFrom = trim((string) $request->query->get('date_from', ''));
        $dateTo = trim((string) $request->query->get('date_to', ''));
        $sort = (string) $request->query->get('sort', 'id_desc');

        $queryBuilder = $this->buildFilteredReservationQueryBuilder(
            $reservationRepository,
            $searchTerm,
            $searchField,
            $statusFilter,
            $personnesFilter,
            $dateFrom,
            $dateTo
        );
        $this->applyReservationSort($queryBuilder, $sort);

        /** @var array<int, Reservation> $reservations */
        $reservations = $queryBuilder->getQuery()->getResult();

        $report = null;
        try {
            $reportPayload = $this->buildReservationReportPayload($reservations);
            $report = $groqReportService->generateReport($reportPayload['activities'], $reportPayload['reservations']);
        } catch (\Throwable $exception) {
            $report = 'Rapport IA indisponible pour cet export PDF.';
        }

        $html = $this->renderView('reservation/pdf_ai.html.twig', [
            'reservations' => $reservations,
            'generatedAt' => new \DateTimeImmutable(),
            'report' => $report,
            'filters' => [
                'q' => $searchTerm,
                'field' => $searchField,
                'status' => $statusFilter,
                'personnes' => $personnesFilter,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'sort' => $sort,
            ],
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="rapport_ia_reservations_%s.pdf"', (new \DateTimeImmutable())->format('Y-m-d')),
            ]
        );
    }

    /**
     * @param array<int, Reservation> $reservations
     * @return array{activities: array<int, array{nom: string, date: string, capacite: int, booked: int, reservations: int}>, reservations: array<int, array{nom: string, activite: string, date: string, nombrePersonnes: int, statut: string}>}
     */
    private function buildReservationReportPayload(array $reservations): array
    {
        $activities = [];
        foreach ($reservations as $reservation) {
            if (!$reservation instanceof Reservation) {
                continue;
            }

            $activity = $reservation->getActiviteEcologique();
            if ($activity === null) {
                continue;
            }

            $activityId = $activity->getIdActivite() ?? spl_object_id($activity);
            if (!isset($activities[$activityId])) {
                $activities[$activityId] = [
                    'nom' => (string) ($activity->getNomActivite() ?? 'Activite inconnue'),
                    'date' => $activity->getDateActivite()?->format('Y-m-d') ?? 'Non planifiee',
                    'capacite' => (int) ($activity->getCapacite() ?? 0),
                    'booked' => 0,
                    'reservations' => 0,
                ];
            }

            $activities[$activityId]['booked'] += (int) ($reservation->getNombrePersonnes() ?? 0);
            $activities[$activityId]['reservations'] += 1;
        }

        $reservationRows = array_map(function (Reservation $reservation): array {
            return [
                'nom' => (string) ($reservation->getNom() ?? 'Sans nom'),
                'activite' => (string) ($reservation->getActiviteEcologique()?->getNomActivite() ?? 'Sans activité'),
                'date' => $reservation->getDateReservation()?->format('Y-m-d') ?? 'Sans date',
                'nombrePersonnes' => (int) ($reservation->getNombrePersonnes() ?? 0),
                'statut' => $this->getReservationStatus($reservation),
            ];
        }, $reservations);

        return [
            'activities' => array_values($activities),
            'reservations' => $reservationRows,
        ];
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
        $options->set('isRemoteEnabled', true);

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

    private function buildFilteredReservationQueryBuilder(
        ReservationRepository $reservationRepository,
        string $searchTerm,
        string $searchField,
        string $statusFilter,
        string $personnesFilter,
        string $dateFrom,
        string $dateTo
    ): QueryBuilder {
        $queryBuilder = $reservationRepository->createQueryBuilder('r')
            ->leftJoin('r.activiteEcologique', 'a')
            ->addSelect('a');

        $this->applyReservationSearchFilter($queryBuilder, $searchTerm, $searchField);
        $this->applyReservationStatusFilter($queryBuilder, $statusFilter);
        $this->applyReservationPersonnesFilter($queryBuilder, $personnesFilter);
        $this->applyReservationDateRangeFilter($queryBuilder, $dateFrom, $dateTo);

        return $queryBuilder;
    }

    private function applyReservationSearchFilter(QueryBuilder $queryBuilder, string $searchTerm, string $searchField): void
    {
        $tokens = preg_split('/\s+/', $this->normalizeSearchValue($searchTerm), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($tokens === []) {
            return;
        }

        if ($searchField === 'all') {
            foreach ($tokens as $index => $token) {
                $parameter = sprintf('search_token_%d', $index);
                $queryBuilder
                    ->andWhere(
                        $queryBuilder->expr()->orX(
                            $queryBuilder->expr()->like('LOWER(CONCAT(r.id_reservation, \'\'))', ':' . $parameter),
                            $queryBuilder->expr()->like('LOWER(r.nom)', ':' . $parameter),
                            $queryBuilder->expr()->like('LOWER(COALESCE(a.nom_activite, \'\'))', ':' . $parameter),
                            $queryBuilder->expr()->like('LOWER(CONCAT(r.date_reservation, \'\'))', ':' . $parameter),
                            $queryBuilder->expr()->like('LOWER(r.email)', ':' . $parameter),
                            $queryBuilder->expr()->like('LOWER(CONCAT(r.nombre_personnes, \'\'))', ':' . $parameter)
                        )
                    )
                    ->setParameter($parameter, '%' . $token . '%');
            }

            return;
        }

        $fieldExpression = match ($searchField) {
            'id' => 'LOWER(CONCAT(r.id_reservation, \'\'))',
            'nom' => 'LOWER(r.nom)',
            'activite' => 'LOWER(COALESCE(a.nom_activite, \'\'))',
            'date' => 'LOWER(CONCAT(r.date_reservation, \'\'))',
            'email' => 'LOWER(r.email)',
            'nombre' => 'LOWER(CONCAT(r.nombre_personnes, \'\'))',
            default => null,
        };

        if ($fieldExpression === null) {
            return;
        }

        foreach ($tokens as $index => $token) {
            $parameter = sprintf('search_field_token_%d', $index);
            $queryBuilder
                ->andWhere($queryBuilder->expr()->like($fieldExpression, ':' . $parameter))
                ->setParameter($parameter, '%' . $token . '%');
        }
    }

    private function applyReservationStatusFilter(QueryBuilder $queryBuilder, string $statusFilter): void
    {
        if ($statusFilter === 'all') {
            return;
        }

        $today = new \DateTimeImmutable('today');

        match ($statusFilter) {
            'cancelled' => $queryBuilder->andWhere('r.activiteEcologique IS NULL'),
            'pending' => $queryBuilder
                ->andWhere('r.activiteEcologique IS NOT NULL')
                ->andWhere('r.date_reservation > :reservationStatusToday')
                ->setParameter('reservationStatusToday', $today),
            'confirmed' => $queryBuilder
                ->andWhere('r.activiteEcologique IS NOT NULL')
                ->andWhere('r.date_reservation <= :reservationStatusToday')
                ->setParameter('reservationStatusToday', $today),
            default => null,
        };
    }

    private function applyReservationPersonnesFilter(QueryBuilder $queryBuilder, string $personnesFilter): void
    {
        if ($personnesFilter === 'all') {
            return;
        }

        match ($personnesFilter) {
            '1-5' => $queryBuilder->andWhere('r.nombre_personnes BETWEEN 1 AND 5'),
            '6-10' => $queryBuilder->andWhere('r.nombre_personnes BETWEEN 6 AND 10'),
            '11-50' => $queryBuilder->andWhere('r.nombre_personnes BETWEEN 11 AND 50'),
            '50+' => $queryBuilder->andWhere('r.nombre_personnes > 50'),
            default => null,
        };
    }

    private function applyReservationDateRangeFilter(QueryBuilder $queryBuilder, string $dateFrom, string $dateTo): void
    {
        if ($dateFrom !== '') {
            $queryBuilder
                ->andWhere('r.date_reservation >= :reservationDateFrom')
                ->setParameter('reservationDateFrom', new \DateTimeImmutable($dateFrom));
        }

        if ($dateTo !== '') {
            $queryBuilder
                ->andWhere('r.date_reservation <= :reservationDateTo')
                ->setParameter('reservationDateTo', new \DateTimeImmutable($dateTo));
        }
    }

    private function applyReservationSort(QueryBuilder $queryBuilder, string $sort): void
    {
        match ($sort) {
            'id_asc' => $queryBuilder->orderBy('r.id_reservation', 'ASC'),
            'id_desc' => $queryBuilder->orderBy('r.id_reservation', 'DESC'),
            'nom_asc' => $queryBuilder->orderBy('r.nom', 'ASC')->addOrderBy('r.id_reservation', 'DESC'),
            'nom_desc' => $queryBuilder->orderBy('r.nom', 'DESC')->addOrderBy('r.id_reservation', 'DESC'),
            'date_asc' => $queryBuilder->orderBy('r.date_reservation', 'ASC')->addOrderBy('r.id_reservation', 'DESC'),
            'nombre_asc' => $queryBuilder->orderBy('r.nombre_personnes', 'ASC')->addOrderBy('r.id_reservation', 'DESC'),
            'nombre_desc' => $queryBuilder->orderBy('r.nombre_personnes', 'DESC')->addOrderBy('r.id_reservation', 'DESC'),
            default => $queryBuilder->orderBy('r.date_reservation', 'DESC')->addOrderBy('r.id_reservation', 'DESC'),
        };
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

    private function normalizeGroqErrorMessage(string $message): string
    {
        $normalized = trim($message);

        if ($normalized === '') {
            return 'Le rapport IA est indisponible pour le moment.';
        }

        if (str_contains($normalized, 'Invalid API Key')) {
            return 'La clé Groq du rapport IA est invalide.';
        }

        if (str_contains($normalized, 'rate_limit') || str_contains($normalized, 'Rate limit')) {
            return 'Le quota Groq est atteint temporairement. Réessayez dans un instant.';
        }

        if (str_contains($normalized, 'model')) {
            return 'Le modèle Groq du rapport IA est indisponible ou non autorisé.';
        }

        return $normalized;
    }

    private function normalizeSearchValue(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);

        return $ascii !== false ? $ascii : $normalized;
    }

    /**
     * @return array<int, array{question: string, answer: string, wrong: array<int, string>, choices: array<int, string>}>
     */
    private function buildRandomEcoQuizQuestions(int $count): array
    {
        $bank = $this->buildEcoQuizBank();
        if ($count <= 0 || count($bank) === 0) {
            return [];
        }

        $count = min($count, count($bank));
        $keys = array_rand($bank, $count);
        if (!is_array($keys)) {
            $keys = [$keys];
        }

        $selected = [];
        foreach ($keys as $key) {
            $selected[] = $bank[$key];
        }

        $selected = array_values($selected);

        foreach ($selected as &$question) {
            $correct = (string) ($question['answer'] ?? '');
            $wrong = array_values(array_filter(
                $question['wrong'] ?? [],
                static fn ($value): bool => is_string($value) && trim($value) !== '' && trim($value) !== $correct
            ));

            $wrong = array_slice($wrong, 0, 3);
            while (count($wrong) < 3) {
                $candidate = 'Aucune de ces réponses';
                if (!in_array($candidate, $wrong, true) && $candidate !== $correct) {
                    $wrong[] = $candidate;
                    continue;
                }

                $candidate = 'Je ne sais pas';
                if (!in_array($candidate, $wrong, true) && $candidate !== $correct) {
                    $wrong[] = $candidate;
                    continue;
                }

                $candidate = 'Non applicable';
                if (!in_array($candidate, $wrong, true) && $candidate !== $correct) {
                    $wrong[] = $candidate;
                }
            }

            $choices = $wrong;
            $choices[] = $correct;
            shuffle($choices);
            $question['choices'] = $choices;
        }
        unset($question);

        return $selected;
    }

    /**
     * @return array<int, array{question: string, answer: string, wrong: array<int, string>}>
     */
    private function buildEcoQuizBank(): array
    {
        return [
            [
                'question' => 'Quel geste aide le plus à réduire la pollution plastique en mer ?',
                'answer' => 'Réduire les plastiques à usage unique',
                'wrong' => ['Jeter les déchets par la fenêtre', 'Enterrer les plastiques sur la plage', 'Les brûler à l’air libre'],
            ],
            [
                'question' => 'Qu’appelle-t-on « microplastiques » ?',
                'answer' => 'De minuscules fragments de plastique',
                'wrong' => ['Des algues microscopiques', 'Des grains de sable colorés', 'De la mousse de mer naturelle'],
            ],
            [
                'question' => 'Pourquoi faut-il éviter de laisser des déchets sur la plage ?',
                'answer' => 'Ils peuvent être ingérés par les animaux marins',
                'wrong' => ['Ils se transforment en eau', 'Ils améliorent la biodiversité', 'Ils rendent la mer plus salée'],
            ],
            [
                'question' => 'Quel est le rôle principal des herbiers marins (posidonies) ?',
                'answer' => 'Abriter la faune et stocker du carbone',
                'wrong' => ['Augmenter la température de l’eau', 'Produire du pétrole', 'Créer des vagues'],
            ],
            [
                'question' => 'Quel phénomène est lié à l’augmentation du CO₂ dans l’océan ?',
                'answer' => 'L’acidification des océans',
                'wrong' => ['La solidification de l’eau', 'La disparition de la marée', 'La création de nouvelles îles'],
            ],
            [
                'question' => 'Quel impact le plastique a-t-il sur les tortues marines ?',
                'answer' => 'Elles peuvent s’étouffer ou s’empoisonner en l’ingérant',
                'wrong' => ['Il leur sert toujours de nourriture', 'Il accélère leur croissance', 'Il renforce leur carapace'],
            ],
            [
                'question' => 'Que signifie « surpêche » ?',
                'answer' => 'Pêcher plus vite que les espèces ne se reproduisent',
                'wrong' => ['Pêcher uniquement la nuit', 'Pêcher à la main', 'Pêcher seulement des petits poissons'],
            ],
            [
                'question' => 'Quel objet est le plus souvent retrouvé lors des nettoyages de plages ?',
                'answer' => 'Des mégots de cigarette',
                'wrong' => ['Des télévisions', 'Des vélos', 'Des casseroles'],
            ],
            [
                'question' => 'Quelle bonne pratique limite la pollution pendant un pique-nique ?',
                'answer' => 'Utiliser une gourde et des contenants réutilisables',
                'wrong' => ['Tout emballer dans du film plastique', 'Laisser les restes sur place', 'Jeter les déchets dans la mer'],
            ],
            [
                'question' => 'Quel est l’effet d’une crème solaire non adaptée sur certains récifs ?',
                'answer' => 'Elle peut contribuer à fragiliser les coraux',
                'wrong' => ['Elle rend l’eau potable', 'Elle crée du sable', 'Elle augmente l’oxygène dissous'],
            ],
            [
                'question' => 'Pourquoi le verre cassé est-il dangereux sur la plage ?',
                'answer' => 'Il blesse les visiteurs et la faune',
                'wrong' => ['Il attire les poissons', 'Il se dissout instantanément', 'Il empêche les vagues d’arriver'],
            ],
            [
                'question' => 'Quel déchet met le plus de temps à se dégrader ?',
                'answer' => 'Une bouteille en plastique',
                'wrong' => ['Une peau de banane', 'Un ticket en papier', 'Une feuille'],
            ],
            [
                'question' => 'Quel comportement respecte le mieux la vie marine pendant la baignade ?',
                'answer' => 'Observer sans toucher ni nourrir les animaux',
                'wrong' => ['Attraper les poissons pour les montrer', 'Donner du pain aux poissons', 'Ramasser les étoiles de mer'],
            ],
            [
                'question' => 'Quel est l’objectif d’un tri sélectif ?',
                'answer' => 'Recycler et réduire les déchets enfouis/incinérés',
                'wrong' => ['Mélanger tous les déchets', 'Augmenter la quantité de déchets', 'Rendre les déchets plus lourds'],
            ],
            [
                'question' => 'Quel est un effet direct du réchauffement de l’océan ?',
                'answer' => 'Le stress et le déplacement de certaines espèces',
                'wrong' => ['La disparition du sel', 'La création de diamants', 'La fin du vent'],
            ],
            [
                'question' => 'Pourquoi faut-il ramasser les filets ou morceaux de cordage trouvés sur le rivage ?',
                'answer' => 'Ils peuvent piéger les animaux (enchevêtrement)',
                'wrong' => ['Ils aident les poissons à se cacher', 'Ils deviennent des coraux', 'Ils réparent les bateaux automatiquement'],
            ],
            [
                'question' => 'Quel est un bon réflexe pour protéger la qualité de l’eau ?',
                'answer' => 'Éviter de jeter huiles et produits chimiques à l’évier',
                'wrong' => ['Laver sa voiture sur la plage', 'Verser la peinture dans la mer', 'Jeter les piles dans le sable'],
            ],
            [
                'question' => 'Quel animal marin est un mammifère ?',
                'answer' => 'Le dauphin',
                'wrong' => ['La méduse', 'L’étoile de mer', 'La crevette'],
            ],
            [
                'question' => 'Pourquoi les sacs plastiques sont-ils dangereux pour la faune ?',
                'answer' => 'Ils ressemblent à des proies et peuvent être avalés',
                'wrong' => ['Ils sont toujours biodégradables', 'Ils se transforment en nourriture saine', 'Ils renforcent les nageoires'],
            ],
            [
                'question' => 'Quel est le meilleur moyen de limiter l’empreinte carbone d’un déplacement ?',
                'answer' => 'Privilégier le covoiturage ou les transports en commun',
                'wrong' => ['Faire tourner le moteur à l’arrêt', 'Rouler plus vite', 'Conduire avec les pneus sous-gonflés'],
            ],
            [
                'question' => 'Quel déchet doit être jeté dans une filière spécifique (déchetterie) ?',
                'answer' => 'Une pile',
                'wrong' => ['Un trognon de pomme', 'Une feuille de papier', 'Un bouchon en plastique propre'],
            ],
            [
                'question' => 'Quelle action protège les oiseaux marins sur le littoral ?',
                'answer' => 'Respecter les zones de nidification et la signalisation',
                'wrong' => ['Marcher dans les nids', 'Nourrir les oiseaux avec des chips', 'Jeter des restes de nourriture partout'],
            ],
            [
                'question' => 'Pourquoi les déchets organiques (restes de nourriture) ne doivent-ils pas être laissés sur la plage ?',
                'answer' => 'Ils attirent des animaux et déséquilibrent l’écosystème',
                'wrong' => ['Ils se transforment en plastique', 'Ils augmentent la salinité', 'Ils rendent le sable magnétique'],
            ],
            [
                'question' => 'Quel est un signe possible de pollution sur une plage ?',
                'answer' => 'Une accumulation de déchets et de mousse anormale',
                'wrong' => ['Du sable', 'Des vagues', 'Du soleil'],
            ],
            [
                'question' => 'Que peut-on faire si l’on voit un animal marin blessé ou piégé ?',
                'answer' => 'Alerter les autorités/associations locales compétentes',
                'wrong' => ['Le ramener chez soi', 'Le laisser avec un déchet pour l’aider', 'Le nourrir avec des bonbons'],
            ],
            [
                'question' => 'Quelle pratique aide à préserver les fonds marins en plongée/snorkeling ?',
                'answer' => 'Ne pas marcher sur les herbiers ou coraux',
                'wrong' => ['S’accrocher aux coraux', 'Soulever les rochers pour s’amuser', 'Ramasser des coquillages vivants'],
            ],
        ];
    }
}
