<?php

namespace App\Controller;

use App\Entity\ActiviteEcologique;
use App\Form\ActiviteEcologiqueType;
use App\Repository\ActiviteEcologiqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/activite/ecologique')]
final class ActiviteEcologiqueController extends AbstractController
{
    #[Route(name: 'app_activite_ecologique_index', methods: ['GET'])]
    public function index(ActiviteEcologiqueRepository $activiteEcologiqueRepository): Response
    {
        return $this->render('activite_ecologique/index.html.twig', [
            'activite_ecologiques' => $activiteEcologiqueRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_activite_ecologique_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $activiteEcologique = new ActiviteEcologique();
        $form = $this->createForm(ActiviteEcologiqueType::class, $activiteEcologique);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($activiteEcologique);
            $entityManager->flush();

            return $this->redirectToRoute('app_activite_ecologique_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activite_ecologique/new.html.twig', [
            'activite_ecologique' => $activiteEcologique,
            'form' => $form,
        ]);
    }

    #[Route('/{id_activite}', name: 'app_activite_ecologique_show', methods: ['GET'])]
    public function show(ActiviteEcologique $activiteEcologique): Response
    {
        return $this->render('activite_ecologique/show.html.twig', [
            'activite_ecologique' => $activiteEcologique,
        ]);
    }

    #[Route('/{id_activite}/edit', name: 'app_activite_ecologique_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ActiviteEcologique $activiteEcologique, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ActiviteEcologiqueType::class, $activiteEcologique);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_activite_ecologique_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activite_ecologique/edit.html.twig', [
            'activite_ecologique' => $activiteEcologique,
            'form' => $form,
        ]);
    }

    #[Route('/{id_activite}', name: 'app_activite_ecologique_delete', methods: ['POST'])]
    public function delete(Request $request, ActiviteEcologique $activiteEcologique, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$activiteEcologique->getId_activite(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($activiteEcologique);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_activite_ecologique_index', [], Response::HTTP_SEE_OTHER);
    }
}
