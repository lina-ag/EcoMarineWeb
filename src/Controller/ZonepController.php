<?php
namespace App\Controller;

use App\Entity\Zonep;
use App\Form\ZonepType;
use App\Repository\ZonepRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;
use Symfony\UX\Map\InfoWindow;


#[Route('/zonep')]
final class ZonepController extends AbstractController
{
    #[Route('', name: 'app_zonep_index', methods: ['GET'])]
    public function index(
    Request $request,
    ZonepRepository $zonepRepository,
    PaginatorInterface $paginator
): Response {
    $sortBy = $request->query->get('tri', 'idZone');
    $order  = $request->query->get('sens', 'ASC');
    $search = trim((string) $request->query->get('search', ''));
    $status = trim((string) $request->query->get('status', ''));

    $query = $zonepRepository->findFiltered($search, $status, $sortBy, $order);

    $zoneps = $paginator->paginate(
        $query->getQuery(),
        $request->query->getInt('page', 1),
        10
    );
    // Crée la carte centrée sur la Tunisie (coordonnées par défaut)
         $map = (new Map())
        ->center(new Point(36.8, 10.18))
        ->zoom(7);

    if ($request->isXmlHttpRequest()) {
        return $this->render('zonep/_results.html.twig', [
            'zoneps'  => $zoneps,
        ]);
    }

    return $this->render('zonep/index.html.twig', [
        'zoneps'  => $zoneps,
        'sortBy'  => $sortBy,
        'order'   => $order,
        'search'  => $search,
        'status'  => $status,
        'map'     => $map,
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

    #[Route('/export/pdf', name: 'app_zonep_export_pdf', methods: ['GET'])]
    public function exportPdf(ZonepRepository $zonepRepository, Pdf $pdf): Response
    {
        $zoneps = $zonepRepository->findAll();

        $html = $this->renderView('zonep/pdf.html.twig', [
            'zoneps' => $zoneps,
        ]);

        try {
            return new PdfResponse(
                $pdf->getOutputFromHtml($html, [
                    'orientation' => 'Landscape',
                    'encoding' => 'utf-8',
                ]),
                'zones_'.date('Y-m-d').'.pdf'
            );
        } catch (\Throwable $exception) {
            return $this->renderWithDompdf($html, 'zones_'.date('Y-m-d').'.pdf');
        }
    }

    private function renderWithDompdf(string $html, string $filename): Response
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }
}
