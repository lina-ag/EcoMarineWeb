<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\ActiviteEcologiqueRepository;
use App\Repository\ReservationRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reservation')]
final class ReservationController extends AbstractController
{
    #[Route('/activity-date/{id_activite}', name: 'app_reservation_activity_date', methods: ['GET'])]
    public function activityDate(int $id_activite, ActiviteEcologiqueRepository $activiteEcologiqueRepository): JsonResponse
    {
        $activite = $activiteEcologiqueRepository->find($id_activite);

        if (!$activite || !$activite->getDate_activite()) {
            return $this->json(['dates' => []], Response::HTTP_NOT_FOUND);
        }

        $dates = $activiteEcologiqueRepository->findDatesForReservationByName((string) $activite->getNom_activite());
        if (count($dates) === 0) {
            $dates = [$activite->getDate_activite()->format('Y-m-d')];
        }

        return $this->json(['dates' => $dates]);
    }

    #[Route(name: 'app_reservation_index', methods: ['GET'])]
    public function index(Request $request, ReservationRepository $reservationRepository): Response
    {
        $searchTerm = trim((string) $request->query->get('q', ''));

        $queryBuilder = $reservationRepository
            ->createQueryBuilder('r')
            ->leftJoin('r.activiteEcologique', 'a')
            ->addSelect('a')
            ->orderBy('r.date_reservation', 'DESC');

        if ($searchTerm !== '') {
            $tokens = preg_split('/\s+/', mb_strtolower($searchTerm), -1, PREG_SPLIT_NO_EMPTY) ?: [];

            foreach ($tokens as $index => $token) {
                $parameterName = 'term_' . $index;
                $orGroup = $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->like('LOWER(r.nom)', ':' . $parameterName),
                    $queryBuilder->expr()->like('LOWER(r.email)', ':' . $parameterName),
                    $queryBuilder->expr()->like('LOWER(COALESCE(a.nom_activite, \'\'))', ':' . $parameterName)
                );

                if (ctype_digit($token)) {
                    $idParameterName = 'id_' . $index;
                    $peopleParameterName = 'people_' . $index;

                    $orGroup->add($queryBuilder->expr()->eq('r.id_reservation', ':' . $idParameterName));
                    $orGroup->add($queryBuilder->expr()->eq('r.nombre_personnes', ':' . $peopleParameterName));

                    $queryBuilder
                        ->setParameter($idParameterName, (int) $token)
                        ->setParameter($peopleParameterName, (int) $token);
                }

                $queryBuilder
                    ->andWhere($orGroup)
                    ->setParameter($parameterName, '%' . $token . '%');
            }
        }

        $reservations = $queryBuilder->getQuery()->getResult();

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
            'search_term' => $searchTerm,
            'result_count' => count($reservations),
        ]);
    }

    #[Route('/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Votre réservation a été enregistrée avec succès !');

            if ($request->query->get('source') === 'front') {
                return $this->redirect($this->generateUrl('app_home') . '#slide08', Response::HTTP_SEE_OTHER);
            }

            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);

        }

        return $this->render('reservation/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id_reservation}', name: 'app_reservation_show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id_reservation}/pdf', name: 'app_reservation_pdf', methods: ['GET'])]
    public function exportPdf(Reservation $reservation): Response
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $html = $this->renderView('reservation/pdf.html.twig', [
            'reservation' => $reservation,
            'generatedAt' => new \DateTimeImmutable(),
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = sprintf('reservation_%d.pdf', $reservation->getIdReservation() ?? 0);

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ]
        );
    }

    #[Route('/{id_reservation}/edit', name: 'app_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id_reservation}', name: 'app_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reservation->getId_reservation(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($reservation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }
}
