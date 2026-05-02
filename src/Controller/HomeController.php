<?php

namespace App\Controller;

use App\Entity\ActiviteEcologique;
use App\Entity\DetectionDrone;
use App\Entity\FauneMarine;
use App\Entity\MissionDrone;
use App\Entity\Observation;
use App\Entity\PredictionEchouage;
use App\Entity\Survzone;
use App\Entity\Utilisateur;
use App\Entity\Zonep;
use App\Repository\ActionNettoyageRepository;
use App\Repository\VolontaireRepository;
use App\Service\AiRecommendationService;
use Knp\Component\Pager\PaginatorInterface;
use App\Form\ActiviteEcologiqueType;
use App\Form\DetectionDroneType;
use App\Form\FauneMarineType;
use App\Form\MissionDroneType;
use App\Form\ObservationType;
use App\Form\PredictionEchouageType;
use App\Form\ReservationType;
use App\Form\SurvzoneType;
use App\Form\UtilisateurType;
use App\Form\ZonepType;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    // 🔥 NOUVELLE ROUTE POUR LA RACINE
    #[Route('/', name: 'app_root')]
    public function root(): Response
    {
        // Rediriger vers la page de connexion
        return $this->redirectToRoute('app_signIn');
    }

    #[Route('/home', name: 'app_home', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        ActionNettoyageRepository $actionNettoyageRepository,
        VolontaireRepository $volontaireRepository,
        UtilisateurRepository $utilisateurRepository,
        AiRecommendationService $aiRecommendationService,
        PaginatorInterface $paginator,
        SessionInterface $session
    ): Response {
        $form = $this->createForm(UtilisateurType::class, new Utilisateur());
        $formReservation = $this->createForm(ReservationType::class);

        $zonepForm = $this->createForm(ZonepType::class);
        $survzoneForm = $this->createForm(SurvzoneType::class);

        $zonepForm = $this->createForm(ZonepType::class, new Zonep());
        $survzoneForm = $this->createForm(SurvzoneType::class, new Survzone());
        $activiteForm = $this->createForm(ActiviteEcologiqueType::class, new ActiviteEcologique());
        $fauneMarineForm = $this->createForm(FauneMarineType::class, new FauneMarine());
        $observationForm = $this->createForm(ObservationType::class, new Observation());
        $predictionForm = $this->createForm(PredictionEchouageType::class, new PredictionEchouage());
        $missionDroneForm = $this->createForm(MissionDroneType::class, new MissionDrone());
        $detectionDroneForm = $this->createForm(DetectionDroneType::class, new DetectionDrone());

        $queryBuilder = $actionNettoyageRepository->createQueryBuilder('a')
            ->orderBy('a.date_action', 'ASC');

        $actionsNettoyage = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            3
        );

        $allActionsNettoyage = $actionNettoyageRepository->findAll();
        $mesInscriptionsNettoyage = [];
        $inscriptionsUtilisateur = [];
        $actionsRecommandees = [];
        $currentUser = $session->get('user');

        if (!$currentUser instanceof Utilisateur && $this->getUser() instanceof Utilisateur) {
            $currentUser = $this->getUser();
        }

        if ($currentUser instanceof Utilisateur) {
            $currentUser = $utilisateurRepository->find($currentUser->getIdUtilisateur()) ?? $currentUser;
            $mesInscriptionsNettoyage = $volontaireRepository->findBy(['utilisateur' => $currentUser]);
            $inscriptionsUtilisateur = array_map(
                static fn($volontaire) => $volontaire->getIdAction()?->getIdAction(),
                $mesInscriptionsNettoyage
            );
            $inscriptionsUtilisateur = array_values(array_filter($inscriptionsUtilisateur, static fn($id) => $id !== null));

            $actionsDisponiblesPourRecommandation = array_values(array_filter(
                $allActionsNettoyage,
                static fn($action) => !$action->estComplete() && !in_array($action->getIdAction(), $inscriptionsUtilisateur, true)
            ));

            $actionsRecommandees = $aiRecommendationService->recommendActions($actionsDisponiblesPourRecommandation, $currentUser);
        }

        if ($request->isXmlHttpRequest() && $request->query->get('section') === 'nettoyage') {
            return $this->render('home/_actions_nettoyage_list.html.twig', [
                'actions_nettoyage' => $actionsNettoyage,
                'inscriptions_utilisateur' => $inscriptionsUtilisateur,
            ]);
        }

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'form' => $form->createView(),

            // Variables used by the current home slides/partials.
            'form_reservation' => $formReservation->createView(),

            'zonepForm' => $zonepForm->createView(),
            'survzoneForm' => $survzoneForm->createView(),

            'form_activite' => $activiteForm->createView(),
            'form_zonep' => $zonepForm->createView(),
            'form_survzone' => $survzoneForm->createView(),

            // Compatibility aliases used by other blocks in the same template.

            'activiteForm' => $activiteForm->createView(),
            'fauneMarineForm' => $fauneMarineForm->createView(),
            'observationForm' => $observationForm->createView(),
            'predictionForm' => $predictionForm->createView(),
            'missionDroneForm' => $missionDroneForm->createView(),
            'detectionDroneForm' => $detectionDroneForm->createView(),

            'actions_nettoyage' => $actionsNettoyage,
            'actions_recommandees' => $actionsRecommandees,
            'inscriptions_utilisateur' => $inscriptionsUtilisateur,
            'mes_inscriptions_nettoyage' => $mesInscriptionsNettoyage,
            'current_user' => $currentUser,
        ]);
    }
}
