<?php

namespace App\Controller;

use App\Entity\ActionNettoyage;
use App\Form\ActionNettoyageType;
use App\Repository\ActionNettoyageRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/action-nettoyage')]
final class ActionNettoyageController extends AbstractController
{
    #[Route('', name: 'app_action_nettoyage_index', methods: ['GET'])]
    public function index(
        Request $request,
        ActionNettoyageRepository $repository,
        PaginatorInterface $paginator
    ): Response {
        $search = trim((string) $request->query->get('search', ''));
        $period = (string) $request->query->get('period', 'all');

        $query = $repository->createQueryBuilder('a')
            ->orderBy('a.date_action', 'DESC')
            ->addOrderBy('a.id_action', 'DESC');

        if ($search !== '') {
            $query
                ->andWhere('LOWER(a.lieu) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        $today = new \DateTimeImmutable('today');

        if ($period === 'past') {
            $query
                ->andWhere('a.date_action < :today')
                ->setParameter('today', $today->format('Y-m-d'));
        } elseif ($period === 'upcoming') {
            $query
                ->andWhere('a.date_action >= :today')
                ->setParameter('today', $today->format('Y-m-d'));
        }

        $actionNettoyages = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            5
        );

        if ($request->isXmlHttpRequest()) {
            return $this->render('action_nettoyage/_results.html.twig', [
                'action_nettoyages' => $actionNettoyages,
            ]);
        }

        $allActions = $repository->createQueryBuilder('a')
            ->orderBy('a.date_action', 'DESC')
            ->getQuery()
            ->getResult();

        $stats = $this->buildStats($allActions);
        $topLocations = $this->buildTopLocations($allActions);
        $monthlySeries = $this->buildMonthlySeries($allActions);
        $recentPastActions = array_slice(array_values(array_filter(
            $allActions,
            static fn(ActionNettoyage $action): bool => $action->getDateAction() instanceof \DateTimeInterface
                && \DateTimeImmutable::createFromInterface($action->getDateAction())->setTime(0, 0) < $today
        )), 0, 5);

        return $this->render('action_nettoyage/index.html.twig', [
            'action_nettoyages' => $actionNettoyages,
            'search' => $search,
            'period' => $period,
            'stats' => $stats,
            'top_locations' => $topLocations,
            'monthly_series' => $monthlySeries,
            'recent_past_actions' => $recentPastActions,
        ]);
    }

    #[Route('/rapport', name: 'app_action_nettoyage_report', methods: ['GET'])]
    public function report(ActionNettoyageRepository $repository): Response
    {
        $reportData = $this->buildReportData($repository);

        return $this->render('action_nettoyage/report.html.twig', $reportData);
    }

    #[Route('/rapport/pdf', name: 'app_action_nettoyage_report_pdf', methods: ['GET'])]
    public function reportPdf(ActionNettoyageRepository $repository, Pdf $snappyPdf): Response
    {
        $reportData = $this->buildReportData($repository);
        $reportData['generated_at'] = new \DateTimeImmutable();

        $html = $this->renderView('action_nettoyage/report_pdf.html.twig', $reportData);

        try {
            $pdfContent = $snappyPdf->getOutputFromHtml($html, [
                'enable-local-file-access' => true,
                'margin-top' => 12,
                'margin-right' => 10,
                'margin-bottom' => 12,
                'margin-left' => 10,
            ]);
        } catch (\Throwable) {
            $options = new Options();
            $options->set('defaultFont', 'DejaVu Sans');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            $pdfContent = $dompdf->output();
        }

        return new Response($pdfContent, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf(
                'attachment; filename="rapport_actions_nettoyage_%s.pdf"',
                (new \DateTimeImmutable())->format('Y-m-d')
            ),
        ]);
    }

    #[Route('/new', name: 'app_action_nettoyage_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $actionNettoyage = new ActionNettoyage();

        $form = $this->createForm(ActionNettoyageType::class, $actionNettoyage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($actionNettoyage);
            $entityManager->flush();

            $this->addFlash('success', 'Action de nettoyage ajoutee avec succes.');

            return $this->redirectToRoute('app_action_nettoyage_index');
        }

