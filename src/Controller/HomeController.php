<?php

namespace App\Controller;

use App\Entity\Survzone;
use App\Entity\Zonep;
use App\Form\ReservationType;
use App\Form\SurvzoneType;
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
        $formReservation = $this->createForm(ReservationType::class);
        $formZonep = $this->createForm(ZonepType::class, new Zonep());
        $formSurvzone = $this->createForm(SurvzoneType::class, new Survzone());

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'form_reservation' => $formReservation->createView(),
            'form_zonep' => $formZonep->createView(),
            'form_survzone' => $formSurvzone->createView(),
        ]);
    }
}
