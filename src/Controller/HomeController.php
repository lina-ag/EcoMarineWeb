<?php

namespace App\Controller;

use App\Entity\ActiviteEcologique;
use App\Entity\DetectionDrone;
use App\Entity\FauneMarine;
use App\Entity\MissionDrone;
use App\Entity\Observation;
use App\Entity\PredictionEchouage;
use App\Entity\Reservation;
use App\Entity\Survzone;
use App\Entity\Utilisateur;
use App\Entity\Zonep;
use App\Repository\ActionNettoyageRepository;
use App\Service\GroqMailService;
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

    #[Route('/home/zonep/new', name: 'app_home_zonep_new', methods: ['GET', 'POST'])]
    public function newZonep(Request $request, EntityManagerInterface $entityManager): Response
    {
        $zonep = new Zonep();
        $form = $this->createForm(ZonepType::class, $zonep);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($zonep);
            $entityManager->flush();

            return $this->redirectToRoute('app_home');
        }

        return $this->render('zonep/front/new.html.twig', [
            'zonep' => $zonep,
            'form' => $form,
        ]);
    }

    #[Route('/home/survzone/new', name: 'app_home_survzone_new', methods: ['GET', 'POST'])]
    public function newSurvzone(Request $request, EntityManagerInterface $entityManager): Response
    {
        $survzone = new Survzone();
        $form = $this->createForm(SurvzoneType::class, $survzone);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($survzone);
            $entityManager->flush();
            $this->addFlash('success', 'La surveillance a ete ajoutee avec succes !');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('survzone/front/new.html.twig', [
            'survzone' => $survzone,
            'form' => $form,
        ]);
    }

    #[Route('/home/activite-ecologique/new', name: 'app_home_activite_ecologique_new', methods: ['GET', 'POST'])]
    public function newActiviteEcologique(Request $request, EntityManagerInterface $entityManager): Response
    {
        $activiteEcologique = new ActiviteEcologique();
        $form = $this->createForm(ActiviteEcologiqueType::class, $activiteEcologique);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Impossible de creer l\'activite. Verifiez les champs saisis.');

            return $this->redirect($this->generateUrl('app_home') . '#slide09', Response::HTTP_SEE_OTHER);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $isRecurring = (bool) $form->get('is_recurring')->getData();

            if (!$isRecurring) {
                $entityManager->persist($activiteEcologique);
                $entityManager->flush();
                $this->addFlash('success', 'Activite ecologique creee avec succes.');

                return $this->redirect($this->generateUrl('app_home') . '#slide09', Response::HTTP_SEE_OTHER);
            }

            $startDate = $activiteEcologique->getDate_activite();
            $endDate = $form->get('recurrence_end_date')->getData();
            $selectedDays = $form->get('recurrence_days')->getData();

            if (!$startDate instanceof \DateTimeInterface || !$endDate instanceof \DateTimeInterface) {
                $form->get('recurrence_end_date')->addError(new \Symfony\Component\Form\FormError('Veuillez choisir une date de fin valide.'));
            } elseif ($endDate < $startDate) {
                $form->get('recurrence_end_date')->addError(new \Symfony\Component\Form\FormError('La date de fin doit etre superieure ou egale a la date de debut.'));
            } elseif (!is_array($selectedDays) || count($selectedDays) === 0) {
                $form->get('recurrence_days')->addError(new \Symfony\Component\Form\FormError('Selectionnez au moins un jour de repetition.'));
            } else {
                $normalizedDays = array_map('intval', $selectedDays);
                sort($normalizedDays);

                $dates = [];
                $cursor = \DateTime::createFromFormat('Y-m-d', $startDate->format('Y-m-d'));
                $limit = \DateTime::createFromFormat('Y-m-d', $endDate->format('Y-m-d'));

                while ($cursor <= $limit) {
                    if (in_array((int) $cursor->format('N'), $normalizedDays, true)) {
                        $dates[] = clone $cursor;
                    }
                    $cursor->modify('+1 day');
                }

                if (count($dates) === 0) {
                    $form->get('recurrence_days')->addError(new \Symfony\Component\Form\FormError('Aucune occurrence trouvee avec les jours selectionnes.'));
                } else {
                    foreach ($dates as $index => $date) {
                        $item = $index === 0 ? $activiteEcologique : new ActiviteEcologique();
                        $item
                            ->setNom_activite((string) $activiteEcologique->getNom_activite())
                            ->setDescription($activiteEcologique->getDescription())
                            ->setCapacite((int) $activiteEcologique->getCapacite())
                            ->setDate_activite($date);
                        $entityManager->persist($item);
                    }

                    $entityManager->flush();
                    $this->addFlash('success', sprintf('%d activite(s) creee(s) avec recurrence.', count($dates)));

                    return $this->redirect($this->generateUrl('app_home') . '#slide09', Response::HTTP_SEE_OTHER);
                }
            }

            $this->addFlash('error', 'Recurrence invalide. Verifiez la date de fin et les jours selectionnes.');

            return $this->redirect($this->generateUrl('app_home') . '#slide09', Response::HTTP_SEE_OTHER);
        }

        return $this->render('activite_ecologique/front/new.html.twig', [
            'activite_ecologique' => $activiteEcologique,
            'form' => $form,
        ]);
    }

    #[Route('/home/reservation/new', name: 'app_home_reservation_new', methods: ['GET', 'POST'])]
    public function newReservation(Request $request, EntityManagerInterface $entityManager, GroqMailService $groqMailService): Response
    {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Votre reservation a ete enregistree avec succes !');

            try {
                $groqMailService->sendReservationConfirmation(
                    (string) $reservation->getEmail(),
                    (string) $reservation->getNom(),
                    (string) ($reservation->getActiviteEcologique()?->getNom_activite() ?? 'Activite EcoMarine'),
                    $reservation->getDate_reservation()?->format('d/m/Y') ?? ''
                );
            } catch (\Throwable) {
                $this->addFlash('error', 'Reservation enregistree, mais l\'email de confirmation n\'a pas pu etre envoye.');
            }

            return $this->redirect($this->generateUrl('app_home') . '#slide08', Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/front/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }
}
