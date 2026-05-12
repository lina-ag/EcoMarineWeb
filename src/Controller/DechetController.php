<?php

namespace App\Controller;

use App\Entity\Dechet;
use App\Form\DechetType;
use App\Repository\DechetRepository;
use App\Service\AiWasteAnalyzerService;
use App\Service\DechetPriorityService;
use App\Service\WeatherService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/dechet')]
final class DechetController extends AbstractController
{
    #[Route(name: 'app_dechet_index', methods: ['GET'])]
    public function index(DechetRepository $dechetRepository): Response
    {
        $dechets = $dechetRepository->findAll();

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

        return $this->render('dechet/index.html.twig', [
            'dechets' => $dechets,
            'statsCards' => $statsCards,
            'statusCounts' => $statusCounts,
            'filters' => [
                'q' => '',
                'type' => '',
                'statut' => '',
                'zone' => '',
                'sort' => 'date',
                'direction' => 'desc',
            ],
        ]);
    }

    #[Route('/new', name: 'app_dechet_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        WeatherService $weatherService,
        AiWasteAnalyzerService $aiWasteAnalyzerService,
        DechetPriorityService $priorityService,
        DechetRepository $dechetRepository
    ): Response {
        $dechet = new Dechet();
        $form = $this->createForm(DechetType::class, $dechet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photoFile')->getData();
            $uploadedPath = null;

            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename ?: 'dechet');
                $extension = $photoFile->guessExtension() ?: 'jpg';
                $newFilename = $safeFilename.'-'.uniqid().'.'.$extension;

                try {
                    $targetDir = $this->getParameter('kernel.project_dir').'/public/uploads/dechets';

                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0777, true);
                    }

                    $photoFile->move($targetDir, $newFilename);
                    $dechet->setPhoto($newFilename);
                    $uploadedPath = $targetDir.'/'.$newFilename;
                } catch (FileException $e) {
                    $this->addFlash('error', "Erreur lors de l'upload de l'image.");
                }
            }

            $latitude = $form->get('latitude')->getData();
            $longitude = $form->get('longitude')->getData();

            if ($latitude !== null && $latitude !== '') {
                $dechet->setLatitude((float) $latitude);
            }

            if ($longitude !== null && $longitude !== '') {
                $dechet->setLongitude((float) $longitude);
            }

            $weatherMain = null;
            $weatherWind = 0.0;

            try {
                $weather = $weatherService->getWeatherForCity('Monastir');

                $weatherMain = $weather['condition'] ?? null;
                $weatherWind = (float) ($weather['wind_speed'] ?? 0);

                $dechet->setWeatherMain($weatherMain);
                $dechet->setWeatherWind($weatherWind);
            } catch (\Throwable $e) {
                $this->addFlash('warning', 'Meteo indisponible pour ce signalement.');
            }

            $aiType = null;
            $aiConfidence = 0.0;
            $aiSummary = null;
            $aiRecommendedAction = null;

            if ($uploadedPath && file_exists($uploadedPath)) {
                try {
                    $ai = $aiWasteAnalyzerService->analyzeImage($uploadedPath, $dechet->getType(), $dechet->getDescription(), $dechet->getZone(), $dechet->getQuantite(), $dechet->getStatut());

                    $aiType = $ai['type_suggestion'] ?? null;
                    $aiConfidence = (float) ($ai['confidence'] ?? 0);
                    $aiSummary = $ai['summary'] ?? null;
                    $aiRecommendedAction = $ai['recommended_action'] ?? null;

                    $dechet->setAiTypeSuggestion($aiType);
                    $dechet->setAiConfidence($aiConfidence);
                    $dechet->setAiSummary($aiSummary);
                } catch (\Throwable $e) {
                    $this->addFlash('warning', 'Analyse IA indisponible pour cette image.');
                }
            }

            $sameZoneCount = count($dechetRepository->findBy(['zone' => $dechet->getZone()]));

            $priority = $priorityService->compute(
                (float) ($dechet->getQuantite() ?? 0),
                $weatherMain,
                $weatherWind,
                $aiType,
                $aiConfidence,
                $sameZoneCount
            );

            $dechet->setPriorityScore((int) $priority['score']);
            $dechet->setPriorityLabel((string) $priority['label']);
            $dechet->setRecommendedAction($aiRecommendedAction ?: (string) $priority['recommendation']);

            $entityManager->persist($dechet);
            $entityManager->flush();

            return $this->redirectToRoute('app_dechet_show', ['id_dechet' => $dechet->getIdDechet()]);
        }

        return $this->render('dechet/new.html.twig', [
            'dechet' => $dechet,
            'form' => $form,
        ]);
    }

    #[Route('/{id_dechet}', name: 'app_dechet_show', methods: ['GET'])]
    public function show(Dechet $dechet): Response
    {
        return $this->render('dechet/show.html.twig', [
            'dechet' => $dechet,
        ]);
    }

    #[Route('/{id_dechet}/edit', name: 'app_dechet_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Dechet $dechet, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DechetType::class, $dechet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_dechet_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dechet/edit.html.twig', [
            'dechet' => $dechet,
            'form' => $form,
        ]);
    }

    #[Route('/{id_dechet}', name: 'app_dechet_delete', methods: ['POST'])]
    public function delete(Request $request, Dechet $dechet, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$dechet->getId_dechet(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($dechet);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_dechet_index', [], Response::HTTP_SEE_OTHER);
    }
}