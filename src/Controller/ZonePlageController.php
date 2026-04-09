<?php

namespace App\Controller;

use App\Entity\ZonePlage;
use App\Form\ZonePlageType;
use App\Repository\ZonePlageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/zone/plage')]
final class ZonePlageController extends AbstractController
{
    #[Route(name: 'app_zone_plage_index', methods: ['GET'])]
    public function index(ZonePlageRepository $zonePlageRepository): Response
    {
        return $this->render('zone_plage/index.html.twig', [
            'zone_plages' => $zonePlageRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_zone_plage_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $zonePlage = new ZonePlage();
        $form = $this->createForm(ZonePlageType::class, $zonePlage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($zonePlage);
            $entityManager->flush();

            return $this->redirectToRoute('app_zone_plage_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('zone_plage/new.html.twig', [
            'zone_plage' => $zonePlage,
            'form' => $form,
        ]);
    }

    #[Route('/{id_zone}', name: 'app_zone_plage_show', methods: ['GET'])]
    public function show(ZonePlage $zonePlage): Response
    {
        return $this->render('zone_plage/show.html.twig', [
            'zone_plage' => $zonePlage,
        ]);
    }

    #[Route('/{id_zone}/edit', name: 'app_zone_plage_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ZonePlage $zonePlage, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ZonePlageType::class, $zonePlage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_zone_plage_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('zone_plage/edit.html.twig', [
            'zone_plage' => $zonePlage,
            'form' => $form,
        ]);
    }

    #[Route('/{id_zone}', name: 'app_zone_plage_delete', methods: ['POST'])]
    public function delete(Request $request, ZonePlage $zonePlage, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$zonePlage->getId_zone(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($zonePlage);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_zone_plage_index', [], Response::HTTP_SEE_OTHER);
    }
}
