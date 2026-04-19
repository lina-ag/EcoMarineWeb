<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\ActiviteEcologiqueRepository;
use App\Repository\ReservationRepository;
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
            $queryBuilder
                ->andWhere('LOWER(r.nom) LIKE :term OR LOWER(r.email) LIKE :term OR LOWER(a.nom_activite) LIKE :term')
                ->setParameter('term', '%' . mb_strtolower($searchTerm) . '%');

            if (ctype_digit($searchTerm)) {
                $queryBuilder
                    ->orWhere('r.id_reservation = :id OR r.nombre_personnes = :people')
                    ->setParameter('id', (int) $searchTerm)
                    ->setParameter('people', (int) $searchTerm);
            }
        }

        return $this->render('reservation/index.html.twig', [
            'reservations' => $queryBuilder->getQuery()->getResult(),
            'search_term' => $searchTerm,
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

            $this->addFlash('success', 'Reservation confirmee avec succes.');

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
