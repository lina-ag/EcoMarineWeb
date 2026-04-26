<?php

namespace App\Controller;
use App\Service\FaceRecognitionService;
use App\Entity\Utilisateur;
use App\Entity\Role;
use App\Form\UtilisateurType;
use App\Repository\UtilisateurRepository;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Sonata\Exporter\Exporter;
use Sonata\Exporter\Source\IteratorCallbackSourceIterator;
use Sonata\Exporter\Writer\XlsxWriter;
use Sonata\Exporter\Writer\CsvWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/utilisateur')]
final class UtilisateurController extends AbstractController
{
    #[Route(name: 'app_utilisateur_index', methods: ['GET'])]
public function index(
    Request $request,
    UtilisateurRepository $utilisateurRepository,
    RoleRepository $roleRepository,
    PaginatorInterface $paginator
): Response {
    $search = trim((string) $request->query->get('search', ''));
    $role   = (string) $request->query->get('role', 'all');

    $query = $utilisateurRepository->searchUsersQuery($search, $role);
    $roles = $roleRepository->findAll();

    $utilisateurs = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        5
    );

    return $this->render('utilisateur/index.html.twig', [
        'utilisateurs' => $utilisateurs,
        'search'       => $search,
        'selectedRole' => $role,
        'roles'        => $roles,
    ]);
}


    #[Route('/new', name: 'app_utilisateur_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, UtilisateurRepository $utilisateurRepository, RoleRepository $roleRepository, FaceRecognitionService $faceService ): Response
    {
        $utilisateur = new Utilisateur();
        $utilisateur->setCreatedAt(new \DateTime());
        
        // 🔥 Rôle par défaut : utilisateur
        $defaultRole = $roleRepository->findOneBy(['nomRole' => 'utilisateur']);
        if ($defaultRole) {
            $utilisateur->setRole($defaultRole);
        }
        
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

    $plainPassword = $utilisateur->getMotDePasse();
    if (!empty($plainPassword)) {
        $utilisateur->setMotDePasse(password_hash($plainPassword, PASSWORD_BCRYPT));
    }

    $existingUser = $utilisateurRepository->findOneBy(['email' => $utilisateur->getEmail()]);
    if ($existingUser) {
        $this->addFlash('error', 'Cet email est déjà utilisé par un autre utilisateur.');
        return $this->render('utilisateur/new.html.twig', ['utilisateur' => $utilisateur, 'form' => $form]);
    }

    if (!preg_match('/@gmail\.com$/', $utilisateur->getEmail())) {
        $this->addFlash('error', "L'email doit être une adresse Gmail (@gmail.com)");
        return $this->render('utilisateur/new.html.twig', ['utilisateur' => $utilisateur, 'form' => $form]);
    }

    $dateNaissance = $utilisateur->getDateNaissance();
    $today = (new \DateTime())->setTime(0, 0, 0);
    if ($dateNaissance && $dateNaissance >= $today) {
        $this->addFlash('error', "La date de naissance doit être strictement inférieure à aujourd'hui");
        return $this->render('utilisateur/new.html.twig', ['utilisateur' => $utilisateur, 'form' => $form]);
    }

    $errors = $validator->validate($utilisateur);
    if (count($errors) > 0) {
        foreach ($errors as $error) {
            $this->addFlash('error', $error->getMessage());
        }
        return $this->render('utilisateur/new.html.twig', ['utilisateur' => $utilisateur, 'form' => $form]);
    }


    $entityManager->persist($utilisateur);
    $entityManager->flush();

    $this->addFlash('success', 'Utilisateur créé avec succès !');
    return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
}

        return $this->render('utilisateur/new.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form,
        ]);
    }

    #[Route('/{id_utilisateur}', name: 'app_utilisateur_show', methods: ['GET'])]
    public function show(Utilisateur $utilisateur): Response
    {
        return $this->render('utilisateur/show.html.twig', [
            'utilisateur' => $utilisateur,
        ]);
    }

    #[Route('/{id_utilisateur}/edit', name: 'app_utilisateur_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager, ValidatorInterface $validator, UtilisateurRepository $utilisateurRepository): Response
    {
        $originalPassword = $utilisateur->getMotDePasse();
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // 🔐 Gestion du mot de passe
            $newPassword = $utilisateur->getMotDePasse();
            
            if (!empty($newPassword) && $newPassword !== $originalPassword) {
                $utilisateur->setMotDePasse(password_hash($newPassword, PASSWORD_BCRYPT));
            } else {
                $utilisateur->setMotDePasse($originalPassword);
            }
            
            // 🔥 Vérifier que l'email n'existe pas déjà (sauf pour l'utilisateur actuel)
            $existingUser = $utilisateurRepository->findOneBy(['email' => $utilisateur->getEmail()]);
            if ($existingUser && $existingUser->getIdUtilisateur() !== $utilisateur->getIdUtilisateur()) {
                $this->addFlash('error', 'Cet email est déjà utilisé par un autre utilisateur.');
                return $this->render('utilisateur/edit.html.twig', [
                    'utilisateur' => $utilisateur,
                    'form' => $form,
                ]);
            }
            
            // 🔥 Vérifier que l'email est Gmail
            $email = $utilisateur->getEmail();
            if (!preg_match('/@gmail\.com$/', $email)) {
                $this->addFlash('error', 'L\'email doit être une adresse Gmail (@gmail.com)');
                return $this->render('utilisateur/edit.html.twig', [
                    'utilisateur' => $utilisateur,
                    'form' => $form,
                ]);
            }
            
            // 🔥 Vérifier que la date de naissance est strictement dans le passé
            $dateNaissance = $utilisateur->getDateNaissance();
            $today = new \DateTime();
            $today->setTime(0, 0, 0);

            if ($dateNaissance && $dateNaissance >= $today) {
                $this->addFlash('error', 'La date de naissance doit être strictement inférieure à la date d\'aujourd\'hui');
                return $this->render('utilisateur/edit.html.twig', [
                    'utilisateur' => $utilisateur,
                    'form' => $form,
                ]);
            }
            
            $errors = $validator->validate($utilisateur);
            
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('utilisateur/edit.html.twig', [
                    'utilisateur' => $utilisateur,
                    'form' => $form,
                ]);
            }
            
            $entityManager->flush();
            
            $this->addFlash('success', 'Utilisateur modifié avec succès !');

            return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('utilisateur/edit.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form,
        ]);
    }

    #[Route('/{id_utilisateur}', name: 'app_utilisateur_delete', methods: ['POST'])]
    public function delete(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$utilisateur->getIdUtilisateur(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($utilisateur);
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur supprimé avec succès !');
        }

        return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
    }
    #[Route('/export/excel', name: 'app_utilisateur_export_excel', methods: ['GET'])]
public function exportExcel(
    UtilisateurRepository $utilisateurRepository,
    Exporter $exporter
): StreamedResponse {
    $utilisateurs = $utilisateurRepository->findAll();

    $source = new IteratorCallbackSourceIterator(
        new \ArrayIterator($utilisateurs),
        function (Utilisateur $u) {
            return [
                'ID'             => $u->getIdUtilisateur(),
                'Nom'            => $u->getNom(),
                'Prénom'         => $u->getPrenom(),
                'Email'          => $u->getEmail(),
                'Téléphone'      => $u->getTelephone(),
                'Rôle'           => $u->getRoleName(),
                'Date naissance' => $u->getDateNaissance()?->format('d/m/Y'),
                'Créé le'        => $u->getCreatedAt()?->format('d/m/Y H:i'),
            ];
        }
    );

    return $exporter->getResponse(
        'xlsx',
        'utilisateurs_' . date('Y-m-d') . '.xlsx',
        $source
    );
}
}