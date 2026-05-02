<?php
namespace App\Controller;

use App\Bundle\ForgotPasswordBundle\Service\ForgotPasswordService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ForgotPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
public function request(Request $request, ForgotPasswordService $service): Response
{
    if ($request->isMethod('POST')) {
        $email = $request->request->get('email');
        
        try {
            $result = $service->sendResetEmail($email);
            dump($result);
            if ($result) {
                $this->addFlash('success', 'Email envoyé avec succès !');
            } else {
                $this->addFlash('error', 'Email introuvable dans la base de données.');
            }
        } catch (\Exception $e) {
            dump('ERREUR: ' . $e->getMessage());
            $this->addFlash('error', 'Erreur: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('app_forgot_password');
    }
    return $this->render('security/forgot_password.html.twig');
}

    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function reset(string $token, Request $request, ForgotPasswordService $service): Response
    {
        $email = $service->validateToken($token);
        if (!$email) {
            $this->addFlash('error', 'Lien invalide ou expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('password');
            $confirm     = $request->request->get('confirm_password');

            if ($newPassword !== $confirm) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_reset_password', ['token' => $token]);
            }

            if (strlen($newPassword) < 8) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
                return $this->redirectToRoute('app_reset_password', ['token' => $token]);
            }

            $service->resetPassword($token, $newPassword);
            $this->addFlash('success', 'Mot de passe réinitialisé avec succès !');
            return $this->redirectToRoute('app_signIn');
        }

        return $this->render('security/reset_password.html.twig', ['token' => $token]);
    }
}