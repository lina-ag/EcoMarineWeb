<?php

namespace App\Controller;

use App\Form\ReservationType;
use App\Form\ZonepType;
use App\Form\SurvzoneType;
use App\Form\FauneMarineType;
use App\Form\ObservationType;
use App\Form\PredictionEchouageType;
use App\Form\MissionDroneType;
use App\Form\DetectionDroneType;
use App\Form\ActiviteEcologiqueType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\UtilisateurType;
use App\Entity\Utilisateur;
use App\Entity\FauneMarine;
use App\Entity\Observation;
use App\Entity\PredictionEchouage;
use App\Entity\MissionDrone;
use App\Entity\DetectionDrone;
use App\Entity\Reservation;
use App\Entity\Zonep;
use App\Entity\Survzone;
use App\Entity\ActiviteEcologique;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UtilisateurType::class, new Utilisateur());
        $formReservation = $this->createForm(ReservationType::class);
        $zonepForm = $this->createForm(ZonepType::class);
        $survzoneForm = $this->createForm(SurvzoneType::class);
        $activiteForm = $this->createForm(ActiviteEcologiqueType::class, new ActiviteEcologique());
        $fauneMarineForm = $this->createForm(FauneMarineType::class, new FauneMarine());
        $observationForm = $this->createForm(ObservationType::class, new Observation());
        $predictionForm = $this->createForm(PredictionEchouageType::class, new PredictionEchouage());
        $missionDroneForm = $this->createForm(MissionDroneType::class, new MissionDrone());
        $detectionDroneForm = $this->createForm(DetectionDroneType::class, new DetectionDrone());

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'form' => $form->createView(),
            'form_reservation' => $formReservation->createView(),
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
            'form_reservation' => $formReservation->createView(),
            'zonepForm' => $zonepForm->createView(),
            'survzoneForm' => $survzoneForm->createView(),
            'fauneMarineForm' => $fauneMarineForm->createView(),
            'observationForm' => $observationForm->createView(),
            'predictionForm' => $predictionForm->createView(),
            'missionDroneForm' => $missionDroneForm->createView(),
            'detectionDroneForm' => $detectionDroneForm->createView(),
=======
        $reservation = new Reservation();
        $form_reservation = $this->createForm(ReservationType::class, $reservation);
        
        $activiteEcologique = new ActiviteEcologique();
        $form_activite = $this->createForm(ActiviteEcologiqueType::class, $activiteEcologique);
        
        $zonep = new Zonep();
        $form_zonep = $this->createForm(ZonepType::class, $zonep);
        
        $survzone = new Survzone();
        $form_survzone = $this->createForm(SurvzoneType::class, $survzone);
        
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'form_reservation' => $form_reservation,
            'form_activite' => $form_activite,
            'form_zonep' => $form_zonep,
            'form_survzone' => $form_survzone,
            'zonepForm' => $form_zonep,
            'survzoneForm' => $form_survzone,
>>>>>>> 5209fc2d120843fb405f05322fa4127f76a5b7ae
        ]);
    }
}
