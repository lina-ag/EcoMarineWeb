<?php

namespace App\Controller;

use App\Repository\DechetRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
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

        return $this->render('dechet/stats.html.twig', [
            'totalDechets' => $totalDechets,
            'totalQuantite' => $totalQuantite,
            'parType' => $parType,
            'parZone' => $parZone,
            'parStatut' => $parStatut,
        ]);
    }
}