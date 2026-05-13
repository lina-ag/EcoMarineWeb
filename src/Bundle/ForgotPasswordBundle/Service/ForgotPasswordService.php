<?php
namespace App\Bundle\ForgotPasswordBundle\Service;

use App\Repository\UtilisateurRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ForgotPasswordService
{
    public function __construct(
        private UtilisateurRepository $utilisateurRepository,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $router,
        private EntityManagerInterface $em,
        private RequestStack $requestStack
    ) {}

    public function sendResetEmail(string $email): bool
    {
        $user = $this->utilisateurRepository->findOneBy(['email' => $email]);
        if (!$user) {
            return false;
        }

        $token = bin2hex(random_bytes(32));
        $expiry = new \DateTime('+1 hour');

        $session = $this->requestStack->getSession();
        $session->set('reset_token_' . $token, [
            'email'   => $email,
            'expires' => $expiry->getTimestamp(),
        ]);

        $resetUrl = $this->router->generate('app_reset_password',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        try {
            $emailMessage = (new Email())
                ->from('noreply@ecomarine.com')
                ->to($email)
                ->subject('Réinitialisation de votre mot de passe - EcoMarine')
                ->html("
                    <h2>Réinitialisation du mot de passe</h2>
                    <p>Bonjour {$user->getPrenom()},</p>
                    <p>Cliquez sur le lien ci-dessous pour réinitialiser votre mot de passe :</p>
                    <a href='{$resetUrl}' style='background:#2ec4b6; color:white; padding:12px 24px;
                       border-radius:8px; text-decoration:none;'>
                       Réinitialiser mon mot de passe
                    </a>
                    <p>Ce lien expire dans <strong>1 heure</strong>.</p>
                    <p>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
                ");

            $this->mailer->send($emailMessage);
            return true;

        } catch (\Exception $e) {
            dump('Erreur: ' . $e->getMessage());
            return false;
        }
        
    }

    public function validateToken(string $token): ?string
    {
        $session = $this->requestStack->getSession();
        $data = $session->get('reset_token_' . $token);
        if (!$data) return null;
        if (time() > $data['expires']) return null;
        return $data['email'];
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $email = $this->validateToken($token);
        if (!$email) return false;

        $user = $this->utilisateurRepository->findOneBy(['email' => $email]);
        if (!$user) return false;

        $user->setMotDePasse(password_hash($newPassword, PASSWORD_BCRYPT));
        $this->em->flush();

        $session = $this->requestStack->getSession();
        $session->remove('reset_token_' . $token);
        return true;
    }
}