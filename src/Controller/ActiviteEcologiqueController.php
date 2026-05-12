<?php

namespace App\Controller;

use App\Entity\ActiviteEcologique;
use App\Form\ActiviteEcologiqueType;
use App\Repository\ActiviteEcologiqueRepository;
use App\Repository\ReservationRepository;
use App\Service\PredictionAIService;
use App\Service\WeatherService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\DescriptionAIService; 
#[Route('/activite/ecologique')]
final class ActiviteEcologiqueController extends AbstractController
{
    #[Route(name: 'app_activite_ecologique_index', methods: ['GET'])]
    #[Route(name: 'app_activite_ecologique_index', methods: ['GET'])]
    public function index(Request $request, ActiviteEcologiqueRepository $activiteEcologiqueRepository, WeatherService $weatherService): Response
    {
        $searchTerm     = trim((string) $request->query->get('q', ''));
        $searchField    = (string) $request->query->get('field', 'all');
        $periodeFilter  = (string) $request->query->get('periode', 'all');
        $capaciteFilter = (string) $request->query->get('capacite', 'all');
        $sort           = (string) $request->query->get('sort', 'date_desc');

        $activites = $activiteEcologiqueRepository->findAll();

        $activites = array_values(array_filter($activites, function (ActiviteEcologique $activite) use ($searchTerm, $searchField, $periodeFilter, $capaciteFilter): bool {
            return $this->matchesActivitySearch($activite, $searchTerm, $searchField)
                && $this->matchesActivityCapacity($activite, $capaciteFilter)
                && $this->matchesActivityPeriod($activite, $periodeFilter);
        }));

        usort($activites, function (ActiviteEcologique $left, ActiviteEcologique $right) use ($sort): int {
            return $this->compareActivities($left, $right, $sort);
        });

        $page       = max(1, (int) $request->query->get('page', 1));
        $perPage    = 10;
        $totalItems = count($activites);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $currentPage = min($page, $totalPages);
        $offset      = ($currentPage - 1) * $perPage;
        $paginatedActivities = array_slice($activites, $offset, $perPage);
        $pageStart   = $totalItems > 0 ? $offset + 1 : 0;
        $pageEnd     = $totalItems > 0 ? min($offset + $perPage, $totalItems) : 0;

        $weather = $weatherService->getWeatherForCity('Monastir');

        $activityDna = $this->loadActivityDna();

        return $this->render('activite_ecologique/index.html.twig', [
            'activite_ecologiques'       => $paginatedActivities,
            'weather'                    => $weather,
            'search_term'                => $searchTerm,
            'search_field'               => $searchField,
            'periode_filter'             => $periodeFilter,
            'capacite_filter'            => $capaciteFilter,
            'sort_by'                    => $sort,
            'result_count'               => $totalItems,
            'page'                       => $currentPage,
            'total_pages'                => $totalPages,
            'page_start'                 => $pageStart,
            'page_end'                   => $pageEnd,
            'autocomplete_suggestions'   => $this->buildActivityAutocompleteSuggestions($activites),
            'activity_dna'               => $activityDna,
        ]);
    }
    #[Route('/export-all/pdf', name: 'app_activite_ecologique_export_pdf', methods: ['GET'])]
    public function exportAllPdf(ActiviteEcologiqueRepository $activiteEcologiqueRepository): Response
    {
        $activites = $activiteEcologiqueRepository->findAll();

        $html = $this->renderView('activite_ecologique/pdf.html.twig', [
            'activite_ecologiques' => $activites,
            'generatedAt' => new \DateTimeImmutable(),
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
                'Content-Disposition' => sprintf('attachment; filename="activites_%s.pdf"', (new \DateTimeImmutable())->format('Y-m-d')),
            ]
        );
    }

