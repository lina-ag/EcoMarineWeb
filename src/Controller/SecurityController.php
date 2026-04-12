<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class SecurityController extends AbstractController
{
    #[Route('/security', name: 'app_security')]
    public function index(): Response
    {
        return $this->render('security/index.html.twig', [
            'controller_name' => 'SecurityController',
        ]);
    }
    #[Route('/signUp', name: 'app_signUp')]
    public function signUp(Request $request, EntityManagerInterface $em): Response
    {
        $user = new Utilisateur();

        $form = $this->createForm(UtilisateurType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // 🔐 Hash mot de passe (important)
            $user->setMotDePasse(password_hash($user->getMotDePasse(), PASSWORD_BCRYPT));

            // 📅 date création
            $user->setCreatedAt(new \DateTime());

            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('app_signIn');
        }

        return $this->render('security/signUp.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/signIn', name: 'app_signIn')]
public function signIn(Request $request, EntityManagerInterface $em, SessionInterface $session): Response
{
    if ($request->isMethod('POST')) {

        $email = $request->request->get('email');
        $password = $request->request->get('password');

        // Special case for admin
        if ($email === 'admin@gmail.com' && $password === 'admin') {
            $adminUser = $em->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
            if ($adminUser) {
                $session->set('user', $adminUser);
            } else {
                // Create admin user if not exists
                $admin = new Utilisateur();
                $admin->setEmail('admin@gmail.com');
                $admin->setMotDePasse(password_hash('admin', PASSWORD_BCRYPT));
                $admin->setNom('Admin');
                $admin->setPrenom('Admin');
                $admin->setTelephone('21234567');
                $admin->setRole('admin');
                $admin->setDateNaissance(new \DateTime('1990-01-01'));
                $admin->setCreatedAt(new \DateTime());
                $em->persist($admin);
                $em->flush();
                $session->set('user', $admin);
            }
            return $this->redirectToRoute('admin_dashboard');
        }

        $user = $em->getRepository(Utilisateur::class)
                   ->findOneBy(['email' => $email]);

        if (!$user) {
            $this->addFlash('error', 'Email incorrect');
            return $this->redirectToRoute('app_signIn');
        }

        // 🔐 vérifier mot de passe
        if (!password_verify($password, $user->getMotDePasse())) {
            $this->addFlash('error', 'Mot de passe incorrect');
            return $this->redirectToRoute('app_signIn');
        }

        // ✅ session
        $session->set('user', $user);

        // 🔥 redirection selon rôle
        if ($user->getRole() === 'admin') {
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->redirectToRoute('user_home');
    }

    return $this->render('security/signIn.html.twig');
}
     // 👑 ADMIN PAGE
    #[Route('/admin', name: 'admin_dashboard')]
    public function admin()
    {
        return $this->render('admin/dashboard.html.twig');
    }

    // 👤 USER PAGE
    #[Route('/home', name: 'user_home')]
    public function home(): Response
    {
        return new Response("Bienvenue utilisateur 👤");
    }
}
