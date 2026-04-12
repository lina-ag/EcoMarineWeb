<?php

namespace App\Controller;

use App\Entity\Survzone;
use App\Entity\Zonep;
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
        $zonep = new Zonep();
        $survzone = new Survzone();

        $zonepForm = $this->createForm(ZonepType::class, $zonep);
        $survzoneForm = $this->createForm(SurvzoneType::class, $survzone);

        $zonepForm->handleRequest($request);
        $survzoneForm->handleRequest($request);

        if ($zonepForm->isSubmitted() && $zonepForm->isValid()) {
            $entityManager->persist($zonep);
            $entityManager->flush();

            $this->addFlash('success', 'Zone ajoutée avec succès.');

            return $this->redirectToRoute('app_home');
        }

        if ($survzoneForm->isSubmitted() && $survzoneForm->isValid()) {
            $entityManager->persist($survzone);
            $entityManager->flush();

            $this->addFlash('success', 'Surveillance ajoutée avec succès.');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'zonepForm' => $zonepForm->createView(),
            'survzoneForm' => $survzoneForm->createView(),
        ]);
    }
}
