<?php

namespace App\Controller;

use App\Form\ReservationType;
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
        $formReservation = $this->createForm(ReservationType::class);

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'form_reservation' => $formReservation->createView(),
        ]);
    }
}
