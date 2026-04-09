<?php

namespace App\Controller;

use App\Entity\Volontaire;
use App\Form\VolontaireType;
use App\Repository\VolontaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/volontaire')]
final class VolontaireController extends AbstractController
{
    #[Route(name: 'app_volontaire_index', methods: ['GET'])]
    public function index(VolontaireRepository $volontaireRepository): Response
    {
        return $this->render('volontaire/index.html.twig', [
            'volontaires' => $volontaireRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_volontaire_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $volontaire = new Volontaire();
        $form = $this->createForm(VolontaireType::class, $volontaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($volontaire);
            $entityManager->flush();

            return $this->redirectToRoute('app_volontaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('volontaire/new.html.twig', [
            'volontaire' => $volontaire,
            'form' => $form,
        ]);
    }

    #[Route('/{id_volontaire}', name: 'app_volontaire_show', methods: ['GET'])]
    public function show(Volontaire $volontaire): Response
    {
        return $this->render('volontaire/show.html.twig', [
            'volontaire' => $volontaire,
        ]);
    }

    #[Route('/{id_volontaire}/edit', name: 'app_volontaire_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Volontaire $volontaire, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(VolontaireType::class, $volontaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_volontaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('volontaire/edit.html.twig', [
            'volontaire' => $volontaire,
            'form' => $form,
        ]);
    }

    #[Route('/{id_volontaire}', name: 'app_volontaire_delete', methods: ['POST'])]
    public function delete(Request $request, Volontaire $volontaire, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$volontaire->getId_volontaire(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($volontaire);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_volontaire_index', [], Response::HTTP_SEE_OTHER);
    }
}
