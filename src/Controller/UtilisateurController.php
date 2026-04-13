<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/utilisateur')]
final class UtilisateurController extends AbstractController
{
    #[Route(name: 'app_utilisateur_index', methods: ['GET'])]
    public function index(UtilisateurRepository $utilisateurRepository): Response
    {
        
        return $this->render('utilisateur/index.html.twig', [
            'utilisateurs' => $utilisateurRepository->findAll(),
            
        ]);
    }

    #[Route('/new', name: 'app_utilisateur_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, UtilisateurRepository $utilisateurRepository): Response
    {
        $utilisateur = new Utilisateur();
        $utilisateur->setCreatedAt(new \DateTime());
        
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // 🔥 Vérifier que l'email n'existe pas déjà
            $existingUser = $utilisateurRepository->findOneBy(['email' => $utilisateur->getEmail()]);
            if ($existingUser) {
                $this->addFlash('error', 'Cet email est déjà utilisé par un autre utilisateur.');
                return $this->render('utilisateur/new.html.twig', [
                    'utilisateur' => $utilisateur,
                    'form' => $form,
                ]);
            }
            
            // 🔥 Vérifier que l'email est Gmail
            $email = $utilisateur->getEmail();
            if (!preg_match('/@gmail\.com$/', $email)) {
                $this->addFlash('error', 'L\'email doit être une adresse Gmail (@gmail.com)');
                return $this->render('utilisateur/new.html.twig', [
                    'utilisateur' => $utilisateur,
                    'form' => $form,
                ]);
            }
            
            // 🔥 Vérifier que la date de naissance est dans le passé
            $dateNaissance = $utilisateur->getDateNaissance();
            $today = new \DateTime();
            if ($dateNaissance >= $today) {
                $this->addFlash('error', 'La date de naissance doit être inférieure à la date d\'aujourd\'hui');
                return $this->render('utilisateur/new.html.twig', [
                    'utilisateur' => $utilisateur,
                    'form' => $form,
                ]);
            }
            
            // Validation manuelle
            $errors = $validator->validate($utilisateur);
            
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('utilisateur/new.html.twig', [
                    'utilisateur' => $utilisateur,
                    'form' => $form,
                ]);
            }
            
            $entityManager->persist($utilisateur);
            $entityManager->flush();
            
            $this->addFlash('success', 'Utilisateur créé avec succès !');

            return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('utilisateur/new.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form,
        ]);
    }

    #[Route('/{id_utilisateur}', name: 'app_utilisateur_show', methods: ['GET'])]
    public function show(Utilisateur $utilisateur): Response
    {
        return $this->render('utilisateur/show.html.twig', [
            'utilisateur' => $utilisateur,
        ]);
    }

    #[Route('/{id_utilisateur}/edit', name: 'app_utilisateur_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager, ValidatorInterface $validator, UtilisateurRepository $utilisateurRepository): Response
    {
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // 🔥 Vérifier que l'email n'existe pas déjà (sauf pour l'utilisateur actuel)
            $existingUser = $utilisateurRepository->findOneBy(['email' => $utilisateur->getEmail()]);
            if ($existingUser && $existingUser->getIdUtilisateur() !== $utilisateur->getIdUtilisateur()) {
                $this->addFlash('error', 'Cet email est déjà utilisé par un autre utilisateur.');
                return $this->render('utilisateur/edit.html.twig', [
                    'utilisateur' => $utilisateur,
                    'form' => $form,
                ]);
            }
            
            // 🔥 Vérifier que l'email est Gmail
            $email = $utilisateur->getEmail();
            if (!preg_match('/@gmail\.com$/', $email)) {
                $this->addFlash('error', 'L\'email doit être une adresse Gmail (@gmail.com)');
                return $this->render('utilisateur/edit.html.twig', [
                    'utilisateur' => $utilisateur,
                    'form' => $form,
                ]);
            }
            
           // 🔥 Vérifier que la date de naissance est strictement dans le passé (pas égale à aujourd'hui)
            $dateNaissance = $utilisateur->getDateNaissance();
            $today = new \DateTime();
            $today->setTime(0, 0, 0); // Mettre l'heure à minuit pour comparer uniquement les dates

            if ($dateNaissance >= $today) {
                $this->addFlash('error', 'La date de naissance doit être strictement inférieure à la date d\'aujourd\'hui');
                return $this->render('utilisateur/edit.html.twig', [
                    'utilisateur' => $utilisateur,
                    'form' => $form,
               ]);
            }
            
            $errors = $validator->validate($utilisateur);
            
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('utilisateur/edit.html.twig', [
                    'utilisateur' => $utilisateur,
                    'form' => $form,
                ]);
            }
            
            $entityManager->flush();
            
            $this->addFlash('success', 'Utilisateur modifié avec succès !');

            return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('utilisateur/edit.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form,
        ]);
    }

    #[Route('/{id_utilisateur}', name: 'app_utilisateur_delete', methods: ['POST'])]
    public function delete(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$utilisateur->getIdUtilisateur(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($utilisateur);
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur supprimé avec succès !');
        }

        return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
    }
}