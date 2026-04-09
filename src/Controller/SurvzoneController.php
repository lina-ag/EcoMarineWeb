<?php

namespace App\Controller;

use App\Entity\Survzone;
use App\Form\SurvzoneType;
use App\Repository\SurvzoneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/survzone')]
final class SurvzoneController extends AbstractController
{
    #[Route(name: 'app_survzone_index', methods: ['GET'])]
    public function index(SurvzoneRepository $survzoneRepository): Response
    {
        return $this->render('survzone/index.html.twig', [
            'survzones' => $survzoneRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_survzone_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $survzone = new Survzone();
        $form = $this->createForm(SurvzoneType::class, $survzone);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($survzone);
            $entityManager->flush();

            return $this->redirectToRoute('app_survzone_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('survzone/new.html.twig', [
            'survzone' => $survzone,
            'form' => $form,
        ]);
    }

    #[Route('/{idSurv}', name: 'app_survzone_show', methods: ['GET'])]
    public function show(Survzone $survzone): Response
    {
        return $this->render('survzone/show.html.twig', [
            'survzone' => $survzone,
        ]);
    }

    #[Route('/{idSurv}/edit', name: 'app_survzone_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Survzone $survzone, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SurvzoneType::class, $survzone);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_survzone_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('survzone/edit.html.twig', [
            'survzone' => $survzone,
            'form' => $form,
        ]);
    }

    #[Route('/{idSurv}', name: 'app_survzone_delete', methods: ['POST'])]
    public function delete(Request $request, Survzone $survzone, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$survzone->getIdSurv(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($survzone);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_survzone_index', [], Response::HTTP_SEE_OTHER);
    }
}
