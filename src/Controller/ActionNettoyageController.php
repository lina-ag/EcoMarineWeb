<?php

namespace App\Controller;

use App\Entity\ActionNettoyage;
use App\Entity\Volontaire;
use App\Form\ActionNettoyageType;
use App\Repository\ActionNettoyageRepository;
use App\Repository\VolontaireRepository;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/action-nettoyage')]
final class ActionNettoyageController extends AbstractController
{
    #[Route('/dashboard', name: 'app_action_nettoyage_dashboard', methods: ['GET'])]
    public function dashboard(
        ActionNettoyageRepository $actionNettoyageRepository,
        VolontaireRepository $volontaireRepository
    ): Response {
        $actions = $actionNettoyageRepository->findAll();
        $today = new \DateTimeImmutable('today');

        $totalActions = count($actions);
        $totalVolontaires = $volontaireRepository->count([]);
        $actionsCompletes = 0;
        $actionsDisponibles = 0;
        $actionsAVenir = 0;
        $actionsPassees = 0;

        foreach ($actions as $action) {
            $isComplete = $action->estComplete();
            $actionDate = $this->extractActionDate($action);

            if ($isComplete) {
                ++$actionsCompletes;
            } else {
                ++$actionsDisponibles;
            }

            if ($actionDate instanceof \DateTimeInterface) {
                $normalizedActionDate = \DateTimeImmutable::createFromFormat('Y-m-d', $actionDate->format('Y-m-d'));

                if ($normalizedActionDate instanceof \DateTimeImmutable && $normalizedActionDate >= $today) {
                    ++$actionsAVenir;
                } elseif ($normalizedActionDate instanceof \DateTimeImmutable) {
                    ++$actionsPassees;
                }
            }
        }

        return $this->render('action_nettoyage/dashboard.html.twig', [
            'stats' => [
                'total_actions' => $totalActions,
                'total_volontaires' => $totalVolontaires,
                'actions_completes' => $actionsCompletes,
                'actions_disponibles' => $actionsDisponibles,
                'actions_a_venir' => $actionsAVenir,
                'actions_passees' => $actionsPassees,
            ],
        ]);
    }

    #[Route('', name: 'app_action_nettoyage_index', methods: ['GET'])]
    public function index(Request $request, ActionNettoyageRepository $repository, PaginatorInterface $paginator): Response
    {
        $selectedLieu = trim((string) $request->query->get('lieu', ''));
        $selectedDate = trim((string) $request->query->get('date_action', ''));
        $selectedStatut = trim((string) $request->query->get('statut', ''));

        $queryBuilder = $repository->createQueryBuilder('a')
            ->orderBy('a.date_action', 'ASC');

        if ($selectedLieu !== '') {
            $queryBuilder
                ->andWhere('LOWER(a.lieu) LIKE LOWER(:lieu)')
                ->setParameter('lieu', '%' . $selectedLieu . '%');
        }

        if ($selectedDate !== '') {
            $dateFilter = \DateTimeImmutable::createFromFormat('Y-m-d', $selectedDate);

            if ($dateFilter instanceof \DateTimeImmutable) {
                $queryBuilder
                    ->andWhere('a.date_action = :dateAction')
                    ->setParameter('dateAction', $dateFilter->format('Y-m-d'));
            }
        }

        if (in_array($selectedStatut, ['complete', 'disponible'], true)) {
            if ($selectedStatut === 'complete') {
                $queryBuilder->andWhere('
                    (
                        SELECT COUNT(v_complete.id_volontaire)
                        FROM App\Entity\Volontaire v_complete
                        WHERE v_complete.id_action = a
                    ) >= a.limiteBenevoles
                ');
            } else {
                $queryBuilder->andWhere('
                    (
                        SELECT COUNT(v_disponible.id_volontaire)
                        FROM App\Entity\Volontaire v_disponible
                        WHERE v_disponible.id_action = a
                    ) < a.limiteBenevoles
                ');
            }
        }

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            3
        );

        if ($request->isXmlHttpRequest() && $request->query->get('section') === 'admin_action_nettoyage') {
            return $this->render('action_nettoyage/_list.html.twig', [
                'action_nettoyages' => $pagination,
            ]);
        }

        return $this->render('action_nettoyage/index.html.twig', [
            'action_nettoyages' => $pagination,
            'filter_lieu' => $selectedLieu,
            'filter_date_action' => $selectedDate,
            'filter_statut' => $selectedStatut,
        ]);
    }

    #[Route('/new', name: 'app_action_nettoyage_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $actionNettoyage = new ActionNettoyage();

        $form = $this->createForm(ActionNettoyageType::class, $actionNettoyage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($actionNettoyage);
            $entityManager->flush();

            $this->addFlash('success', 'Action de nettoyage ajoutée avec succès.');

            return $this->redirectToRoute('app_action_nettoyage_index');
        }

        return $this->render('action_nettoyage/new.html.twig', [
            'action_nettoyage' => $actionNettoyage,
            'form' => $form,
        ]);
    }

    #[Route('/{id_action}', name: 'app_action_nettoyage_show', methods: ['GET'])]
    public function show(ActionNettoyage $actionNettoyage): Response
    {
        return $this->render('action_nettoyage/show.html.twig', [
            'action_nettoyage' => $actionNettoyage,
        ]);
    }

