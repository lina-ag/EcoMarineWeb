<?php

namespace App\Controller;

use App\Entity\ActiviteEcologique;
use App\Form\ActiviteEcologiqueType;
use App\Repository\ActiviteEcologiqueRepository;
use App\Service\WeatherService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/activite/ecologique')]
final class ActiviteEcologiqueController extends AbstractController
{
    #[Route(name: 'app_activite_ecologique_index', methods: ['GET'])]
    public function index(Request $request, ActiviteEcologiqueRepository $activiteEcologiqueRepository, WeatherService $weatherService): Response
    {
        $searchTerm = trim((string) $request->query->get('q', ''));
        $searchField = (string) $request->query->get('field', 'all');
        $periodeFilter = (string) $request->query->get('periode', 'all');
        $capaciteFilter = (string) $request->query->get('capacite', 'all');
        $sort = (string) $request->query->get('sort', 'date_desc');

        $activites = $activiteEcologiqueRepository->findAll();

        $activites = array_values(array_filter($activites, function (ActiviteEcologique $activite) use ($searchTerm, $searchField, $periodeFilter, $capaciteFilter): bool {
            return $this->matchesActivitySearch($activite, $searchTerm, $searchField)
                && $this->matchesActivityCapacity($activite, $capaciteFilter)
                && $this->matchesActivityPeriod($activite, $periodeFilter);
        }));

        usort($activites, function (ActiviteEcologique $left, ActiviteEcologique $right) use ($sort): int {
            return $this->compareActivities($left, $right, $sort);
        });

        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 10;
        $totalItems = count($activites);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $currentPage = min($page, $totalPages);
        $offset = ($currentPage - 1) * $perPage;
        $paginatedActivities = array_slice($activites, $offset, $perPage);
        $pageStart = $totalItems > 0 ? $offset + 1 : 0;
        $pageEnd = $totalItems > 0 ? min($offset + $perPage, $totalItems) : 0;

        $weather = $weatherService->getWeatherForCity('Monastir');

        return $this->render('activite_ecologique/index.html.twig', [
            'activite_ecologiques' => $paginatedActivities,
            'weather' => $weather,
            'search_term' => $searchTerm,
            'search_field' => $searchField,
            'periode_filter' => $periodeFilter,
            'capacite_filter' => $capaciteFilter,
            'sort_by' => $sort,
            'result_count' => $totalItems,
            'page' => $currentPage,
            'total_pages' => $totalPages,
            'page_start' => $pageStart,
            'page_end' => $pageEnd,
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
                'id' => (string) $activite->getIdActivite(),
                'nom' => (string) $activite->getNomActivite(),
                'date' => $activite->getDateActivite()?->format('Y-m-d') ?? '',
                'capacite' => (string) $activite->getCapacite(),
                'description' => (string) ($activite->getDescription() ?? ''),
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

    private function matchesActivityCapacity(ActiviteEcologique $activite, string $capaciteFilter): bool
    {
        if ($capaciteFilter === 'all') {
            return true;
        }

        $capacity = (int) $activite->getCapacite();

        return match ($capaciteFilter) {
            'small' => $capacity <= 20,
            'medium' => $capacity >= 21 && $capacity <= 50,
            'large' => $capacity >= 51,
            'medium-large' => $capacity >= 10,
            default => true,
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
        $today = new \DateTimeImmutable('today');

        return match ($periodeFilter) {
            'today' => $rowDate->format('Y-m-d') === $today->format('Y-m-d'),
            'week' => $rowDate >= $today->modify('last sunday')->setTime(0, 0) && $rowDate <= $today->modify('next saturday')->setTime(23, 59, 59),
            'current' => $rowDate->format('Y-m') === $today->format('Y-m'),
            default => false,
        };
    }

    private function compareActivities(ActiviteEcologique $left, ActiviteEcologique $right, string $sort): int
    {
        return match ($sort) {
            'id_asc' => ($left->getIdActivite() ?? 0) <=> ($right->getIdActivite() ?? 0),
            'id_desc' => ($right->getIdActivite() ?? 0) <=> ($left->getIdActivite() ?? 0),
            'nom_asc' => strcmp($this->normalizeSearchValue((string) $left->getNomActivite()), $this->normalizeSearchValue((string) $right->getNomActivite())),
            'nom_desc' => strcmp($this->normalizeSearchValue((string) $right->getNomActivite()), $this->normalizeSearchValue((string) $left->getNomActivite())),
            'date_asc' => $this->compareActivityDates($left, $right),
            'capacite_asc' => ($left->getCapacite() ?? 0) <=> ($right->getCapacite() ?? 0),
            'capacite_desc' => ($right->getCapacite() ?? 0) <=> ($left->getCapacite() ?? 0),
            default => $this->compareActivityDates($right, $left),
        };
    }

    private function compareActivityDates(ActiviteEcologique $left, ActiviteEcologique $right): int
    {
        $leftDate = $left->getDateActivite();
        $rightDate = $right->getDateActivite();

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

    private function normalizeSearchValue(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);

        return $ascii !== false ? $ascii : $normalized;
    }

    #[Route('/new', name: 'app_activite_ecologique_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
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

            if (!$isRecurring) {
                $entityManager->persist($activiteEcologique);
                $entityManager->flush();

                $this->addFlash('success', 'Activité écologique créée avec succès.');

                if ($fromFront) {
                    return $this->redirect($this->generateUrl('app_home') . '#slide09', Response::HTTP_SEE_OTHER);
                }

                return $this->redirectToRoute('app_activite_ecologique_index', [], Response::HTTP_SEE_OTHER);
            }

            $startDate = $activiteEcologique->getDate_activite();
            $endDate = $form->get('recurrence_end_date')->getData();
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

                $dates = [];
                $cursor = \DateTime::createFromFormat('Y-m-d', $startDate->format('Y-m-d'));
                $limit = \DateTime::createFromFormat('Y-m-d', $endDate->format('Y-m-d'));

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
                'form' => $form,
            ]);
        }

        return $this->render('activite_ecologique/new.html.twig', [
            'activite_ecologique' => $activiteEcologique,
            'form' => $form,
        ]);
    }

    #[Route('/{id_activite}', name: 'app_activite_ecologique_show', methods: ['GET'])]
    public function show(ActiviteEcologique $activiteEcologique): Response
    {
        return $this->render('activite_ecologique/show.html.twig', [
            'activite_ecologique' => $activiteEcologique,
        ]);
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

    #[Route('/{id_activite}', name: 'app_activite_ecologique_delete', methods: ['POST'])]
    public function delete(Request $request, ActiviteEcologique $activiteEcologique, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$activiteEcologique->getId_activite(), $request->getPayload()->getString('_token'))) {
            // Keep reservations history even if the linked activity is deleted.
            foreach ($activiteEcologique->getReservations() as $reservation) {
                $reservation->setActiviteEcologique(null);
            }

            $entityManager->remove($activiteEcologique);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_activite_ecologique_index', [], Response::HTTP_SEE_OTHER);
    }
}
