<?php

namespace App\Controller;

use App\Entity\ActionNettoyage;
use App\Entity\Volontaire;
use App\Repository\VolontaireRepository;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/action-nettoyage')]
final class NettoyageParticipationController extends AbstractController
{
    #[Route('/{id_action}/participer', name: 'app_action_nettoyage_participer', methods: ['POST'])]
    public function participer(
        Request $request,
        ActionNettoyage $actionNettoyage,
        EntityManagerInterface $entityManager,
        VolontaireRepository $volontaireRepository,
        UtilisateurRepository $utilisateurRepository,
        SessionInterface $session
    ): Response {
        $userSession = $session->get('user');

        if (!$userSession instanceof Utilisateur) {
            $this->addFlash('warning', 'Vous devez être connecté pour participer à une action de nettoyage.');
            return $this->redirectToRoute('app_signIn');
        }

        $utilisateur = $utilisateurRepository->find($userSession->getIdUtilisateur());

        if (!$utilisateur) {
            $this->addFlash('danger', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_signIn');
        }

        if (!$this->isCsrfTokenValid('participer' . $actionNettoyage->getIdAction(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_home', ['_fragment' => 'slide12']);
        }

        if ($actionNettoyage->estComplete()) {
            $this->addFlash('danger', 'Cette action de nettoyage est déjà complète.');
            return $this->redirectToRoute('app_home', ['_fragment' => 'slide12']);
        }

        $dejaInscrit = $volontaireRepository->findOneBy([
            'utilisateur' => $utilisateur,
            'id_action' => $actionNettoyage,
        ]);

        if ($dejaInscrit) {
            $this->addFlash('warning', 'Vous êtes déjà inscrit à cette action de nettoyage.');
            return $this->redirectToRoute('app_home', ['_fragment' => 'slide12']);
        }

        $nomComplet = trim(($utilisateur->getNom() ?? '') . ' ' . ($utilisateur->getPrenom() ?? ''));

        if ($nomComplet === '') {
            $nomComplet = 'Utilisateur';
        }

        $contact = $utilisateur->getTelephone() ?: '00000000';

        $volontaire = new Volontaire();
        $volontaire->setNom($nomComplet);
        $volontaire->setContact($contact);
        $volontaire->setIdAction($actionNettoyage);
        $volontaire->setUtilisateur($utilisateur);

        $entityManager->persist($volontaire);
        $entityManager->flush();

        $this->addFlash('success', 'Votre participation a été enregistrée avec succès.');

        return $this->redirectToRoute('app_home', ['_fragment' => 'slide12']);
    }

    #[Route('/{id_action}/annuler-participation', name: 'app_action_nettoyage_annuler_participation', methods: ['POST'])]
    public function annulerParticipation(
        Request $request,
        ActionNettoyage $actionNettoyage,
        EntityManagerInterface $entityManager,
        VolontaireRepository $volontaireRepository,
        UtilisateurRepository $utilisateurRepository,
        SessionInterface $session
    ): Response {
        $userSession = $session->get('user');

        if (!$userSession instanceof Utilisateur) {
            $this->addFlash('warning', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_signIn');
        }

        $utilisateur = $utilisateurRepository->find($userSession->getIdUtilisateur());

        if (!$utilisateur) {
            $this->addFlash('danger', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_signIn');
        }

        if (!$this->isCsrfTokenValid('annuler_participation' . $actionNettoyage->getIdAction(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_home', ['_fragment' => 'slide12']);
        }

        $inscription = $volontaireRepository->findOneBy([
            'utilisateur' => $utilisateur,
            'id_action' => $actionNettoyage,
        ]);

        if (!$inscription) {
            $this->addFlash('warning', 'Vous n’êtes pas inscrit à cette action de nettoyage.');
            return $this->redirectToRoute('app_home', ['_fragment' => 'slide12']);
        }

        $entityManager->remove($inscription);
        $entityManager->flush();

        $this->addFlash('success', 'Votre participation a été annulée.');

        return $this->redirectToRoute('app_home', ['_fragment' => 'slide12']);
    }
}

