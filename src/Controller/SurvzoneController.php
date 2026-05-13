<?php

namespace App\Controller;

use App\Entity\Survzone;
use App\Form\SurvzoneType;
use App\Repository\SurvzoneRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\ORM\EntityManagerInterface;
use FOS\ElasticaBundle\Finder\FinderInterface;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Knp\Snappy\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\GroqAnalyzer;

#[Route('/survzone')]
final class SurvzoneController extends AbstractController
{
    #[Route('', name: 'app_survzone_index', methods: ['GET'])]
    public function index(
        Request $request,
        SurvzoneRepository $survzoneRepository,
        PaginatorInterface $paginator,
        #[Autowire(service: 'fos_elastica.finder.survzone')] FinderInterface $survzoneFinder
    ): Response {
        $sortBy = $request->query->get('tri', 'idSurv');
        $order  = $request->query->get('sens', 'ASC');
        $search = trim((string) $request->query->get('search', ''));

        if ('' !== $search) {
            try {
                $results = $survzoneFinder->find($this->buildSearchQuery($search));
                $this->sortSurvzones($results, $sortBy, $order);
                $survzones = $paginator->paginate($results, $request->query->getInt('page', 1), 10);
            } catch (\Throwable $exception) {
                $query = $survzoneRepository->findBySearchSorted($search, $sortBy, $order);
                $survzones = $paginator->paginate($query->getQuery(), $request->query->getInt('page', 1), 10);
            }
        } else {
            $query = $survzoneRepository->findAllSorted($sortBy, $order);

            $survzones = $paginator->paginate(
                $query->getQuery(),
                $request->query->getInt('page', 1),
                10
            );
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('survzone/_results.html.twig', [
                'survzones' => $survzones,
                'sortBy' => $sortBy,
                'order' => $order,
                'search' => $search,
            ]);
        }

        return $this->render('survzone/index.html.twig', [
            'survzones' => $survzones,
            'sortBy'    => $sortBy,
            'order'     => $order,
            'search'    => $search,
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

    #[Route('/autocomplete/search', name: 'app_survzone_search_autocomplete', methods: ['GET'])]
    public function autocompleteSearch(Request $request, SurvzoneRepository $survzoneRepository): JsonResponse
    {
        $query = trim((string) $request->query->get('query', ''));

        return $this->json([
            'results' => $survzoneRepository->findAutocompleteSuggestions($query),
        ]);
    }

    #[Route('/export/pdf', name: 'app_survzone_export_pdf', methods: ['GET'])]
    public function exportPdf(SurvzoneRepository $survzoneRepository, Pdf $pdf): Response
    {
        $survzones = $survzoneRepository->findAll();

        $html = $this->renderView('survzone/pdf.html.twig', [
            'survzones' => $survzones,
        ]);

        try {
            return new PdfResponse(
                $pdf->getOutputFromHtml($html, [
                    'orientation' => 'Landscape',
                    'encoding' => 'utf-8',
                ]),
                'surveillances_'.date('Y-m-d').'.pdf'
            );
        } catch (\Throwable $exception) {
            return $this->renderWithDompdf($html, 'surveillances_'.date('Y-m-d').'.pdf');
        }
    }

    #[Route('/export/excel', name: 'app_survzone_export_excel', methods: ['GET'])]
    public function exportExcel(SurvzoneRepository $survzoneRepository): StreamedResponse
    {
        $survzones = $survzoneRepository->findAll();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Surveillances');

        $sheet->setCellValue('A1', 'Historique des surveillances de zones');
        $sheet->setCellValue('A2', 'Genere le');
        $sheet->setCellValue('B2', (new \DateTimeImmutable())->format('d/m/Y H:i'));
        $sheet->setCellValue('A4', 'ID');
        $sheet->setCellValue('B4', 'Date');
        $sheet->setCellValue('C4', 'Zone');
        $sheet->setCellValue('D4', 'Observation');

        $headerRange = 'A4:D4';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFDFF6FF');
        $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $rowIndex = 5;

        foreach ($survzones as $survzone) {
            $sheet->setCellValue("A{$rowIndex}", $survzone->getIdSurv());
            $sheet->setCellValue("B{$rowIndex}", $survzone->getDateSurv()?->format('Y-m-d') ?? '');
            $sheet->setCellValue("C{$rowIndex}", $survzone->getZone()?->getNomZone() ?? '');
            $sheet->setCellValue("D{$rowIndex}", (string) ($survzone->getObservation() ?? ''));
            $sheet->getStyle("A{$rowIndex}:D{$rowIndex}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $rowIndex++;
        }

        foreach (range('A', 'D') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = new XlsxWriter($spreadsheet);

        return new StreamedResponse(function () use ($writer): void {
            $writer->save('php://output');
        }, Response::HTTP_OK, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => sprintf('attachment; filename="surveillances_%s.xlsx"', (new \DateTimeImmutable())->format('Y-m-d')),
            'Cache-Control' => 'max-age=0, must-revalidate, no-cache, no-store, private',
        ]);
    }

    #[Route('/rapport/ai/export/pdf', name: 'app_survzone_rapport_ai_export_pdf', methods: ['GET'])]
    public function exportRapportAiPdf(
        SurvzoneRepository $survzoneRepository,
        GroqAnalyzer $groqAnalyzer,
        Pdf $pdf
    ): Response {
        $survzones = $survzoneRepository->findAll();
        $rapport = $groqAnalyzer->analyserSurveillances($survzones);

        $html = $this->renderView('survzone/rapport_ai_pdf.html.twig', [
            'rapport' => $rapport,
            'survzones' => $survzones,
            'generatedAt' => new \DateTimeImmutable(),
        ]);

        $filename = 'rapport_ai_survzones_'.date('Y-m-d').'.pdf';

        try {
            return new PdfResponse(
                $pdf->getOutputFromHtml($html, [
                    'orientation' => 'Portrait',
                    'encoding' => 'utf-8',
                ]),
                $filename
            );
        } catch (\Throwable $exception) {
            return $this->renderWithDompdfPortrait($html, $filename);
        }
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
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }

    private function renderWithDompdfPortrait(string $html, string $filename): Response
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
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

    private function buildSearchQuery(string $search): array
    {
        $should = [
            [
                'multi_match' => [
                    'query' => $search,
                    'fields' => ['zone.nomZone^4', 'observation^3'],
                    'type' => 'best_fields',
                    'fuzziness' => 'AUTO',
                ],
            ],
        ];

        if (\preg_match('/^\d{4}-\d{2}-\d{2}$/', $search)) {
            $should[] = [
                'term' => [
                    'dateSurv' => $search,
                ],
            ];
        }

        return [
            'query' => [
                'bool' => [
                    'should' => $should,
                    'minimum_should_match' => 1,
                ],
            ],
        ];
    }

    /**
     * @param Survzone[] $survzones
     */
    private function sortSurvzones(array &$survzones, string $sortBy, string $order): void
    {
        $allowedSort = ['idSurv', 'dateSurv', 'observation'];
        $sortBy = \in_array($sortBy, $allowedSort, true) ? $sortBy : 'idSurv';
        $direction = 'DESC' === strtoupper($order) ? -1 : 1;

        usort($survzones, static function (Survzone $left, Survzone $right) use ($sortBy, $direction): int {
            $leftValue = match ($sortBy) {
                'dateSurv' => $left->getDateSurv()?->format('Y-m-d') ?? '',
                'observation' => mb_strtolower($left->getObservation() ?? ''),
                default => $left->getIdSurv() ?? 0,
            };

            $rightValue = match ($sortBy) {
                'dateSurv' => $right->getDateSurv()?->format('Y-m-d') ?? '',
                'observation' => mb_strtolower($right->getObservation() ?? ''),
                default => $right->getIdSurv() ?? 0,
            };

            return ($leftValue <=> $rightValue) * $direction;
        });
    }

    #[Route('/rapport/ai', name: 'app_survzone_rapport_ai', methods: ['GET'])]
    public function rapportAi(
        SurvzoneRepository $survzoneRepository,
        GroqAnalyzer $groqAnalyzer
    ): Response {
        $survzones = $survzoneRepository->findAll();
        $rapport = $groqAnalyzer->analyserSurveillances($survzones);

        return $this->render('survzone/rapport_ai.html.twig', [
            'rapport' => $rapport,
            'survzones' => $survzones,
        ]);
    }
}
