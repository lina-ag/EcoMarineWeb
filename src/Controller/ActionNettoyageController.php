<?php

namespace App\Controller;

use App\Entity\ActionNettoyage;
use App\Form\ActionNettoyageType;
use App\Repository\ActionNettoyageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/action-nettoyage')]
final class ActionNettoyageController extends AbstractController
{
    #[Route('', name: 'app_action_nettoyage_index', methods: ['GET'])]
    public function index(ActionNettoyageRepository $repository): Response
    {
        return $this->render('action_nettoyage/index.html.twig', [
            'action_nettoyages' => $repository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_action_nettoyage_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $actionNettoyage = new ActionNettoyage();
        $form = $this->createForm(ActionNettoyageType::class, $actionNettoyage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($actionNettoyage);
            $entityManager->flush();

            $this->addFlash('success', 'Action de nettoyage ajoutée avec succès.');
            return $this->redirectToRoute('app_action_nettoyage_index');
        }

        return $this->render('action_nettoyage/new.html.twig', [
            'action_nettoyage' => $actionNettoyage,
            'form' => $form,
        ]);
    }

    #[Route('/{id_action}', name: 'app_action_nettoyage_show', methods: ['GET'])]
    public function show(ActionNettoyage $actionNettoyage): Response
    {
        return $this->render('action_nettoyage/show.html.twig', [
            'action_nettoyage' => $actionNettoyage,
        ]);
    }

    #[Route('/{id_action}/edit', name: 'app_action_nettoyage_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ActionNettoyage $actionNettoyage, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ActionNettoyageType::class, $actionNettoyage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Action de nettoyage modifiée avec succès.');
            return $this->redirectToRoute('app_action_nettoyage_index');
        }

        return $this->render('action_nettoyage/edit.html.twig', [
            'action_nettoyage' => $actionNettoyage,
            'form' => $form,
        ]);
    }

    #[Route('/{id_action}', name: 'app_action_nettoyage_delete', methods: ['POST'])]
    public function delete(Request $request, ActionNettoyage $actionNettoyage, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $actionNettoyage->getIdAction(), $request->request->get('_token'))) {
            $entityManager->remove($actionNettoyage);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_action_nettoyage_index');
    }
}
