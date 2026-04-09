<?php

namespace App\Controller;

use App\Entity\Biodiversite;
use App\Form\BiodiversiteType;
use App\Repository\BiodiversiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/biodiversite')]
final class BiodiversiteController extends AbstractController
{
    #[Route(name: 'app_biodiversite_index', methods: ['GET'])]
    public function index(BiodiversiteRepository $biodiversiteRepository): Response
    {
        return $this->render('biodiversite/index.html.twig', [
            'biodiversites' => $biodiversiteRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_biodiversite_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $biodiversite = new Biodiversite();
        $form = $this->createForm(BiodiversiteType::class, $biodiversite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($biodiversite);
            $entityManager->flush();

            return $this->redirectToRoute('app_biodiversite_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('biodiversite/new.html.twig', [
            'biodiversite' => $biodiversite,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_biodiversite_show', methods: ['GET'])]
    public function show(Biodiversite $biodiversite): Response
    {
        return $this->render('biodiversite/show.html.twig', [
            'biodiversite' => $biodiversite,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_biodiversite_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Biodiversite $biodiversite, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BiodiversiteType::class, $biodiversite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_biodiversite_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('biodiversite/edit.html.twig', [
            'biodiversite' => $biodiversite,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_biodiversite_delete', methods: ['POST'])]
    public function delete(Request $request, Biodiversite $biodiversite, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$biodiversite->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($biodiversite);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_biodiversite_index', [], Response::HTTP_SEE_OTHER);
    }
}
