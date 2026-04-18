<?php
namespace App\Controller;

use App\Entity\Zonep;
use App\Form\ZonepType;
use App\Repository\ZonepRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/zonep')]
final class ZonepController extends AbstractController
{
    #[Route('', name: 'app_zonep_index', methods: ['GET'])]
    public function index(ZonepRepository $zonepRepository): Response
    {
        return $this->render('zonep/index.html.twig', [
            'zoneps' => $zonepRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_zonep_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $zonep = new Zonep();
        $form = $this->createForm(ZonepType::class, $zonep);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($zonep);
            $entityManager->flush();
            return $this->redirectToRoute('app_zonep_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('zonep/new.html.twig', [
            'zonep' => $zonep,
            'form' => $form,
        ]);
    }

    #[Route('/{idZone}', name: 'app_zonep_show', methods: ['GET'], requirements: ['idZone' => '\d+'])]
    public function show(ZonepRepository $zonepRepository, int $idZone): Response
    {
        $zonep = $zonepRepository->find($idZone);

        if (!$zonep) {
            throw $this->createNotFoundException('Zone introuvable.');
        }

        return $this->render('zonep/show.html.twig', [
            'zonep' => $zonep,
        ]);
    }

    #[Route('/{idZone}/edit', name: 'app_zonep_edit', methods: ['GET', 'POST'], requirements: ['idZone' => '\d+'])]
    public function edit(Request $request, ZonepRepository $zonepRepository, int $idZone, EntityManagerInterface $entityManager): Response
    {
        $zonep = $zonepRepository->find($idZone);

        if (!$zonep) {
            throw $this->createNotFoundException('Zone introuvable.');
        }

        $form = $this->createForm(ZonepType::class, $zonep);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_zonep_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('zonep/edit.html.twig', [
            'zonep' => $zonep,
            'form' => $form,
        ]);
    }

    #[Route('/{idZone}/delete', name: 'app_zonep_delete', methods: ['POST'], requirements: ['idZone' => '\d+'])]
    public function delete(Request $request, ZonepRepository $zonepRepository, int $idZone, EntityManagerInterface $entityManager): Response
    {
        $zonep = $zonepRepository->find($idZone);

        if ($zonep && $this->isCsrfTokenValid('delete'.$zonep->getIdZone(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($zonep);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_zonep_index', [], Response::HTTP_SEE_OTHER);
    }
}