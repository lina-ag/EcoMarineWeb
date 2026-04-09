<?php

namespace App\Controller;

use App\Entity\Observation;
use App\Form\ObservationType;
use App\Repository\ObservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/observation')]
final class ObservationController extends AbstractController
{
    #[Route(name: 'app_observation_index', methods: ['GET'])]
    public function index(ObservationRepository $observationRepository): Response
    {
        return $this->render('observation/index.html.twig', [
            'observations' => $observationRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_observation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $observation = new Observation();
        $form = $this->createForm(ObservationType::class, $observation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($observation);
            $entityManager->flush();

            return $this->redirectToRoute('app_observation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('observation/new.html.twig', [
            'observation' => $observation,
            'form' => $form,
        ]);
    }

    #[Route('/{id_observation}', name: 'app_observation_show', methods: ['GET'])]
    public function show(Observation $observation): Response
    {
        return $this->render('observation/show.html.twig', [
            'observation' => $observation,
        ]);
    }

    #[Route('/{id_observation}/edit', name: 'app_observation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Observation $observation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ObservationType::class, $observation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_observation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('observation/edit.html.twig', [
            'observation' => $observation,
            'form' => $form,
        ]);
    }

    #[Route('/{id_observation}', name: 'app_observation_delete', methods: ['POST'])]
    public function delete(Request $request, Observation $observation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$observation->getId_observation(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($observation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_observation_index', [], Response::HTTP_SEE_OTHER);
    }
}
