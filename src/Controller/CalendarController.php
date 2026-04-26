<?php

namespace App\Controller;

use App\Entity\ActiviteEcologique;
use App\Repository\ActiviteEcologiqueRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Knp\Snappy\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

final class CalendarController extends AbstractController
{
    #[Route('/calendar', name: 'app_front_calendar', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('calendar/index.html.twig');
    }

    #[Route('/calendar/export/pdf', name: 'app_calendar_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, ActiviteEcologiqueRepository $activiteEcologiqueRepository, Pdf $snappyPdf): Response
    {
        $searchTerm = trim((string) $request->query->get('q', ''));
        $statusFilter = (string) $request->query->get('status', 'all');

        $activities = $this->getFilteredActivities($activiteEcologiqueRepository, $searchTerm, $statusFilter);

        $html = $this->renderView('calendar/pdf.html.twig', [
            'activite_ecologiques' => $activities,
            'generatedAt' => new \DateTimeImmutable(),
            'searchTerm' => $searchTerm,
            'statusFilter' => $statusFilter,
        ]);

        try {
            $pdfContent = $snappyPdf->getOutputFromHtml($html, [
                'enable-local-file-access' => true,
                'margin-top' => 18,
                'margin-right' => 12,
                'margin-bottom' => 18,
                'margin-left' => 12,
            ]);
        } catch (\Throwable $throwable) {
            $options = new Options();
            $options->set('defaultFont', 'DejaVu Sans');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            $pdfContent = $dompdf->output();
        }

        return new Response(
            $pdfContent,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="calendrier_activites_%s.pdf"', (new \DateTimeImmutable())->format('Y-m-d')),
            ]
        );
    }

    #[Route('/calendar/export/excel', name: 'app_calendar_export_excel', methods: ['GET'])]
    public function exportExcel(Request $request, ActiviteEcologiqueRepository $activiteEcologiqueRepository): StreamedResponse
    {
        $searchTerm = trim((string) $request->query->get('q', ''));
        $statusFilter = (string) $request->query->get('status', 'all');

        $activities = $this->getFilteredActivities($activiteEcologiqueRepository, $searchTerm, $statusFilter);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Activites');

        $sheet->setCellValue('A1', 'Calendrier des activités');
        $sheet->setCellValue('A2', 'Généré le');
        $sheet->setCellValue('B2', (new \DateTimeImmutable())->format('d/m/Y H:i'));
        $sheet->setCellValue('A4', 'Activité');
        $sheet->setCellValue('B4', 'Date');
        $sheet->setCellValue('C4', 'Capacité');
        $sheet->setCellValue('D4', 'Réservées');
        $sheet->setCellValue('E4', 'Restantes');
        $sheet->setCellValue('F4', 'Remplissage');
        $sheet->setCellValue('G4', 'Statut');
        $sheet->setCellValue('H4', 'Description');

        $headerRange = 'A4:H4';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFDFF6FF');
        $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $rowIndex = 5;

        foreach ($activities as $activity) {
            [$reservedPeople, $remaining, $fillRate, $status] = $this->calculateActivityMetrics($activity);

            $sheet->setCellValue("A{$rowIndex}", $activity->getNomActivite());
            $sheet->setCellValue("B{$rowIndex}", $activity->getDateActivite()?->format('Y-m-d'));
            $sheet->setCellValue("C{$rowIndex}", $activity->getCapacite());
            $sheet->setCellValue("D{$rowIndex}", $reservedPeople);
            $sheet->setCellValue("E{$rowIndex}", $remaining);
            $sheet->setCellValue("F{$rowIndex}", $fillRate . '%');
            $sheet->setCellValue("G{$rowIndex}", $status);
            $sheet->setCellValue("H{$rowIndex}", (string) ($activity->getDescription() ?? ''));

            $sheet->getStyle("A{$rowIndex}:H{$rowIndex}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $rowIndex++;
        }

        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = new XlsxWriter($spreadsheet);

        return new StreamedResponse(function () use ($writer): void {
            $writer->save('php://output');
        }, Response::HTTP_OK, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => sprintf('attachment; filename="calendrier_activites_%s.xlsx"', (new \DateTimeImmutable())->format('Y-m-d')),
            'Cache-Control' => 'max-age=0, must-revalidate, no-cache, no-store, private',
        ]);
    }

    /**
     * @return ActiviteEcologique[]
     */
    private function getFilteredActivities(ActiviteEcologiqueRepository $activiteEcologiqueRepository, string $searchTerm, string $statusFilter): array
    {
        return array_values(array_filter(
            $activiteEcologiqueRepository->findAll(),
            fn (ActiviteEcologique $activity): bool => $this->matchesActivity($activity, $searchTerm, $statusFilter)
        ));
    }

    /**
     * @return array{0:int,1:int,2:int,3:string}
     */
    private function calculateActivityMetrics(ActiviteEcologique $activity): array
    {
        $reservedPeople = 0;

        foreach ($activity->getReservations() as $reservation) {
            $reservedPeople += (int) ($reservation->getNombrePersonnes() ?? 0);
        }

        $capacity = (int) ($activity->getCapacite() ?? 0);
        $remaining = max(0, $capacity - $reservedPeople);
        $fillRate = $capacity > 0 ? (int) round(($reservedPeople / $capacity) * 100) : 0;

        $activityDate = $activity->getDateActivite();
        $status = 'non defini';

        if ($activityDate) {
            $date = \DateTimeImmutable::createFromInterface($activityDate)->setTime(0, 0, 0);
            $today = new \DateTimeImmutable('today');
            $status = $date < $today ? 'past' : ($date > $today ? 'upcoming' : 'today');
        }

        return [$reservedPeople, $remaining, $fillRate, $status];
    }

    private function matchesActivity(ActiviteEcologique $activity, string $searchTerm, string $statusFilter): bool
    {
        if ($searchTerm !== '') {
            $haystack = mb_strtolower(implode(' ', array_filter([
                (string) $activity->getNomActivite(),
                (string) ($activity->getDescription() ?? ''),
                (string) ($activity->getCapacite() ?? ''),
                $activity->getDateActivite()?->format('Y-m-d') ?? '',
            ])));

            $needle = mb_strtolower($searchTerm);

            if (!str_contains($haystack, $needle)) {
                return false;
            }
        }

        if ($statusFilter === 'all') {
            return true;
        }

        $activityDate = $activity->getDateActivite();

        if (!$activityDate) {
            return false;
        }

        $date = \DateTimeImmutable::createFromInterface($activityDate)->setTime(0, 0, 0);
        $today = new \DateTimeImmutable('today');
        $status = $date < $today ? 'past' : ($date > $today ? 'upcoming' : 'today');

        return $status === $statusFilter;
    }
}
