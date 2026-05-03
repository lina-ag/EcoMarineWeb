<?php

namespace App\Controller;

use App\Entity\ActionNettoyage;
use App\Entity\Utilisateur;
use App\Entity\Volontaire;
use App\Repository\VolontaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/action-nettoyage')]
final class NettoyageParticipationController extends AbstractController
{
    #[Route('/{id_action}/participer', name: 'app_action_nettoyage_participer', methods: ['POST'])]
    public function participer(
        Request $request,
        ActionNettoyage $actionNettoyage,
        EntityManagerInterface $entityManager,
        VolontaireRepository $volontaireRepository
    ): Response {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            $this->addFlash('warning', 'Vous devez être connecté pour participer à une action de nettoyage.');

            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('participer' . $actionNettoyage->getIdAction(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide.');

            return $this->redirectToRoute('app_home', [
                '_fragment' => 'slide09',
            ]);
        }

        if ($actionNettoyage->estComplete()) {
            $this->addFlash('danger', 'Cette action de nettoyage est déjà complète.');

            return $this->redirectToRoute('app_home', [
                '_fragment' => 'slide09',
            ]);
        }

        $dejaInscrit = $volontaireRepository->findOneBy([
            'utilisateur' => $utilisateur,
            'id_action' => $actionNettoyage,
        ]);

        if ($dejaInscrit) {
            $this->addFlash('warning', 'Vous êtes déjà inscrit à cette action de nettoyage.');

            return $this->redirectToRoute('app_home', [
                '_fragment' => 'slide09',
            ]);
        }

        $nomComplet = trim(($utilisateur->getNom() ?? '') . ' ' . ($utilisateur->getPrenom() ?? ''));

        if ($nomComplet === '') {
            $nomComplet = 'Utilisateur';
        }

        $contact = $utilisateur->getTelephone();

        if (!$contact) {
            $contact = '00000000';
        }

        $volontaire = new Volontaire();
        $volontaire->setNom($nomComplet);
        $volontaire->setContact($contact);
        $volontaire->setIdAction($actionNettoyage);
        $volontaire->setUtilisateur($utilisateur);

        $entityManager->persist($volontaire);
        $entityManager->flush();

        $this->addFlash('success', 'Votre participation a été enregistrée avec succès.');

        return $this->redirectToRoute('app_home', [
            '_fragment' => 'slide09',
        ]);
    }

    #[Route('/{id_action}/annuler-participation', name: 'app_action_nettoyage_annuler_participation', methods: ['POST'])]
    public function annulerParticipation(
        Request $request,
        ActionNettoyage $actionNettoyage,
        EntityManagerInterface $entityManager,
        VolontaireRepository $volontaireRepository
    ): Response {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            $this->addFlash('warning', 'Vous devez être connecté.');

            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('annuler_participation' . $actionNettoyage->getIdAction(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide.');

            return $this->redirectToRoute('app_home', [
                '_fragment' => 'slide09',
            ]);
        }

        $inscription = $volontaireRepository->findOneBy([
            'utilisateur' => $utilisateur,
            'id_action' => $actionNettoyage,
        ]);

        if (!$inscription) {
            $this->addFlash('warning', 'Vous n’êtes pas inscrit à cette action de nettoyage.');

            return $this->redirectToRoute('app_home', [
                '_fragment' => 'slide09',
            ]);
        }

        $entityManager->remove($inscription);
        $entityManager->flush();

        $this->addFlash('success', 'Votre participation a été annulée.');

        return $this->redirectToRoute('app_home', [
            '_fragment' => 'slide09',
        ]);
    }
}
