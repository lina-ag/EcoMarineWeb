<?php

namespace App\Controller;

use App\Repository\DechetRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DechetStatsController extends AbstractController
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
        $prioriteLabels = [
            'faible' => 0,
            'moyenne' => 0,
            'elevee' => 0,
            'critique' => 0,
        ];

        foreach ($dechets as $dechet) {
            $totalQuantite += $dechet->getQuantite() ?? 0;

            $type = $dechet->getType() ?? 'Inconnu';
            $zone = $dechet->getZone() ?? 'Inconnue';
            $statut = $dechet->getStatut() ?? 'Inconnu';
            $priorityLabel = $dechet->getPriorityLabel() ?? 'faible';

            $parType[$type] = ($parType[$type] ?? 0) + 1;
            $parZone[$zone] = ($parZone[$zone] ?? 0) + 1;
            $parStatut[$statut] = ($parStatut[$statut] ?? 0) + 1;

            if (isset($prioriteLabels[$priorityLabel])) {
                $prioriteLabels[$priorityLabel]++;
            }
        }

        arsort($parZone);
        arsort($parType);

        $criticalZones = method_exists($dechetRepository, 'findCriticalZones')
            ? $dechetRepository->findCriticalZones()
            : [];

        $topZone = array_key_first($parZone) ?? 'Aucune';
        $topType = array_key_first($parType) ?? 'Aucun';
        $topCriticalZone = $criticalZones[0]['zone'] ?? 'Aucune';
        $topCriticalScore = $criticalZones[0]['avgScore'] ?? 0;

        $smartInsight = 'Le systeme ne detecte pas encore de zone fortement critique.';
        if (!empty($criticalZones)) {
            $smartInsight = sprintf(
                'La zone %s est actuellement la plus sensible avec un score moyen de %s et %s signalement(s).',
                $topCriticalZone,
                $topCriticalScore,
                $criticalZones[0]['totalSignalements']
            );
        }

        $globalRecommendation = 'Maintenir une surveillance normale.';
        if (($prioriteLabels['critique'] ?? 0) > 0) {
            $globalRecommendation = 'Deployer une intervention immediate sur les zones critiques.';
        } elseif (($prioriteLabels['elevee'] ?? 0) > 0) {
            $globalRecommendation = 'Planifier un nettoyage rapide sur les zones a priorite elevee.';
        } elseif (($prioriteLabels['moyenne'] ?? 0) > 0) {
            $globalRecommendation = 'Traiter les signalements moyens sous 48h.';
        }

        return $this->render('dechet/stats.html.twig', [
            'totalDechets' => $totalDechets,
            'totalQuantite' => $totalQuantite,
            'parType' => $parType,
            'parZone' => $parZone,
            'parStatut' => $parStatut,
            'prioriteLabels' => $prioriteLabels,
            'criticalZones' => $criticalZones,
            'topZone' => $topZone,
            'topType' => $topType,
            'smartInsight' => $smartInsight,
            'globalRecommendation' => $globalRecommendation,
        ]);
    }
}