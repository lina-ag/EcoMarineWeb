<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(): Response
    {
        $reservation = new Reservation();
        $formReservation = $this->createForm(ReservationType::class, $reservation);

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'form_reservation' => $formReservation,
        ]);
    }
}
