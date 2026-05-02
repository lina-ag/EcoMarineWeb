<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Utilisateur;
use App\Entity\Role;
use App\Form\SignUpType;
use App\Service\FaceRecognitionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\BlockedCountryRepository;
use App\Service\GeoIpService;

final class SecurityController extends AbstractController
{
    #[Route('/security', name: 'app_security')]
    public function index(): Response
    {
        return $this->render('security/index.html.twig', [
            'controller_name' => 'SecurityController',
        ]);
    }

    // ─────────────────────────────────────────────
    // INSCRIPTION
    // ─────────────────────────────────────────────
    #[Route('/signUp', name: 'app_signUp')]
    public function signUp(
        Request $request,
        EntityManagerInterface $em,
        SessionInterface $session,
        FaceRecognitionService $faceService,
        GeoIpService $geoIpService,
        BlockedCountryRepository $blockedCountryRepo
    ): Response {
        $user = new Utilisateur();

        $defaultRole = $em->getRepository(Role::class)->findOneBy(['nomRole' => 'utilisateur']);
        if ($defaultRole) {
            $user->setRole($defaultRole);
        }

// ── Vérification du pays ──
$ip = $request->getClientIp();

// ⚠️ Cas local (dev)
if ($ip === '127.0.0.1' || $ip === '::1') {
    $ip = '41.226.0.1'; // Tunisie pour test
}

$location = $geoIpService->getCountryFromIp($ip);

// ⚠️ Sécurité si API retourne null
if (!$location || !isset($location['country_code'])) {
    $this->addFlash('error', "Impossible de détecter votre localisation.");
    return $this->redirectToRoute('app_signIn');
}

// 🚫 Pays bloqué
if ($blockedCountryRepo->isCountryBlocked($location['country_code'])) {

    $this->addFlash('error',
        "🚫 L'inscription est bloquée depuis votre pays ({$location['country_name']})."
    );

    return $this->redirectToRoute('app_signIn');
}
        $form = $this->createForm(SignUpType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires avant de vous inscrire.');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setMotDePasse(password_hash($user->getMotDePasse(), PASSWORD_BCRYPT));
            $user->setCreatedAt(new \DateTime());

            $imageBase64 = $request->request->get('face_image_data');

            if (
                !$user->getNom() ||
                !$user->getPrenom() ||
                !$user->getEmail() ||
                !$user->getMotDePasse() ||
                !$user->getTelephone() ||
                !$user->getRole() ||
                !$user->getDateNaissance()
            ) {
                $this->addFlash('error', 'Tous les champs sont obligatoires.');
                return $this->render('security/signUp.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            if ($user->getRoleName() === 'chercheur' && !$imageBase64) {
                $this->addFlash('error', 'Veuillez remplir tous les champs et capturer votre visage avant de vous inscrire.');
                return $this->render('security/signUp.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            if ($user->getRoleName() === 'chercheur' && $imageBase64) {
    $encoding = $faceService->extractEncoding($imageBase64);
    if ($encoding) {
        $user->setFaceEncoding($encoding);
    } else {
        // ⚠️ Sauvegarder quand même l'utilisateur, rediriger vers page visage
        $em->persist($user);
        $em->flush();
        $session->set('user_id_for_face_registration', $user->getIdUtilisateur());
        $this->addFlash('error', 'Visage non détecté. Veuillez réessayer sur la page suivante.');
        return $this->redirectToRoute('app_signUp');
    }
}

$em->persist($user);
$em->flush();

            if ($user->getRoleName() === 'chercheur' && !$imageBase64) {
                $session->set('user_id_for_face_registration', $user->getIdUtilisateur());
                $this->addFlash('success', 'Inscription réussie ! Enregistrez maintenant votre visage.');
                return $this->redirectToRoute('app_home');
            }

            $this->addFlash('success', 'Inscription réussie !');
            return $this->redirectToRoute('app_signIn');
        }

        return $this->render('security/signUp.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ─────────────────────────────────────────────
    // CONNEXION CLASSIQUE
    // ─────────────────────────────────────────────
    #[Route('/signIn', name: 'app_signIn')]
    public function signIn(Request $request, EntityManagerInterface $em, SessionInterface $session
    ): Response
    {
        if ($request->isMethod('POST')) {
            $email    = $request->request->get('email');
            $password = $request->request->get('password');

            // Admin spécial
            if ($email === 'admin@gmail.com' && $password === 'admin') {
                $adminUser = $em->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
                if ($adminUser) {
                    $session->set('user', $adminUser);
                } else {
                    $adminRole = $em->getRepository(Role::class)->findOneBy(['nomRole' => 'admin']);
                    $admin = new Utilisateur();
                    $admin->setEmail('admin@gmail.com');
                    $admin->setMotDePasse(password_hash('admin', PASSWORD_BCRYPT));
                    $admin->setNom('Admin');
                    $admin->setPrenom('Admin');
                    $admin->setTelephone('21234567');
                    if ($adminRole) $admin->setRole($adminRole);
                    $admin->setDateNaissance(new \DateTime('1990-01-01'));
                    $admin->setCreatedAt(new \DateTime());
                    $em->persist($admin);
                    $em->flush();
                    $session->set('user', $admin);
                }
                return $this->redirectToRoute('admin_dashboard');
            }

            $user = $em->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $this->addFlash('error', 'Email incorrect');
                return $this->redirectToRoute('app_signIn');
            }
            if ($user->isBlocked()) {
                $this->addFlash('error', '🚫 Votre compte a été bloqué. Contactez l\'administrateur.');
                return $this->redirectToRoute('app_signIn');
            }

            if (!password_verify($password, $user->getMotDePasse())) {
                $this->addFlash('error', 'Mot de passe incorrect');
                return $this->redirectToRoute('app_signIn');
            }

            $session->set('user', $user);

            return $user->getRoleName() === 'admin'
                ? $this->redirectToRoute('admin_dashboard')
                : $this->redirectToRoute('user_home');
        }

        return $this->render('security/signIn.html.twig');
    }

    // ─────────────────────────────────────────────
    // CONNEXION PAR VISAGE (page)
    // ─────────────────────────────────────────────


    // ─────────────────────────────────────────────
    // CONNEXION PAR VISAGE (AJAX)
    // ─────────────────────────────────────────────
    #[Route('/face-recognize', name: 'face_recognize', methods: ['POST'])]
    public function faceRecognize(
        Request $request,
        SessionInterface $session,
        FaceRecognitionService $faceService
    ): JsonResponse {
        $data  = json_decode($request->getContent(), true);
        $image = $data['image'] ?? null;

        if (!$image) {
            return $this->json(['success' => false, 'message' => 'Aucune image reçue']);
        }

        $user = $faceService->recognizeFace($image);

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Visage non reconnu. Essayez encore.']);
        }

        $session->set('user', $user);

        $redirect = $user->getRoleName() === 'admin'
            ? $this->generateUrl('admin_dashboard')
            : $this->generateUrl('user_home');

        return $this->json([
            'success'  => true,
            'redirect' => $redirect,
            'message'  => 'Connexion réussie, bonjour ' . $user->getPrenom() . ' !',
        ]);
    }

    // ─────────────────────────────────────────────
    // ENREGISTREMENT VISAGE après inscription (page)
    // ─────────────────────────────────────────────
   
    // ─────────────────────────────────────────────
    // ENREGISTREMENT VISAGE après inscription (AJAX)
    // ─────────────────────────────────────────────
    #[Route('/face-register', name: 'face_register', methods: ['POST'])]
    public function faceRegister(
        Request $request,
        EntityManagerInterface $em,
        SessionInterface $session,
        FaceRecognitionService $faceService
    ): JsonResponse {
        $data  = json_decode($request->getContent(), true);
        $image = $data['image'] ?? null;

        if (!$image) {
            return $this->json(['success' => false, 'message' => 'Aucune image reçue']);
        }

        $userId = $session->get('user_id_for_face_registration');
        if (!$userId) {
            return $this->json(['success' => false, 'message' => 'Session expirée. Veuillez vous réinscrire.']);
        }

        $user = $em->getRepository(Utilisateur::class)->find($userId);
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Utilisateur non trouvé']);
        }

        $encoding = $faceService->extractEncoding($image);
        if (!$encoding) {
            return $this->json(['success' => false, 'message' => 'Aucun visage détecté. Réessayez.']);
        }

        $user->setFaceEncoding($encoding);
        $em->flush();
        $session->remove('user_id_for_face_registration');

        return $this->json(['success' => true, 'message' => 'Visage enregistré avec succès !']);
    }

    // ─────────────────────────────────────────────
    // ADMIN
    // ─────────────────────────────────────────────
    #[Route('/admin', name: 'admin_dashboard')]
    public function admin(SessionInterface $session): Response
    {
        $user = $session->get('user');
        if (!$user || $user->getRoleName() !== 'admin') {
            $this->addFlash('error', 'Accès non autorisé');
            return $this->redirectToRoute('app_signIn');
        }
        return $this->render('admin/dashboard.html.twig', ['user' => $user]);
    }

    // ─────────────────────────────────────────────
    // HOME UTILISATEUR
    // ─────────────────────────────────────────────
    #[Route('/home', name: 'user_home')]
    public function home(SessionInterface $session): Response
    {
        $user = $session->get('user');
        if (!$user) {
            return $this->redirectToRoute('app_signIn');
        }
        return $this->render('user/home.html.twig', ['user' => $user]);
    }

    // ─────────────────────────────────────────────
    // DÉCONNEXION
    // ─────────────────────────────────────────────
    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(SessionInterface $session): Response
    {
        $session->invalidate();
        $this->addFlash('success', 'Vous avez été déconnecté.');
        return $this->redirectToRoute('app_signIn');
    }
}
