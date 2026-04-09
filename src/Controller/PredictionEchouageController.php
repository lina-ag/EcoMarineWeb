<?php

namespace App\Controller;

use App\Entity\PredictionEchouage;
use App\Form\PredictionEchouageType;
use App\Repository\PredictionEchouageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/prediction/echouage')]
final class PredictionEchouageController extends AbstractController
{
    #[Route(name: 'app_prediction_echouage_index', methods: ['GET'])]
    public function index(PredictionEchouageRepository $predictionEchouageRepository): Response
    {
        return $this->render('prediction_echouage/index.html.twig', [
            'prediction_echouages' => $predictionEchouageRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_prediction_echouage_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $predictionEchouage = new PredictionEchouage();
        $form = $this->createForm(PredictionEchouageType::class, $predictionEchouage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($predictionEchouage);
            $entityManager->flush();

            return $this->redirectToRoute('app_prediction_echouage_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('prediction_echouage/new.html.twig', [
            'prediction_echouage' => $predictionEchouage,
            'form' => $form,
        ]);
    }

    #[Route('/{id_prediction}', name: 'app_prediction_echouage_show', methods: ['GET'])]
    public function show(PredictionEchouage $predictionEchouage): Response
    {
        return $this->render('prediction_echouage/show.html.twig', [
            'prediction_echouage' => $predictionEchouage,
        ]);
    }

    #[Route('/{id_prediction}/edit', name: 'app_prediction_echouage_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PredictionEchouage $predictionEchouage, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PredictionEchouageType::class, $predictionEchouage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_prediction_echouage_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('prediction_echouage/edit.html.twig', [
            'prediction_echouage' => $predictionEchouage,
            'form' => $form,
        ]);
    }

    #[Route('/{id_prediction}', name: 'app_prediction_echouage_delete', methods: ['POST'])]
    public function delete(Request $request, PredictionEchouage $predictionEchouage, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$predictionEchouage->getId_prediction(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($predictionEchouage);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_prediction_echouage_index', [], Response::HTTP_SEE_OTHER);
    }
}
