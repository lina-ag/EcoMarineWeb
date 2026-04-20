<?php

namespace App\Controller;

use App\Form\ReservationType;
use App\Form\ZonepType;
use App\Form\SurvzoneType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\UtilisateurType;
use App\Entity\Utilisateur;

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
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {

        $form = $this->createForm(UtilisateurType::class, new Utilisateur());
        $formReservation = $this->createForm(ReservationType::class);
        $zonepForm = $this->createForm(ZonepType::class);
        $survzoneForm = $this->createForm(SurvzoneType::class);

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'form' => $form->createView(),
            'form_reservation' => $formReservation->createView(),
            'zonepForm' => $zonepForm->createView(),
            'survzoneForm' => $survzoneForm->createView(),
        ]);
    }
}