        return $this->render('action_nettoyage/new.html.twig', [
            'action_nettoyage' => $actionNettoyage,
            'form' => $form,
        ]);
    }

    #[Route('/{id_action}', name: 'app_action_nettoyage_show', methods: ['GET'])]
    public function show(ActionNettoyage $actionNettoyage): Response
    {
        return $this->render('action_nettoyage/show.html.twig', [
            'action_nettoyage' => $actionNettoyage,
        ]);
    }

    #[Route('/{id_action}/edit', name: 'app_action_nettoyage_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        ActionNettoyage $actionNettoyage,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(ActionNettoyageType::class, $actionNettoyage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Action de nettoyage modifiee avec succes.');

            return $this->redirectToRoute('app_action_nettoyage_index');
        }

        return $this->render('action_nettoyage/edit.html.twig', [
            'action_nettoyage' => $actionNettoyage,
            'form' => $form,
        ]);
    }

    #[Route('/{id_action}', name: 'app_action_nettoyage_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        ActionNettoyage $actionNettoyage,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $actionNettoyage->getIdAction(), $request->request->get('_token'))) {
            $entityManager->remove($actionNettoyage);
            $entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true]);
            }

            $this->addFlash('success', 'Action de nettoyage supprimee avec succes.');
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => false], Response::HTTP_BAD_REQUEST);
        }

        return $this->redirectToRoute('app_action_nettoyage_index');
    }

    /**
     * @param ActionNettoyage[] $actions
     * @return array<string, int|float>
     */
    private function buildStats(array $actions): array
    {
        $today = new \DateTimeImmutable('today');
        $past = 0;
        $upcoming = 0;
        $complete = 0;
        $participants = 0;
        $capacity = 0;

        foreach ($actions as $action) {
            $date = $action->getDateAction();
            if ($date instanceof \DateTimeInterface) {
                $dateValue = \DateTimeImmutable::createFromInterface($date)->setTime(0, 0);
                if ($dateValue < $today) {
                    $past++;
                } else {
                    $upcoming++;
                }
            }

            $participants += $action->getNombreVolontaires();
            $capacity += (int) ($action->getLimiteBenevoles() ?? 0);

            if ($action->estComplete()) {
                $complete++;
            }
        }

        $fillRate = $capacity > 0 ? round(($participants / $capacity) * 100, 1) : 0.0;

        return [
            'total_actions' => count($actions),
            'past_actions' => $past,
            'upcoming_actions' => $upcoming,
            'complete_actions' => $complete,
            'total_participants' => $participants,
            'fill_rate' => $fillRate,
        ];
    }

    /**
     * @param ActionNettoyage[] $actions
     * @return array<int, array{label:string, value:int, height:float}>
     */
    private function buildTopLocations(array $actions): array
    {
        $counts = [];
        foreach ($actions as $action) {
            $label = trim((string) $action->getLieu());
            if ($label === '') {
                $label = 'Inconnu';
            }
            $counts[$label] = ($counts[$label] ?? 0) + 1;
        }

        arsort($counts);
        $counts = array_slice($counts, 0, 5, true);
        $max = $counts ? max($counts) : 0;
        $result = [];

        foreach ($counts as $label => $value) {
            $result[] = [
                'label' => $label,
                'value' => $value,
                'height' => $max > 0 ? round(($value / $max) * 100, 1) : 0.0,
            ];
        }

        return $result;
    }

    /**
     * @param ActionNettoyage[] $actions
     * @return array<int, array{label:string, value:int, height:float}>
     */
    private function buildMonthlySeries(array $actions, int $months = 6): array
    {
        $start = (new \DateTimeImmutable('first day of -' . ($months - 1) . ' months'))->setTime(0, 0);
        $points = [];

        for ($i = 0; $i < $months; $i++) {
            $monthDate = $start->modify('+' . $i . ' months');
            $points[$monthDate->format('Y-m')] = [
                'label' => $monthDate->format('m/Y'),
                'value' => 0,
                'height' => 0.0,
            ];
        }

        foreach ($actions as $action) {
            $date = $action->getDateAction();
            if (!$date instanceof \DateTimeInterface) {
                continue;
            }

            $key = \DateTimeImmutable::createFromInterface($date)->format('Y-m');
            if (isset($points[$key])) {
                $points[$key]['value']++;
            }
        }

        $max = 0;
        foreach ($points as $point) {
            if ($point['value'] > $max) {
                $max = $point['value'];
            }
        }

        foreach ($points as &$point) {
            $point['height'] = $max > 0 ? round(($point['value'] / $max) * 100, 1) : 0.0;
        }
        unset($point);

        return array_values($points);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReportData(ActionNettoyageRepository $repository): array
    {
        $today = new \DateTimeImmutable('today');
        $allActions = $repository->createQueryBuilder('a')
            ->orderBy('a.date_action', 'DESC')
            ->getQuery()
            ->getResult();

        $pastActions = array_values(array_filter(
            $allActions,
            static fn(ActionNettoyage $action): bool => $action->getDateAction() instanceof \DateTimeInterface
                && \DateTimeImmutable::createFromInterface($action->getDateAction())->setTime(0, 0) < $today
        ));

        $reportRows = [];
        $participantsInPast = 0;

        foreach ($pastActions as $action) {
            $volunteers = [];
            foreach ($action->getVolontaires() as $volontaire) {
                $volunteers[] = [
                    'nom' => $volontaire->getNom(),
                    'contact' => $volontaire->getContact(),
                ];
            }

            $participants = count($volunteers);
            $participantsInPast += $participants;
            $capacity = (int) ($action->getLimiteBenevoles() ?? 0);

            $reportRows[] = [
                'id' => $action->getIdAction(),
                'lieu' => $action->getLieu(),
                'date' => $action->getDateAction(),
                'participants' => $participants,
                'capacity' => $capacity,
                'fill_rate' => $capacity > 0 ? round(($participants / $capacity) * 100, 1) : 0.0,
                'volunteers' => $volunteers,
            ];
        }

        $stats = $this->buildStats($allActions);
        $stats['past_participants'] = $participantsInPast;
        $stats['past_actions_reported'] = count($reportRows);

        return [
            'stats' => $stats,
            'report_rows' => $reportRows,
            'generated_at' => new \DateTimeImmutable(),
            'top_locations' => $this->buildTopLocations($pastActions),
            'regional_insights' => $this->buildRegionalInsights($pastActions),
        ];
    }

    /**
     * @param ActionNettoyage[] $actions
     * @return array<string, mixed>
     */
    private function buildRegionalInsights(array $actions): array
    {
        $regions = [];

        foreach ($actions as $action) {
            $region = trim((string) $action->getLieu());
            if ($region === '') {
                $region = 'Inconnue';
            }

            if (!isset($regions[$region])) {
                $regions[$region] = [
                    'region' => $region,
                    'actions' => 0,
                    'participants' => 0,
                    'capacity' => 0,
                    'avg_fill_rate' => 0.0,
                ];
            }

            $participants = $action->getNombreVolontaires();
            $capacity = (int) ($action->getLimiteBenevoles() ?? 0);

            $regions[$region]['actions']++;
            $regions[$region]['participants'] += $participants;
            $regions[$region]['capacity'] += $capacity;
        }

        foreach ($regions as &$region) {
            $region['avg_fill_rate'] = $region['capacity'] > 0
                ? round(($region['participants'] / $region['capacity']) * 100, 1)
                : 0.0;
        }
        unset($region);

        usort($regions, static function (array $a, array $b): int {
            if ($a['actions'] === $b['actions']) {
                return $b['participants'] <=> $a['participants'];
            }

            return $b['actions'] <=> $a['actions'];
        });

        $bestRegion = null;
        $bestScore = -1.0;
        $needsSupportRegion = null;
        $lowestScore = null;

        foreach ($regions as $region) {
            $score = ($region['actions'] * 10) + $region['avg_fill_rate'];

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestRegion = $region;
            }

            if ($lowestScore === null || $score < $lowestScore) {
                $lowestScore = $score;
                $needsSupportRegion = $region;
            }
        }

        $summary = 'Aucune conclusion regionale disponible.';

        if ($bestRegion && $needsSupportRegion) {
            $summary = sprintf(
                'La region la plus active est %s avec %d action(s) deja realisee(s), %d benevole(s) mobilise(s) et un taux moyen de remplissage de %.1f%%. A l inverse, %s semble necessiter davantage d organisation ou de mobilisation, avec %d action(s) et un taux moyen de remplissage de %.1f%%.',
                $bestRegion['region'],
                $bestRegion['actions'],
                $bestRegion['participants'],
                $bestRegion['avg_fill_rate'],
                $needsSupportRegion['region'],
                $needsSupportRegion['actions'],
                $needsSupportRegion['avg_fill_rate']
            );
        }

        return [
            'regions' => $regions,
            'best_region' => $bestRegion,
            'needs_support_region' => $needsSupportRegion,
            'summary' => $summary,
        ];
    }
}
