<?php

namespace App\Controller;

use App\Entity\ActiviteEcologique;
use App\Form\ActiviteEcologiqueType;
use App\Repository\ActiviteEcologiqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/activite/ecologique')]
final class ActiviteEcologiqueController extends AbstractController
{
    #[Route(name: 'app_activite_ecologique_index', methods: ['GET'])]
    public function index(Request $request, ActiviteEcologiqueRepository $activiteEcologiqueRepository): Response
    {
        $searchTerm = trim((string) $request->query->get('q', ''));

        $queryBuilder = $activiteEcologiqueRepository
            ->createQueryBuilder('a')
            ->orderBy('a.date_activite', 'DESC');

        if ($searchTerm !== '') {
            $queryBuilder
                ->andWhere('LOWER(a.nom_activite) LIKE :term OR LOWER(a.description) LIKE :term')
                ->setParameter('term', '%' . mb_strtolower($searchTerm) . '%');

            if (ctype_digit($searchTerm)) {
                $queryBuilder
                    ->orWhere('a.id_activite = :id OR a.capacite = :capacity')
                    ->setParameter('id', (int) $searchTerm)
                    ->setParameter('capacity', (int) $searchTerm);
            }
        }

        return $this->render('activite_ecologique/index.html.twig', [
            'activite_ecologiques' => $queryBuilder->getQuery()->getResult(),
            'search_term' => $searchTerm,
        ]);
    }

    #[Route('/new', name: 'app_activite_ecologique_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $activiteEcologique = new ActiviteEcologique();
        $form = $this->createForm(ActiviteEcologiqueType::class, $activiteEcologique);
        $form->handleRequest($request);
        $fromFront = $request->query->get('source') === 'front';

        if ($form->isSubmitted() && !$form->isValid() && $fromFront) {
            $this->addFlash('error', 'Impossible de créer l\'activité. Vérifiez les champs saisis.');

            return $this->redirect($this->generateUrl('app_home') . '#slide09', Response::HTTP_SEE_OTHER);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $isRecurring = (bool) $form->get('is_recurring')->getData();

            if (!$isRecurring) {
                $entityManager->persist($activiteEcologique);
                $entityManager->flush();

                $this->addFlash('success', 'Activité écologique créée avec succès.');

                if ($fromFront) {
                    return $this->redirect($this->generateUrl('app_home') . '#slide09', Response::HTTP_SEE_OTHER);
                }

                return $this->redirectToRoute('app_activite_ecologique_index', [], Response::HTTP_SEE_OTHER);
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

                    $this->addFlash('success', sprintf('%d activité(s) écologique(s) créée(s) avec récurrence.', count($dates)));

                    if ($fromFront) {
                        return $this->redirect($this->generateUrl('app_home') . '#slide09', Response::HTTP_SEE_OTHER);
                    }

                    return $this->redirectToRoute('app_activite_ecologique_index', [], Response::HTTP_SEE_OTHER);
                }
            }

            if ($fromFront) {
                $this->addFlash('error', 'Récurrence invalide. Vérifiez la date de fin et les jours sélectionnés.');

                return $this->redirect($this->generateUrl('app_home') . '#slide09', Response::HTTP_SEE_OTHER);
            }

            return $this->render('activite_ecologique/new.html.twig', [
                'activite_ecologique' => $activiteEcologique,
                'form' => $form,
            ]);
        }

        return $this->render('activite_ecologique/new.html.twig', [
            'activite_ecologique' => $activiteEcologique,
            'form' => $form,
        ]);
    }

    #[Route('/{id_activite}', name: 'app_activite_ecologique_show', methods: ['GET'])]
    public function show(ActiviteEcologique $activiteEcologique): Response
    {
        return $this->render('activite_ecologique/show.html.twig', [
            'activite_ecologique' => $activiteEcologique,
        ]);
    }

    #[Route('/{id_activite}/edit', name: 'app_activite_ecologique_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ActiviteEcologique $activiteEcologique, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ActiviteEcologiqueType::class, $activiteEcologique);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_activite_ecologique_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activite_ecologique/edit.html.twig', [
            'activite_ecologique' => $activiteEcologique,
            'form' => $form,
        ]);
    }

    #[Route('/{id_activite}', name: 'app_activite_ecologique_delete', methods: ['POST'])]
    public function delete(Request $request, ActiviteEcologique $activiteEcologique, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$activiteEcologique->getId_activite(), $request->getPayload()->getString('_token'))) {
            // Keep reservations history even if the linked activity is deleted.
            foreach ($activiteEcologique->getReservations() as $reservation) {
                $reservation->setActiviteEcologique(null);
            }

            $entityManager->remove($activiteEcologique);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_activite_ecologique_index', [], Response::HTTP_SEE_OTHER);
    }
}
