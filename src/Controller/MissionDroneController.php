<?php

namespace App\Controller;

use App\Entity\MissionDrone;
use App\Form\MissionDroneType;
use App\Repository\MissionDroneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mission/drone')]
final class MissionDroneController extends AbstractController
{
    #[Route(name: 'app_mission_drone_index', methods: ['GET'])]
    public function index(MissionDroneRepository $missionDroneRepository): Response
    {
        return $this->render('mission_drone/index.html.twig', [
            'mission_drones' => $missionDroneRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_mission_drone_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $missionDrone = new MissionDrone();
        $form = $this->createForm(MissionDroneType::class, $missionDrone);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($missionDrone);
            $entityManager->flush();

            return $this->redirectToRoute('app_mission_drone_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('mission_drone/new.html.twig', [
            'mission_drone' => $missionDrone,
            'form' => $form,
        ]);
    }

    #[Route('/{id_mission}', name: 'app_mission_drone_show', methods: ['GET'])]
    public function show(MissionDrone $missionDrone): Response
    {
        return $this->render('mission_drone/show.html.twig', [
            'mission_drone' => $missionDrone,
        ]);
    }

    #[Route('/{id_mission}/edit', name: 'app_mission_drone_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MissionDrone $missionDrone, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MissionDroneType::class, $missionDrone);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_mission_drone_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('mission_drone/edit.html.twig', [
            'mission_drone' => $missionDrone,
            'form' => $form,
        ]);
    }

    #[Route('/{id_mission}', name: 'app_mission_drone_delete', methods: ['POST'])]
    public function delete(Request $request, MissionDrone $missionDrone, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$missionDrone->getId_mission(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($missionDrone);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_mission_drone_index', [], Response::HTTP_SEE_OTHER);
    }
}
