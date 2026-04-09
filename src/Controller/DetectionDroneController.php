<?php

namespace App\Controller;

use App\Entity\DetectionDrone;
use App\Form\DetectionDroneType;
use App\Repository\DetectionDroneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/detection/drone')]
final class DetectionDroneController extends AbstractController
{
    #[Route(name: 'app_detection_drone_index', methods: ['GET'])]
    public function index(DetectionDroneRepository $detectionDroneRepository): Response
    {
        return $this->render('detection_drone/index.html.twig', [
            'detection_drones' => $detectionDroneRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_detection_drone_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $detectionDrone = new DetectionDrone();
        $form = $this->createForm(DetectionDroneType::class, $detectionDrone);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($detectionDrone);
            $entityManager->flush();

            return $this->redirectToRoute('app_detection_drone_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('detection_drone/new.html.twig', [
            'detection_drone' => $detectionDrone,
            'form' => $form,
        ]);
    }

    #[Route('/{id_detection}', name: 'app_detection_drone_show', methods: ['GET'])]
    public function show(DetectionDrone $detectionDrone): Response
    {
        return $this->render('detection_drone/show.html.twig', [
            'detection_drone' => $detectionDrone,
        ]);
    }

    #[Route('/{id_detection}/edit', name: 'app_detection_drone_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, DetectionDrone $detectionDrone, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DetectionDroneType::class, $detectionDrone);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_detection_drone_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('detection_drone/edit.html.twig', [
            'detection_drone' => $detectionDrone,
            'form' => $form,
        ]);
    }

    #[Route('/{id_detection}', name: 'app_detection_drone_delete', methods: ['POST'])]
    public function delete(Request $request, DetectionDrone $detectionDrone, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$detectionDrone->getId_detection(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($detectionDrone);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_detection_drone_index', [], Response::HTTP_SEE_OTHER);
    }
}
