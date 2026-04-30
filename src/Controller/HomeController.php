<?php

namespace App\Controller;

use App\Entity\ActiviteEcologique;
use App\Entity\Reservation;
use App\Entity\Survzone;
use App\Entity\Zonep;
use App\Form\ActiviteEcologiqueType;
use App\Form\ReservationType;
use App\Form\SurvzoneType;
use App\Form\ZonepType;
use App\Service\GroqMailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_root')]
    public function root(): Response
    {
        return $this->redirectToRoute('app_signIn');
    }

    #[Route('/home', name: 'app_home', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/home/zonep/new', name: 'app_home_zonep_new', methods: ['GET', 'POST'])]
    public function newZonep(Request $request, EntityManagerInterface $entityManager): Response
    {
        $zonep = new Zonep();
        $form = $this->createForm(ZonepType::class, $zonep);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($zonep);
            $entityManager->flush();

            return $this->redirectToRoute('app_home');
        }

        return $this->render('zonep/front/new.html.twig', [
            'zonep' => $zonep,
            'form' => $form,
        ]);
    }

    #[Route('/home/survzone/new', name: 'app_home_survzone_new', methods: ['GET', 'POST'])]
    public function newSurvzone(Request $request, EntityManagerInterface $entityManager): Response
    {
        $survzone = new Survzone();
        $form = $this->createForm(SurvzoneType::class, $survzone);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($survzone);
            $entityManager->flush();
            $this->addFlash('success', 'La surveillance a été ajoutée avec succès !');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('survzone/front/new.html.twig', [
            'survzone' => $survzone,
            'form' => $form,
        ]);
    }

    #[Route('/home/activite-ecologique/new', name: 'app_home_activite_ecologique_new', methods: ['GET', 'POST'])]
    public function newActiviteEcologique(Request $request, EntityManagerInterface $entityManager): Response
    {
        $activiteEcologique = new ActiviteEcologique();
        $form = $this->createForm(ActiviteEcologiqueType::class, $activiteEcologique);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Impossible de créer l\'activité. Vérifiez les champs saisis.');

            return $this->redirect($this->generateUrl('app_home') . '#slide09', Response::HTTP_SEE_OTHER);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $isRecurring = (bool) $form->get('is_recurring')->getData();

            if (!$isRecurring) {
                $entityManager->persist($activiteEcologique);
                $entityManager->flush();
                $this->addFlash('success', 'Activité écologique créée avec succès.');

                return $this->redirect($this->generateUrl('app_home') . '#slide09', Response::HTTP_SEE_OTHER);
            }

            $startDate = $activiteEcologique->getDate_activite();
            $endDate = $form->get('recurrence_end_date')->getData();
            $selectedDays = $form->get('recurrence_days')->getData();

            if (!$startDate instanceof \DateTimeInterface || !$endDate instanceof \DateTimeInterface) {
                $form->get('recurrence_end_date')->addError(new \Symfony\Component\Form\FormError('Veuillez choisir une date de fin valide.'));
            } elseif ($endDate < $startDate) {
                $form->get('recurrence_end_date')->addError(new \Symfony\Component\Form\FormError('La date de fin doit être supérieure ou égale à la date de début.'));
            } elseif (!is_array($selectedDays) || count($selectedDays) === 0) {
                $form->get('recurrence_days')->addError(new \Symfony\Component\Form\FormError('Sélectionnez au moins un jour de répétition.'));
            } else {
                $normalizedDays = array_map('intval', $selectedDays);
                sort($normalizedDays);

                $dates = [];
                $cursor = \DateTime::createFromFormat('Y-m-d', $startDate->format('Y-m-d'));
                $limit = \DateTime::createFromFormat('Y-m-d', $endDate->format('Y-m-d'));

                while ($cursor <= $limit) {
                    if (in_array((int) $cursor->format('N'), $normalizedDays, true)) {
                        $dates[] = clone $cursor;
                    }
                    $cursor->modify('+1 day');
                }

                if (count($dates) === 0) {
                    $form->get('recurrence_days')->addError(new \Symfony\Component\Form\FormError('Aucune occurrence trouvée avec les jours sélectionnés.'));
                } else {
                    foreach ($dates as $index => $date) {
                        $item = $index === 0 ? $activiteEcologique : new ActiviteEcologique();
                        $item
                            ->setNom_activite((string) $activiteEcologique->getNom_activite())
                            ->setDescription($activiteEcologique->getDescription())
                            ->setCapacite((int) $activiteEcologique->getCapacite())
                            ->setDate_activite($date);
                        $entityManager->persist($item);
                    }

                    $entityManager->flush();
                    $this->addFlash('success', sprintf('%d activité(s) créée(s) avec récurrence.', count($dates)));

                    return $this->redirect($this->generateUrl('app_home') . '#slide09', Response::HTTP_SEE_OTHER);
                }
            }

            $this->addFlash('error', 'Récurrence invalide. Vérifiez la date de fin et les jours sélectionnés.');

            return $this->redirect($this->generateUrl('app_home') . '#slide09', Response::HTTP_SEE_OTHER);
        }

        return $this->render('activite_ecologique/front/new.html.twig', [
            'activite_ecologique' => $activiteEcologique,
            'form' => $form,
        ]);
    }

    #[Route('/home/reservation/new', name: 'app_home_reservation_new', methods: ['GET', 'POST'])]
    public function newReservation(Request $request, EntityManagerInterface $entityManager, GroqMailService $groqMailService): Response
    {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Votre réservation a été enregistrée avec succès !');

            try {
                $groqMailService->sendReservationConfirmation(
                    (string) $reservation->getEmail(),
                    (string) $reservation->getNom(),
                    (string) ($reservation->getActiviteEcologique()?->getNom_activite() ?? 'Activite EcoMarine'),
                    $reservation->getDate_reservation()?->format('d/m/Y') ?? ''
                );
            } catch (\Throwable) {
                $this->addFlash('error', 'Réservation enregistrée, mais l\'email de confirmation n\'a pas pu être envoyé.');
            }

            return $this->redirect($this->generateUrl('app_home') . '#slide08', Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/front/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }
}