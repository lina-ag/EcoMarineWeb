<?php

namespace App\Controller;

use App\Entity\Dechet;
use App\Form\DechetType;
use App\Repository\DechetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dechet')]
final class DechetController extends AbstractController
{
    #[Route(name: 'app_dechet_index', methods: ['GET'])]
    public function index(Request $request, DechetRepository $dechetRepository): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $type = trim((string) $request->query->get('type', ''));
        $statut = trim((string) $request->query->get('statut', ''));
        $zone = trim((string) $request->query->get('zone', ''));

        $dechets = $dechetRepository->findAll();

        $dechets = array_values(array_filter($dechets, function (Dechet $dechet) use ($q, $type, $statut, $zone) {
            if ($q !== '') {
                $haystack = mb_strtolower(
                    ($dechet->getType() ?? '') . ' ' .
                    ($dechet->getZone() ?? '') . ' ' .
                    ($dechet->getDescription() ?? '') . ' ' .
                    ($dechet->getStatut() ?? '')
                );

                if (!str_contains($haystack, mb_strtolower($q))) {
                    return false;
                }
            }

            if ($type !== '' && $dechet->getType() !== $type) {
                return false;
            }

            if ($statut !== '' && $dechet->getStatut() !== $statut) {
                return false;
            }

            if ($zone !== '' && !str_contains(mb_strtolower($dechet->getZone() ?? ''), mb_strtolower($zone))) {
                return false;
            }

            return true;
        }));

        $totalSignalements = count($dechets);
        $totalQuantite = 0;
        $zones = [];
        $pollutionElevee = 0;

        foreach ($dechets as $dechet) {
            $quantite = $dechet->getQuantite() ?? 0;
            $totalQuantite += $quantite;

            $zoneName = trim((string) $dechet->getZone());
            if ($zoneName !== '') {
                $zones[$zoneName] = true;
            }

            if ($quantite > 15) {
                $pollutionElevee++;
            }
        }

        return $this->render('dechet/index.html.twig', [
            'dechets' => $dechets,
            'filters' => [
                'q' => $q,
                'type' => $type,
                'statut' => $statut,
                'zone' => $zone,
            ],
            'statsCards' => [
                'totalSignalements' => $totalSignalements,
                'totalQuantite' => $totalQuantite,
                'zonesTouchees' => count($zones),
                'pollutionElevee' => $pollutionElevee,
            ],
        ]);
    }

    #[Route('/new', name: 'app_dechet_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $dechet = new Dechet();
        $form = $this->createForm(DechetType::class, $dechet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($dechet);
            $entityManager->flush();

            return $this->redirectToRoute('app_dechet_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dechet/new.html.twig', [
            'dechet' => $dechet,
            'form' => $form,
        ]);
    }

    #[Route('/{id_dechet<\d+>}', name: 'app_dechet_show', methods: ['GET'])]
    public function show(Dechet $dechet): Response
    {
        return $this->render('dechet/show.html.twig', [
            'dechet' => $dechet,
        ]);
    }

    #[Route('/{id_dechet<\d+>}/edit', name: 'app_dechet_edit', methods: ['GET', 'POST'])]
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

    #[Route('/{id_dechet<\d+>}/traiter', name: 'app_dechet_traiter', methods: ['POST'])]
    public function traiter(Request $request, Dechet $dechet, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('traiter' . $dechet->getId_dechet(), $request->request->get('_token'))) {
            $dechet->setStatut('traite');
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_dechet_index', $request->query->all(), Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id_dechet<\d+>}', name: 'app_dechet_delete', methods: ['POST'])]
    public function delete(Request $request, Dechet $dechet, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $dechet->getId_dechet(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($dechet);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_dechet_index', [], Response::HTTP_SEE_OTHER);
    }
}