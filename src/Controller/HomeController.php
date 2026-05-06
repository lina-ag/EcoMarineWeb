<?php

namespace App\Controller;

use App\Repository\DechetRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(Request $request, DechetRepository $dechetRepository): Response
    {
        $allowedTabs = ['overview', 'latest', 'documents', 'contact'];
        $tab = strtolower((string) $request->query->get('tab', 'overview'));

        if (!in_array($tab, $allowedTabs, true)) {
            $tab = 'overview';
        }

        $dechets = $dechetRepository->findBy([], ['id_dechet' => 'DESC']);

        $statsCards = [
            'totalSignalements' => count($dechets),
            'totalQuantite' => 0,
            'zonesTouchees' => 0,
            'pollutionElevee' => 0,
            'progressionNettoyage' => 0,
        ];

        $statusCounts = ['signale' => 0, 'en_cours' => 0, 'traite' => 0];
        $zones = [];

        foreach ($dechets as $dechet) {
            $statsCards['totalQuantite'] += (float) ($dechet->getQuantite() ?? 0);

            if ($dechet->getZone()) {
                $zones[$dechet->getZone()] = true;
            }

            if (((float) ($dechet->getQuantite() ?? 0)) > 15) {
                $statsCards['pollutionElevee']++;
            }

            $statut = $dechet->getStatut();
            if (isset($statusCounts[$statut])) {
                $statusCounts[$statut]++;
            }
        }

        $statsCards['zonesTouchees'] = count($zones);

        if ($statsCards['totalSignalements'] > 0) {
            $statsCards['progressionNettoyage'] = round(($statusCounts['traite'] / $statsCards['totalSignalements']) * 100, 1);
        }

        return $this->render('home/index.html.twig', [
            'activeTab' => $tab,
            'contact' => [
                'name' => 'FARHAT MERYEM',
                'role' => 'Specialiste de la gestion des dechets',
                'email' => 'meryemfarhat601@gmail.com',
                'region' => 'Kuriat, Monastir',
            ],
            'dechets' => $dechets,
            'statsCards' => $statsCards,
            'statusCounts' => $statusCounts,
        ]);
    }
}