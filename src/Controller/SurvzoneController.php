<?php

namespace App\Controller;

use App\Entity\Survzone;
use App\Form\SurvzoneType;
use App\Repository\SurvzoneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/survzone')]
final class SurvzoneController extends AbstractController
{
    #[Route('', name: 'app_survzone_index', methods: ['GET'])]
    public function index(
        Request $request,
        SurvzoneRepository $survzoneRepository,
        PaginatorInterface $paginator
    ): Response {
        $sortBy = $request->query->get('tri', 'idSurv');
        $order  = $request->query->get('sens', 'ASC');

        $query = $survzoneRepository->findAllSorted($sortBy, $order);

        $survzones = $paginator->paginate(
            $query->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('survzone/index.html.twig', [
            'survzones' => $survzones,
            'sortBy'    => $sortBy,
            'order'     => $order,
        ]);
    }

    #[Route('/new', name: 'app_survzone_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $survzone = new Survzone();
        $form = $this->createForm(SurvzoneType::class, $survzone);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($survzone);
            $entityManager->flush();
            $this->addFlash('success', 'La surveillance a été ajoutée avec succès !');
            return $this->redirectToRoute('app_survzone_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('survzone/new.html.twig', [
            'survzone' => $survzone,
            'form' => $form,
        ]);
    }

    #[Route('/{idSurv}', name: 'app_survzone_show', methods: ['GET'], requirements: ['idSurv' => '\d+'])]
    public function show(SurvzoneRepository $survzoneRepository, int $idSurv): Response
    {
        $survzone = $survzoneRepository->find($idSurv);

        if (!$survzone) {
            throw $this->createNotFoundException('Surveillance introuvable.');
        }

        return $this->render('survzone/show.html.twig', [
            'survzone' => $survzone,
        ]);
    }

    #[Route('/{idSurv}/edit', name: 'app_survzone_edit', methods: ['GET', 'POST'], requirements: ['idSurv' => '\d+'])]
    public function edit(Request $request, SurvzoneRepository $survzoneRepository, int $idSurv, EntityManagerInterface $entityManager): Response
    {
        $survzone = $survzoneRepository->find($idSurv);

        if (!$survzone) {
            throw $this->createNotFoundException('Surveillance introuvable.');
        }

        $form = $this->createForm(SurvzoneType::class, $survzone);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'La surveillance a été modifiée avec succès !');
            return $this->redirectToRoute('app_survzone_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('survzone/edit.html.twig', [
            'survzone' => $survzone,
            'form' => $form,
        ]);
    }

    #[Route('/{idSurv}/delete', name: 'app_survzone_delete', methods: ['POST'], requirements: ['idSurv' => '\d+'])]
    public function delete(Request $request, SurvzoneRepository $survzoneRepository, int $idSurv, EntityManagerInterface $entityManager): Response
    {
        $survzone = $survzoneRepository->find($idSurv);

        if ($survzone && $this->isCsrfTokenValid('delete'.$survzone->getIdSurv(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($survzone);
            $entityManager->flush();
            $this->addFlash('success', 'La surveillance a été supprimée avec succès !');
        }

        return $this->redirectToRoute('app_survzone_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/export/pdf', name: 'app_survzone_export_pdf', methods: ['GET'])]
public function exportPdf(SurvzoneRepository $survzoneRepository): Response
{
    $survzones = $survzoneRepository->findAll();

    $html = $this->renderView('survzone/pdf.html.twig', [
        'survzones' => $survzones,
    ]);

    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isHtml5ParserEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    return new Response(
        $dompdf->output(),
        200,
        [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="surveillances_' . date('Y-m-d') . '.pdf"',
        ]
    );
  }
}