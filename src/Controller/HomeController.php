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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UtilisateurType::class, new Utilisateur());
        $formReservation = $this->createForm(ReservationType::class);
        $zonepForm = $this->createForm(ZonepType::class, new Zonep());
        $survzoneForm = $this->createForm(SurvzoneType::class, new Survzone());
        $activiteForm = $this->createForm(ActiviteEcologiqueType::class, new ActiviteEcologique());
        $fauneMarineForm = $this->createForm(FauneMarineType::class, new FauneMarine());
        $observationForm = $this->createForm(ObservationType::class, new Observation());
        $predictionForm = $this->createForm(PredictionEchouageType::class, new PredictionEchouage());
        $missionDroneForm = $this->createForm(MissionDroneType::class, new MissionDrone());
        $detectionDroneForm = $this->createForm(DetectionDroneType::class, new DetectionDrone());

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'form' => $form->createView(),

            // Variables used by the current home slides/partials.
            'form_reservation' => $formReservation->createView(),
            'form_activite' => $activiteForm->createView(),
            'form_zonep' => $zonepForm->createView(),
            'form_survzone' => $survzoneForm->createView(),

            // Compatibility aliases used by other blocks in the same template.
            'zonepForm' => $zonepForm->createView(),
            'survzoneForm' => $survzoneForm->createView(),
            'activiteForm' => $activiteForm->createView(),
            'fauneMarineForm' => $fauneMarineForm->createView(),
            'observationForm' => $observationForm->createView(),
            'predictionForm' => $predictionForm->createView(),
            'missionDroneForm' => $missionDroneForm->createView(),
            'detectionDroneForm' => $detectionDroneForm->createView(),
        ]);
    }
}
