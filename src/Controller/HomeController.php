<?php

namespace App\Controller;

use App\Entity\ActionNettoyage;
use App\Entity\Utilisateur;
use App\Form\ReservationType;
use App\Repository\ActionNettoyageRepository;
use App\Repository\VolontaireRepository;
use App\Service\AiRecommendationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        ActionNettoyageRepository $actionNettoyageRepository,
        VolontaireRepository $volontaireRepository,
        AiRecommendationService $recommendationService
    ): Response {
        /*
         * Module Réservation
         * On garde le formulaire de réservation car il appartient à l'autre module.
         */
        $formReservation = $this->createForm(ReservationType::class);

        /*
         * Module Nettoyage
         * On récupère toutes les actions de nettoyage.
         */
        $actionsNettoyage = $actionNettoyageRepository->findBy([], [
            'date_action' => 'ASC',
        ]);

        /*
         * Ces deux tableaux servent à l'affichage utilisateur :
         * - mes_inscriptions_nettoyage : liste complète des participations de l'utilisateur connecté
         * - inscriptions_utilisateur : seulement les IDs des actions où il est déjà inscrit
         */
        $mesInscriptionsNettoyage = [];
        $inscriptionsUtilisateur = [];

        $utilisateur = $this->getUser();

        if ($utilisateur instanceof Utilisateur) {
            $mesInscriptionsNettoyage = $volontaireRepository->findBy([
                'utilisateur' => $utilisateur,
            ]);

            foreach ($mesInscriptionsNettoyage as $inscription) {
                if ($inscription->getIdAction()) {
                    $inscriptionsUtilisateur[] = $inscription->getIdAction()->getIdAction();
                }
            }
        }

        $actionsDisponibles = array_filter(
            $actionsNettoyage,
            static fn(ActionNettoyage $action) =>
            !$action->estComplete() && !in_array($action->getIdAction(), $inscriptionsUtilisateur, true)
        );

        $actionsRecommandees = $recommendationService->recommendActions(
            array_values($actionsDisponibles),
            $utilisateur instanceof Utilisateur ? $utilisateur : null
        );

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',

            // Module réservation
            'form_reservation' => $formReservation->createView(),

            // Module nettoyage
            'actions_nettoyage' => $actionsNettoyage,
            'inscriptions_utilisateur' => $inscriptionsUtilisateur,
            'mes_inscriptions_nettoyage' => $mesInscriptionsNettoyage,
            'actions_recommandees' => $actionsRecommandees,
        ]);
    }
}
