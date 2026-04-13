<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\UtilisateurType;
use App\Form\ReservationType;
use App\Form\ZonepType;
use App\Form\SurvzoneType;
use App\Form\ActiviteEcologiqueType;
use App\Entity\Utilisateur;
use App\Entity\Reservation;
use App\Entity\Zonep;
use App\Entity\Survzone;
use App\Entity\ActiviteEcologique;
use Doctrine\ORM\EntityManagerInterface;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
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
        ]);
    }
}