    #[Route('/new', name: 'app_activite_ecologique_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, PredictionAIService $predictionAIService): Response
    {
        $activiteEcologique = new ActiviteEcologique();
        $form = $this->createForm(ActiviteEcologiqueType::class, $activiteEcologique);
        $form->handleRequest($request);
        $fromFront = $request->query->get('source') === 'front';

        if ($form->isSubmitted() && !$form->isValid() && $fromFront) {
            $this->addFlash('error', 'Impossible de créer l\'activité. Vérifiez les champs saisis.');
            return $this->redirect($this->generateUrl('app_home') . '#slide09', Response::HTTP_SEE_OTHER);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $isRecurring = (bool) $form->get('is_recurring')->getData();

            // ── PRÉDICTION AI ─────────────────────────────────────────────────
            $dateActivite = $activiteEcologique->getDate_activite();
            $prediction   = null;

            if ($dateActivite instanceof \DateTimeInterface) {
                $prediction = $predictionAIService->predictRisque(
                    (int) $dateActivite->format('n'),       // mois (1-12)
                    (int) $dateActivite->format('N') - 1,   // jour semaine (0=Lundi)
                    (int) $activiteEcologique->getCapacite(),
                    (string) $activiteEcologique->getNom_activite()
                );
            }
            // ─────────────────────────────────────────────────────────────────

            if (!$isRecurring) {
                $entityManager->persist($activiteEcologique);
                $entityManager->flush();

                if ($prediction !== null) {
                    $this->addFlash('prediction_' . $prediction['couleur'], $prediction['message']);
                }

                $this->addFlash('success', 'Activité écologique créée avec succès.');

                if ($fromFront) {
                    return $this->redirect($this->generateUrl('app_home') . '#slide09', Response::HTTP_SEE_OTHER);
                }

                return $this->redirectToRoute('app_activite_ecologique_index', [], Response::HTTP_SEE_OTHER);
            }

            $startDate    = $activiteEcologique->getDate_activite();
            $endDate      = $form->get('recurrence_end_date')->getData();
            $selectedDays = $form->get('recurrence_days')->getData();

            if (!$startDate instanceof \DateTimeInterface || !$endDate instanceof \DateTimeInterface) {
                $form->get('recurrence_end_date')->addError(new \Symfony\Component\Form\FormError('Veuillez choisir une date de fin valide.'));
            } elseif ($endDate < $startDate) {
                $form->get('recurrence_end_date')->addError(new \Symfony\Component\Form\FormError('La date de fin doit être supérieure ou égale à la date de début.'));
            } elseif (!is_array($selectedDays) || count($selectedDays) === 0) {
                $form->get('recurrence_days')->addError(new \Symfony\Component\Form\FormError('Sélectionnez au moins un jour de répétition.'));
            } else {
                $normalizedDays = array_map('intval', $selectedDays);
                sort($normalizedDays);

                $dates  = [];
                $cursor = \DateTime::createFromFormat('Y-m-d', $startDate->format('Y-m-d'));
                $limit  = \DateTime::createFromFormat('Y-m-d', $endDate->format('Y-m-d'));

                while ($cursor <= $limit) {
                    if (in_array((int) $cursor->format('N'), $normalizedDays, true)) {
                        $dates[] = clone $cursor;
                    }
                    $cursor->modify('+1 day');
                }

                if (count($dates) === 0) {
                    $form->get('recurrence_days')->addError(new \Symfony\Component\Form\FormError('Aucune occurrence trouvée avec les jours sélectionnés.'));
                } else {
                    foreach ($dates as $index => $date) {
                        $item = $index === 0 ? $activiteEcologique : new ActiviteEcologique();
                        $item
                            ->setNom_activite((string) $activiteEcologique->getNom_activite())
                            ->setDescription($activiteEcologique->getDescription())
                            ->setCapacite((int) $activiteEcologique->getCapacite())
                            ->setDate_activite($date);
                        $entityManager->persist($item);
                    }

                    $entityManager->flush();

                    if ($prediction !== null) {
                        $this->addFlash('prediction_' . $prediction['couleur'], $prediction['message']);
                    }

                    $this->addFlash('success', sprintf('%d activité(s) écologique(s) créée(s) avec récurrence.', count($dates)));

                    if ($fromFront) {
                        return $this->redirect($this->generateUrl('app_home') . '#slide09', Response::HTTP_SEE_OTHER);
                    }

                    return $this->redirectToRoute('app_activite_ecologique_index', [], Response::HTTP_SEE_OTHER);
                }
            }

            if ($fromFront) {
                $this->addFlash('error', 'Récurrence invalide. Vérifiez la date de fin et les jours sélectionnés.');
                return $this->redirect($this->generateUrl('app_home') . '#slide09', Response::HTTP_SEE_OTHER);
            }

            return $this->render('activite_ecologique/new.html.twig', [
                'activite_ecologique' => $activiteEcologique,
                'form'               => $form,
            ]);
        }

        // include prediction error in dev
        $predictionError = null;
        try {
            if ($this->getParameter('kernel.environment') !== 'prod') {
                $errorPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'last_prediction_error.json';
                if (file_exists($errorPath)) {
                    $predictionError = json_decode(file_get_contents($errorPath), true);
                }
            }
        } catch (\Throwable $e) {
            $predictionError = null;
        }

        return $this->render('activite_ecologique/new.html.twig', [
            'activite_ecologique' => $activiteEcologique,
            'form'               => $form,
            'prediction_error' => $predictionError,
        ]);
    }
    #[Route('/generate-description', name: 'app_activite_ecologique_generate_description', methods: ['POST'])]
    public function generateDescription(Request $request, DescriptionAIService $descriptionAIService): \Symfony\Component\HttpFoundation\JsonResponse
    {
        try {
            $data        = json_decode($request->getContent(), true);
            $nom         = trim((string) ($data['nom'] ?? ''));
            $capacite    = (int) ($data['capacite'] ?? 10);
            $dateStr     = trim((string) ($data['date'] ?? ''));
            $date        = null;

            if ($nom === '') {
                return $this->json(['success' => false, 'message' => 'Nom manquant.']);
            }

            if ($dateStr !== '') {
                try {
                    $date = new \DateTime($dateStr);
                } catch (\Exception) {
                    $date = null;
                }
            }

            $result = $descriptionAIService->generateDescription($nom, $capacite > 0 ? $capacite : 10, $date);

            // Ensure we always have a message field
            if (!isset($result['message'])) {
                if ($result['success']) {
                    $result['message'] = 'OK';
                } else {
                    $result['message'] = 'Erreur inconnue';
                }
            }

            return $this->json($result);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage(),
                'error' => get_class($e),
            ], 500);
        }
    }
    #[Route('/{id_activite}', name: 'app_activite_ecologique_show', methods: ['GET'])]
    public function show(ActiviteEcologique $activiteEcologique): Response
    {
        return $this->render('activite_ecologique/show.html.twig', [
            'activite_ecologique' => $activiteEcologique,
        ]);
    }

    #[Route('/{id_activite}/dna', name: 'app_activite_ecologique_dna', methods: ['GET'])]
    public function dnaDetails(ActiviteEcologiqueRepository $activityRepo, ActiviteEcologique $activiteEcologique): Response
    {
        return $this->render('activite_ecologique/dna_details.html.twig', $this->buildActivityDnaContext($activityRepo, $activiteEcologique));
    }

    #[Route('/{id_activite}/dna/pdf', name: 'app_activite_ecologique_dna_pdf', methods: ['GET'])]
    public function dnaPdf(ActiviteEcologiqueRepository $activityRepo, ActiviteEcologique $activiteEcologique): Response
    {
        $context = $this->buildActivityDnaContext($activityRepo, $activiteEcologique);
        $html = $this->renderView('activite_ecologique/pdf_dna.html.twig', $context);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="dna_activite_%d.pdf"', $activiteEcologique->getIdActivite() ?? 0),
            ]
        );
    }

    #[Route('/{id_activite}/edit', name: 'app_activite_ecologique_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ActiviteEcologique $activiteEcologique, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ActiviteEcologiqueType::class, $activiteEcologique);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_activite_ecologique_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activite_ecologique/edit.html.twig', [
            'activite_ecologique' => $activiteEcologique,
            'form' => $form,
        ]);
    }

    #[Route('/predict-ajax', name: 'app_activite_ecologique_predict_ajax', methods: ['GET','POST'])]
    public function predictAjax(Request $request, PredictionAIService $predictionAIService): JsonResponse
    {
        $mois = (int) $request->request->get('mois', $request->query->get('mois', 0));
        $jour = (int) $request->request->get('jour_semaine', $request->query->get('jour_semaine', 0));
        $capacite = max(1, (int) $request->request->get('capacite', $request->query->get('capacite', 10)));
        $type = (string) $request->request->get('type', $request->query->get('type', ''));

        return new JsonResponse($predictionAIService->predictRisque($mois, $jour, $capacite, $type));
    }

    #[Route('/{id_activite}', name: 'app_activite_ecologique_delete', methods: ['POST'])]
    public function delete(Request $request, ActiviteEcologique $activiteEcologique, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$activiteEcologique->getId_activite(), $request->getPayload()->getString('_token'))) {
            foreach ($activiteEcologique->getReservations() as $reservation) {
                $reservation->setActiviteEcologique(null);
            }

            $entityManager->remove($activiteEcologique);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_activite_ecologique_index', [], Response::HTTP_SEE_OTHER);
    }

    // ── MÉTHODES PRIVÉES ────────────────────────────────────────────────────────

    private function matchesActivitySearch(ActiviteEcologique $activite, string $searchTerm, string $searchField): bool
    {
        $tokens = preg_split('/\s+/', $this->normalizeSearchValue($searchTerm), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($tokens === []) {
            return true;
        }

        $searchableParts = [];

        if ($searchField === 'all') {
            $searchableParts = array_filter([
                $this->normalizeSearchValue((string) $activite->getIdActivite()),
                $this->normalizeSearchValue((string) $activite->getNomActivite()),
                $this->normalizeSearchValue($activite->getDateActivite()?->format('Y-m-d') ?? ''),
                $this->normalizeSearchValue((string) $activite->getCapacite()),
                $this->normalizeSearchValue((string) ($activite->getDescription() ?? '')),
            ]);
        } else {
            $fieldValue = match ($searchField) {
                'id'          => (string) $activite->getIdActivite(),
                'nom'         => (string) $activite->getNomActivite(),
                'date'        => $activite->getDateActivite()?->format('Y-m-d') ?? '',
                'capacite'    => (string) $activite->getCapacite(),
                'description' => (string) ($activite->getDescription() ?? ''),
                default       => '',
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

    private function matchesActivityCapacity(ActiviteEcologique $activite, string $capaciteFilter): bool
    {
        if ($capaciteFilter === 'all') {
            return true;
        }

        $capacity = (int) $activite->getCapacite();

        return match ($capaciteFilter) {
            'small'        => $capacity <= 20,
            'medium'       => $capacity >= 21 && $capacity <= 50,
            'large'        => $capacity >= 51,
            'medium-large' => $capacity >= 10,
            default        => true,
        };
    }

    private function matchesActivityPeriod(ActiviteEcologique $activite, string $periodeFilter): bool
    {
        if ($periodeFilter === 'all') {
            return true;
        }

        $dateActivite = $activite->getDateActivite();

        if (!$dateActivite) {
            return false;
        }

        $rowDate = \DateTimeImmutable::createFromInterface($dateActivite);
        $today   = new \DateTimeImmutable('today');

        return match ($periodeFilter) {
            'today'   => $rowDate->format('Y-m-d') === $today->format('Y-m-d'),
            'week'    => $rowDate >= $today->modify('last sunday')->setTime(0, 0) && $rowDate <= $today->modify('next saturday')->setTime(23, 59, 59),
            'current' => $rowDate->format('Y-m') === $today->format('Y-m'),
            default   => false,
        };
    }

    private function compareActivities(ActiviteEcologique $left, ActiviteEcologique $right, string $sort): int
    {
        return match ($sort) {
            'id_asc'       => ($left->getIdActivite() ?? 0) <=> ($right->getIdActivite() ?? 0),
            'id_desc'      => ($right->getIdActivite() ?? 0) <=> ($left->getIdActivite() ?? 0),
            'nom_asc'      => strcmp($this->normalizeSearchValue((string) $left->getNomActivite()), $this->normalizeSearchValue((string) $right->getNomActivite())),
            'nom_desc'     => strcmp($this->normalizeSearchValue((string) $right->getNomActivite()), $this->normalizeSearchValue((string) $left->getNomActivite())),
            'date_asc'     => $this->compareActivityDates($left, $right),
            'capacite_asc' => ($left->getCapacite() ?? 0) <=> ($right->getCapacite() ?? 0),
            'capacite_desc'=> ($right->getCapacite() ?? 0) <=> ($left->getCapacite() ?? 0),
            default        => $this->compareActivityDates($right, $left),
        };
    }

    private function compareActivityDates(ActiviteEcologique $left, ActiviteEcologique $right): int
    {
        $leftDate  = $left->getDateActivite();
        $rightDate = $right->getDateActivite();

        if (!$leftDate && !$rightDate) return 0;
        if (!$leftDate) return -1;
        if (!$rightDate) return 1;

        return $leftDate <=> $rightDate;
    }

    private function buildFilteredActivityQueryBuilder(
        ActiviteEcologiqueRepository $activiteEcologiqueRepository,
        string $searchTerm,
        string $searchField,
        string $periodeFilter,
        string $capaciteFilter
    ): QueryBuilder {
        $queryBuilder = $activiteEcologiqueRepository->createQueryBuilder('a');

        $this->applyActivitySearchFilter($queryBuilder, $searchTerm, $searchField);
        $this->applyActivityCapacityFilter($queryBuilder, $capaciteFilter);
        $this->applyActivityPeriodFilter($queryBuilder, $periodeFilter);

        return $queryBuilder;
    }

    private function applyActivitySearchFilter(QueryBuilder $queryBuilder, string $searchTerm, string $searchField): void
    {
        $tokens = preg_split('/\s+/', $this->normalizeSearchValue($searchTerm), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($tokens === []) {
            return;
        }

        if ($searchField === 'all') {
            foreach ($tokens as $index => $token) {
                $parameter = sprintf('activity_search_token_%d', $index);
                $queryBuilder
                    ->andWhere(
                        $queryBuilder->expr()->orX(
                            $queryBuilder->expr()->like('LOWER(CONCAT(a.id_activite, \'\'))', ':' . $parameter),
                            $queryBuilder->expr()->like('LOWER(a.nom_activite)', ':' . $parameter),
                            $queryBuilder->expr()->like('LOWER(CONCAT(a.date_activite, \'\'))', ':' . $parameter),
                            $queryBuilder->expr()->like('LOWER(CONCAT(a.capacite, \'\'))', ':' . $parameter)
                        )
                    )
                    ->setParameter($parameter, '%' . $token . '%');
            }
            return;
        }

        $fieldExpression = match ($searchField) {
            'id'          => 'LOWER(CONCAT(a.id_activite, \'\'))',
            'nom'         => 'LOWER(a.nom_activite)',
            'date'        => 'LOWER(CONCAT(a.date_activite, \'\'))',
            'capacite'    => 'LOWER(CONCAT(a.capacite, \'\'))',
            default       => null,
        };

        if ($fieldExpression === null) {
            return;
        }

        foreach ($tokens as $index => $token) {
            $parameter = sprintf('activity_search_field_token_%d', $index);
            $queryBuilder
                ->andWhere($queryBuilder->expr()->like($fieldExpression, ':' . $parameter))
                ->setParameter($parameter, '%' . $token . '%');
        }
    }

    private function applyActivityCapacityFilter(QueryBuilder $queryBuilder, string $capaciteFilter): void
    {
        if ($capaciteFilter === 'all') {
            return;
        }

        match ($capaciteFilter) {
            'small'        => $queryBuilder->andWhere('a.capacite <= 20'),
            'medium'       => $queryBuilder->andWhere('a.capacite BETWEEN 21 AND 50'),
            'large'        => $queryBuilder->andWhere('a.capacite >= 51'),
            'medium-large' => $queryBuilder->andWhere('a.capacite >= 10'),
            default        => null,
        };
    }

    private function applyActivityPeriodFilter(QueryBuilder $queryBuilder, string $periodeFilter): void
    {
        if ($periodeFilter === 'all') {
            return;
        }

        $today = new \DateTimeImmutable('today');

        match ($periodeFilter) {
            'today'   => $queryBuilder->andWhere('a.date_activite = :activityToday')->setParameter('activityToday', $today),
            'week'    => $queryBuilder
                ->andWhere('a.date_activite BETWEEN :activityWeekStart AND :activityWeekEnd')
                ->setParameter('activityWeekStart', (clone $today)->modify('last sunday')->setTime(0, 0))
                ->setParameter('activityWeekEnd', (clone $today)->modify('next saturday')->setTime(23, 59, 59)),
            'current' => $queryBuilder
                ->andWhere('a.date_activite BETWEEN :activityMonthStart AND :activityMonthEnd')
                ->setParameter('activityMonthStart', $today->modify('first day of this month')->setTime(0, 0))
                ->setParameter('activityMonthEnd', $today->modify('last day of this month')->setTime(23, 59, 59)),
            default   => null,
        };
    }

    private function applyActivitySort(QueryBuilder $queryBuilder, string $sort): void
    {
        match ($sort) {
            'id_asc'        => $queryBuilder->orderBy('a.id_activite', 'ASC'),
            'id_desc'       => $queryBuilder->orderBy('a.id_activite', 'DESC'),
            'nom_asc'       => $queryBuilder->orderBy('a.nom_activite', 'ASC')->addOrderBy('a.id_activite', 'DESC'),
            'nom_desc'      => $queryBuilder->orderBy('a.nom_activite', 'DESC')->addOrderBy('a.id_activite', 'DESC'),
            'date_asc'      => $queryBuilder->orderBy('a.date_activite', 'ASC')->addOrderBy('a.id_activite', 'DESC'),
            'capacite_asc'  => $queryBuilder->orderBy('a.capacite', 'ASC')->addOrderBy('a.id_activite', 'DESC'),
            'capacite_desc' => $queryBuilder->orderBy('a.capacite', 'DESC')->addOrderBy('a.id_activite', 'DESC'),
            default         => $queryBuilder->orderBy('a.date_activite', 'DESC')->addOrderBy('a.id_activite', 'DESC'),
        };
    }

    /**
     * @param ActiviteEcologique[] $activities
     */
    private function loadBookedPeopleByActivity(ReservationRepository $reservationRepository, array $activities): array
    {
        $activityIds = [];

        foreach ($activities as $activity) {
            if ($activity instanceof ActiviteEcologique && $activity->getIdActivite() !== null) {
                $activityIds[] = $activity->getIdActivite();
            }
        }

        if ($activityIds === []) {
            return [];
        }

        $rows = $reservationRepository->createQueryBuilder('r')
            ->select('IDENTITY(r.activiteEcologique) AS activity_id, COALESCE(SUM(r.nombre_personnes), 0) AS booked_people')
            ->andWhere('r.activiteEcologique IN (:activityIds)')
            ->setParameter('activityIds', $activityIds)
            ->groupBy('activity_id')
            ->getQuery()
            ->getArrayResult();

        $bookedPeopleByActivity = [];
        foreach ($rows as $row) {
            $activityId = (int) ($row['activity_id'] ?? 0);
            $bookedPeopleByActivity[$activityId] = (int) ($row['booked_people'] ?? 0);
        }

        return $bookedPeopleByActivity;
    }

    private function normalizeSearchValue(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);

        return $ascii !== false ? $ascii : $normalized;
    }

    private function loadActivityDna(): array
    {
        $jsonPath = $this->getParameter('kernel.project_dir') . '/var/activity_dna.json';
        if (!file_exists($jsonPath)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($jsonPath), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function buildActivityDnaContext(ActiviteEcologiqueRepository $activityRepo, ActiviteEcologique $activiteEcologique): array
    {
        $activityDna = $this->loadActivityDna();
        $activityId = (int) ($activiteEcologique->getIdActivite() ?? 0);
        $dna = $activityDna[$activityId] ?? null;

        $reservations = $activiteEcologique->getReservations()->toArray();
        usort($reservations, static function ($left, $right): int {
            $leftDate = $left?->getDateReservation();
            $rightDate = $right?->getDateReservation();

            if (!$leftDate && !$rightDate) {
                return 0;
            }
            if (!$leftDate) {
                return 1;
            }
            if (!$rightDate) {
                return -1;
            }

            return $rightDate <=> $leftDate;
        });

        $capacity = max(1, (int) ($activiteEcologique->getCapacite() ?? 0));
        $activityDate = $activiteEcologique->getDateActivite();
        $totalReservations = count($reservations);
        $totalPeople = 0;
        $leadTimes = [];
        $uniqueEmails = [];
        $monthlyBookings = [];
        $emailFrequency = [];

        foreach ($reservations as $reservation) {
            if (!$reservation instanceof \App\Entity\Reservation) {
                continue;
            }

            $people = (int) ($reservation->getNombrePersonnes() ?? 0);
            $totalPeople += $people;

            $email = trim((string) ($reservation->getEmail() ?? ''));
            if ($email !== '') {
                $emailLower = strtolower($email);
                $uniqueEmails[$emailLower] = true;
                $emailFrequency[$emailLower] = ($emailFrequency[$emailLower] ?? 0) + 1;
            }

            $reservationDate = $reservation->getDateReservation();
            if ($activityDate instanceof \DateTimeInterface && $reservationDate instanceof \DateTimeInterface) {
                $leadTimes[] = abs($activityDate->diff($reservationDate)->days ?? 0);
            }

            if ($reservationDate instanceof \DateTimeInterface) {
                $monthKey = $reservationDate->format('Y-m');
                $monthlyBookings[$monthKey] = ($monthlyBookings[$monthKey] ?? 0) + $people;
            }
        }

        ksort($monthlyBookings);

        $fillRate = min(100, round(($totalPeople / $capacity) * 100, 1));
        $avgGroup = $totalReservations > 0 ? round($totalPeople / $totalReservations, 1) : 0.0;
        $avgLeadTime = count($leadTimes) > 0 ? round(array_sum($leadTimes) / count($leadTimes), 1) : 0.0;
        $minLeadTime = count($leadTimes) > 0 ? min($leadTimes) : null;
        $maxLeadTime = count($leadTimes) > 0 ? max($leadTimes) : null;
        $uniqueVisitors = count($uniqueEmails);

        $repeatCustomers = count(array_filter($emailFrequency, fn($count) => $count > 1));
        $tauxRetour = $uniqueVisitors > 0 ? round(($repeatCustomers / $uniqueVisitors) * 100, 1) : 0;

        $profileSummary = match (true) {
            $fillRate >= 80 => 'Demande très forte: activité à forte traction. Pensez à ajouter des créneaux similaires.',
            $fillRate >= 40 => 'Demande stable: activité régulière avec marge de progression marketing.',
            default => 'Demande faible: retravailler visibilité, calendrier et positionnement.',
        };

        $similarActivities = [];
        if ($dna && isset($dna['cluster'])) {
            $currentCluster = (int) $dna['cluster'];
            foreach ($activityDna as $otherActivityId => $otherDna) {
                if ($otherActivityId === $activityId) {
                    continue;
                }
                if ((int) ($otherDna['cluster'] ?? -1) === $currentCluster) {
                    try {
                        $otherActivity = $activityRepo->find($otherActivityId);
                        if ($otherActivity) {
                            $similarActivities[] = [
                                'activity' => $otherActivity,
                                'dna' => $otherDna,
                            ];
                        }
                    } catch (\Exception $e) {
                        // ignore missing activities
                    }
                }
            }
        }

        return [
            'activite_ecologique' => $activiteEcologique,
            'dna' => $dna,
            'total_reservations' => $totalReservations,
            'total_people' => $totalPeople,
            'fill_rate' => $fillRate,
            'avg_group' => $avgGroup,
            'avg_lead_time' => $avgLeadTime,
            'min_lead_time' => $minLeadTime,
            'max_lead_time' => $maxLeadTime,
            'unique_visitors' => $uniqueVisitors,
            'taux_retour' => $tauxRetour,
            'monthly_bookings' => $monthlyBookings,
            'recent_reservations' => array_slice($reservations, 0, 8),
            'profile_summary' => $profileSummary,
            'similar_activities' => $similarActivities,
        ];
    }

    /**
     * @param ActiviteEcologique[] $activities
     *
     * @return string[]
     */
    private function buildActivityAutocompleteSuggestions(array $activities): array
    {
        $suggestions = [];

        foreach ($activities as $activity) {
            if (!$activity instanceof ActiviteEcologique) {
                continue;
            }

            $name = trim((string) $activity->getNomActivite());
            if ($name !== '') {
                $suggestions[] = $name;
            }

            $date = $activity->getDateActivite();
            if ($date instanceof \DateTimeInterface) {
                $suggestions[] = $date->format('Y-m-d');
                $suggestions[] = $date->format('d/m/Y');
            }
        }

        $suggestions = array_values(array_unique($suggestions));
        sort($suggestions, SORT_NATURAL | SORT_FLAG_CASE);

        return array_slice($suggestions, 0, 12);
    }

}