    #[Route('/{id_action}/edit', name: 'app_action_nettoyage_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        ActionNettoyage $actionNettoyage,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(ActionNettoyageType::class, $actionNettoyage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Action de nettoyage modifiée avec succès.');

            return $this->redirectToRoute('app_action_nettoyage_index');
        }

        return $this->render('action_nettoyage/edit.html.twig', [
            'action_nettoyage' => $actionNettoyage,
            'form' => $form,
        ]);
    }

    #[Route('/{id_action}', name: 'app_action_nettoyage_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        ActionNettoyage $actionNettoyage,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $actionNettoyage->getIdAction(), $request->request->get('_token'))) {
            $entityManager->remove($actionNettoyage);
            $entityManager->flush();

            $this->addFlash('success', 'Action de nettoyage supprimée avec succès.');
        }

        return $this->redirectToRoute('app_action_nettoyage_index');
    }

    /*
     * Partie utilisateur connecté :
     * Participer à une action de nettoyage.
     */
    #[Route('/{id_action}/participer', name: 'app_action_nettoyage_participer', methods: ['POST'])]
    public function participer(
        Request $request,
        ActionNettoyage $actionNettoyage,
        EntityManagerInterface $entityManager,
        VolontaireRepository $volontaireRepository,
        SessionInterface $session
    ): Response {
        $utilisateur = $session->get('user');

        if (!$utilisateur instanceof Utilisateur) {
            $this->addFlash('warning', 'Vous devez être connecté pour participer à une action de nettoyage.');

            return $this->redirectToRoute('app_signIn');
        }

        if (!$this->isCsrfTokenValid('participer' . $actionNettoyage->getIdAction(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide.');

            return $this->redirectToRoute('app_home', [
                '_fragment' => 'slide09',
            ]);
        }

        if ($actionNettoyage->estComplete()) {
            $this->addFlash('danger', 'Cette action de nettoyage est déjà complète.');

            return $this->redirectToRoute('app_home', [
                '_fragment' => 'slide09',
            ]);
        }

        $dejaInscrit = $volontaireRepository->findOneBy([
            'utilisateur' => $utilisateur,
            'id_action' => $actionNettoyage,
        ]);

        if ($dejaInscrit) {
            $this->addFlash('warning', 'Vous êtes déjà inscrit à cette action de nettoyage.');

            return $this->redirectToRoute('app_home', [
                '_fragment' => 'slide09',
            ]);
        }

        $nomComplet = trim(($utilisateur->getNom() ?? '') . ' ' . ($utilisateur->getPrenom() ?? ''));

        if ($nomComplet === '') {
            $nomComplet = 'Utilisateur';
        }

        $contact = $utilisateur->getTelephone();

        if (!$contact) {
            $contact = '00000000';
        }

        $volontaire = new Volontaire();
        $volontaire->setNom($nomComplet);
        $volontaire->setContact($contact);
        $volontaire->setIdAction($actionNettoyage);
        $volontaire->setUtilisateur($utilisateur);

        $entityManager->persist($volontaire);
        $entityManager->flush();

        $this->addFlash('success', 'Votre participation a été enregistrée avec succès.');

        return $this->redirectToRoute('app_home', [
            '_fragment' => 'slide09',
        ]);
    }

    /*
     * Partie utilisateur connecté :
     * Annuler sa propre participation.
     */
    #[Route('/{id_action}/annuler-participation', name: 'app_action_nettoyage_annuler_participation', methods: ['POST'])]
    public function annulerParticipation(
        Request $request,
        ActionNettoyage $actionNettoyage,
        EntityManagerInterface $entityManager,
        VolontaireRepository $volontaireRepository,
        SessionInterface $session
    ): Response {
        $utilisateur = $session->get('user');

        if (!$utilisateur instanceof Utilisateur) {
            $this->addFlash('warning', 'Vous devez être connecté.');

            return $this->redirectToRoute('app_signIn');
        }

        if (!$this->isCsrfTokenValid('annuler_participation' . $actionNettoyage->getIdAction(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide.');

            return $this->redirectToRoute('app_home', [
                '_fragment' => 'slide09',
            ]);
        }

        $inscription = $volontaireRepository->findOneBy([
            'utilisateur' => $utilisateur,
            'id_action' => $actionNettoyage,
        ]);

        if (!$inscription) {
            $this->addFlash('warning', 'Vous n’êtes pas inscrit à cette action de nettoyage.');

            return $this->redirectToRoute('app_home', [
                '_fragment' => 'slide09',
            ]);
        }

        $entityManager->remove($inscription);
        $entityManager->flush();

        $this->addFlash('success', 'Votre participation a été annulée.');

        return $this->redirectToRoute('app_home', [
            '_fragment' => 'slide09',
        ]);
    }

    private function extractActionDate(ActionNettoyage $actionNettoyage): ?\DateTimeInterface
    {
        if (method_exists($actionNettoyage, 'getDateAction')) {
            return $actionNettoyage->getDateAction();
        }

        if (method_exists($actionNettoyage, 'getDate_action')) {
            return $actionNettoyage->getDate_action();
        }

        return null;
    }
}
