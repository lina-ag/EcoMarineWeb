<?php

namespace App\Controller;

use App\Entity\Zonep;
use App\Form\ZonepType;
use App\Repository\ZonepRepository;
use App\Service\ZoneRisqueAI;
use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Snappy\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Map\Bridge\Leaflet\LeafletOptions;
use Symfony\UX\Map\Bridge\Leaflet\Option\AttributionControlOptions;
use Symfony\UX\Map\Bridge\Leaflet\Option\ControlPosition;
use Symfony\UX\Map\Bridge\Leaflet\Option\TileLayer;
use Symfony\UX\Map\Bridge\Leaflet\Option\ZoomControlOptions;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Point;

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
            2
        );

        $leafletOptions = (new LeafletOptions())
            ->tileLayer(new TileLayer(
                url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                options: ['maxZoom' => 19]
            ))
            ->attributionControlOptions(new AttributionControlOptions(ControlPosition::BOTTOM_RIGHT))
            ->zoomControlOptions(new ZoomControlOptions(ControlPosition::TOP_RIGHT));

        $map = (new Map())
            ->center(new Point(36.8, 10.18))
            ->zoom(7)
            ->options($leafletOptions);

        if ($request->isXmlHttpRequest()) {
            return $this->render('zonep/_results.html.twig', [
                'zoneps' => $zoneps,
                'sortBy' => $sortBy,
                'order' => $order,
                'search' => $search,
                'status' => $status,
            ]);
        }

        return $this->render('zonep/index.html.twig', [
            'zoneps' => $zoneps,
            'sortBy' => $sortBy,
            'order'  => $order,
            'search' => $search,
            'status' => $status,
            'map'    => $map,
        ]);
    }

    #[Route('/new', name: 'app_zonep_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $zonep = new Zonep();
        $form  = $this->createForm(ZonepType::class, $zonep);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($zonep);
            $entityManager->flush();
            return $this->redirectToRoute('app_zonep_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('zonep/new.html.twig', [
            'zonep' => $zonep,
            'form'  => $form,
        ]);
    }

    #[Route('/export/pdf', name: 'app_zonep_export_pdf', methods: ['GET'])]
    public function exportPdf(ZonepRepository $zonepRepository, Pdf $pdf): Response
    {
        $zoneps = $zonepRepository->findAll();
        $html   = $this->renderView('zonep/pdf.html.twig', ['zoneps' => $zoneps]);

        try {
            return new PdfResponse(
                $pdf->getOutputFromHtml($html, ['orientation' => 'Landscape', 'encoding' => 'utf-8']),
                'zones_'.date('Y-m-d').'.pdf'
            );
        } catch (\Throwable $exception) {
            return $this->renderWithDompdf($html, 'zones_'.date('Y-m-d').'.pdf');
        }
    }

    #[Route('/export/excel', name: 'app_zonep_export_excel', methods: ['GET'])]
    public function exportExcel(ZonepRepository $zonepRepository): StreamedResponse
    {
        $zoneps      = $zonepRepository->findAll();
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Zones');

        $sheet->setCellValue('A1', 'Rapport des zones de protection marine');
        $sheet->setCellValue('A2', 'Genere le');
        $sheet->setCellValue('B2', (new \DateTimeImmutable())->format('d/m/Y H:i'));
        $sheet->setCellValue('A4', 'ID');
        $sheet->setCellValue('B4', 'Nom de la zone');
        $sheet->setCellValue('C4', 'Categorie');
        $sheet->setCellValue('D4', 'Statut');
        $sheet->setCellValue('E4', 'Nb surveillances');

        $headerRange = 'A4:E4';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFDFF6FF');
        $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $rowIndex = 5;
        foreach ($zoneps as $zonep) {
            $sheet->setCellValue("A{$rowIndex}", $zonep->getIdZone());
            $sheet->setCellValue("B{$rowIndex}", $zonep->getNomZone());
            $sheet->setCellValue("C{$rowIndex}", $zonep->getCategorieZone());
            $sheet->setCellValue("D{$rowIndex}", $zonep->getStatus());
            $sheet->setCellValue("E{$rowIndex}", $zonep->getSurvzones()->count());
            $sheet->getStyle("A{$rowIndex}:E{$rowIndex}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $rowIndex++;
        }

        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = new XlsxWriter($spreadsheet);

        return new StreamedResponse(function () use ($writer): void {
            $writer->save('php://output');
        }, Response::HTTP_OK, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => sprintf('attachment; filename="zones_%s.xlsx"', (new \DateTimeImmutable())->format('Y-m-d')),
            'Cache-Control'       => 'max-age=0, must-revalidate, no-cache, no-store, private',
        ]);
    }

    // ══════════════════════════════════════════════════════════
    // ⚠️  ROUTES AVEC {idZone} — les routes statiques D'ABORD,
    //     puis les routes dynamiques comme /{idZone}
    // ══════════════════════════════════════════════════════════

    #[Route('/{idZone}/analyse-risque', name: 'app_zonep_analyse_risque', methods: ['GET', 'POST'], requirements: ['idZone' => '\d+'])]
    public function analyseRisque(
        Request         $request,
        ZonepRepository $zonepRepository,
        int             $idZone,
        ZoneRisqueAI    $ai
    ): Response {
        $zonep = $zonepRepository->find($idZone);

        if (!$zonep) {
            throw $this->createNotFoundException('Zone introuvable.');
        }

        $resultat = null;

        if ($request->isMethod('POST')) {
            $resultat = $ai->predireRisque(
                (float) $request->request->get('temperature', 20),
                (int)   $request->request->get('activite_humaine', 0),
                (int)   $request->request->get('pollution', 0),
                (int)   $request->request->get('biodiversite', 4)
            );
        }

        return $this->render('zonep/analyse_risque.html.twig', [
            'zonep'    => $zonep,
            'resultat' => $resultat,
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
            'form'  => $form,
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

    private function renderWithDompdf(string $html, string $filename): Response
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }
}
