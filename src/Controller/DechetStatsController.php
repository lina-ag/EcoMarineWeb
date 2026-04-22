<?php

namespace App\Controller;

use App\Repository\DechetRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

final class DechetStatsController extends AbstractController
{
    #[Route('/dechet/stats', name: 'app_dechet_stats', methods: ['GET'])]
    public function index(DechetRepository $dechetRepository): Response
    {
        $dechets = $dechetRepository->findAll();

        $totalDechets = count($dechets);
        $totalQuantite = 0;
        $parType = [];
        $parZone = [];
        $parStatut = [];

        foreach ($dechets as $dechet) {
            $totalQuantite += $dechet->getQuantite() ?? 0;

            $type = $dechet->getType() ?? 'Inconnu';
            $zone = $dechet->getZone() ?? 'Inconnue';
            $statut = $dechet->getStatut() ?? 'Inconnu';

            $parType[$type] = ($parType[$type] ?? 0) + 1;
            $parZone[$zone] = ($parZone[$zone] ?? 0) + 1;
            $parStatut[$statut] = ($parStatut[$statut] ?? 0) + 1;
        }

        arsort($parType);
        arsort($parZone);
        arsort($parStatut);

        $topType = !empty($parType) ? array_key_first($parType) : 'Aucune donnée';
        $topZone = !empty($parZone) ? array_key_first($parZone) : 'Aucune donnée';

        $zoneRanking = array_slice($parZone, 0, 3, true);

        $chartLabels = array_keys($parType);
        $chartValues = array_values($parType);

        $progressionNettoyage = $totalDechets > 0 && isset($parStatut['traite'])
            ? round(($parStatut['traite'] / $totalDechets) * 100)
            : 0;

        $impact = 'Faible';
        if ($totalQuantite > 50) {
            $impact = 'Critique';
        } elseif ($totalQuantite > 20) {
            $impact = 'Modéré';
        }

        $insight = 'Aucune donnée disponible.';
        if ($totalDechets > 0) {
            $insight = sprintf(
                'La zone la plus touchée est %s et le type dominant est %s. Progression du nettoyage : %d%%.',
                $topZone,
                $topType,
                $progressionNettoyage
            );
        }

        return $this->render('dechet/stats.html.twig', [
            'totalDechets' => $totalDechets,
            'totalQuantite' => $totalQuantite,
            'parType' => $parType,
            'parZone' => $parZone,
            'parStatut' => $parStatut,
            'topType' => $topType,
            'topZone' => $topZone,
            'zoneRanking' => $zoneRanking,
            'chartLabels' => $chartLabels,
            'chartValues' => $chartValues,
            'progressionNettoyage' => $progressionNettoyage,
            'impact' => $impact,
            'insight' => $insight,
        ]);
    }

    #[Route('/dechet/export/csv', name: 'app_dechet_export_csv', methods: ['GET'])]
    public function exportCsv(DechetRepository $dechetRepository): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($dechetRepository) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['ID', 'Type', 'Quantite', 'Zone', 'Description', 'Date signalement', 'Statut']);

            foreach ($dechetRepository->findAll() as $dechet) {
                fputcsv($handle, [
                    $dechet->getIdDechet(),
                    $dechet->getType(),
                    $dechet->getQuantite(),
                    $dechet->getZone(),
                    $dechet->getDescription(),
                    $dechet->getDateSignalement()?->format('Y-m-d'),
                    $dechet->getStatut(),
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="dechets.csv"');

        return $response;
    }
}