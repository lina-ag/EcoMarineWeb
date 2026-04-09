<?php

namespace App\Controller;

use App\Entity\FauneMarine;
use App\Form\FauneMarineType;
use App\Repository\FauneMarineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/faune/marine')]
final class FauneMarineController extends AbstractController
{
    #[Route(name: 'app_faune_marine_index', methods: ['GET'])]
    public function index(FauneMarineRepository $fauneMarineRepository): Response
    {
        return $this->render('faune_marine/index.html.twig', [
            'faune_marines' => $fauneMarineRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_faune_marine_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $fauneMarine = new FauneMarine();
        $form = $this->createForm(FauneMarineType::class, $fauneMarine);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($fauneMarine);
            $entityManager->flush();

            return $this->redirectToRoute('app_faune_marine_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('faune_marine/new.html.twig', [
            'faune_marine' => $fauneMarine,
            'form' => $form,
        ]);
    }

    #[Route('/{id_animal}', name: 'app_faune_marine_show', methods: ['GET'])]
    public function show(FauneMarine $fauneMarine): Response
    {
        return $this->render('faune_marine/show.html.twig', [
            'faune_marine' => $fauneMarine,
        ]);
    }

    #[Route('/{id_animal}/edit', name: 'app_faune_marine_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, FauneMarine $fauneMarine, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FauneMarineType::class, $fauneMarine);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_faune_marine_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('faune_marine/edit.html.twig', [
            'faune_marine' => $fauneMarine,
            'form' => $form,
        ]);
    }

    #[Route('/{id_animal}', name: 'app_faune_marine_delete', methods: ['POST'])]
    public function delete(Request $request, FauneMarine $fauneMarine, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$fauneMarine->getId_animal(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($fauneMarine);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_faune_marine_index', [], Response::HTTP_SEE_OTHER);
    }
}
