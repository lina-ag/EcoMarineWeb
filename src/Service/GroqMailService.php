<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GroqMailService
{
    private const DEFAULT_FROM_EMAIL = 'ecomarine.reservation@outlook.com';

    private string $groqApiKey;

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly HttpClientInterface $httpClient,
    ) {
        $this->groqApiKey = $_ENV['GROQ_API_KEY'] ?? '';
    }

    public function sendReservationConfirmation(
        string $toEmail,
        string $toName,
        string $activityName,
        string $reservationDate,
    ): void {
        $this->assertMailerEnabled();

        $content = $this->generateReservationEmailContent(
            $toName,
            $activityName,
            $reservationDate,
        );

        $email = (new Email())
            ->from($this->getFromEmail())
            ->to($toEmail)
            ->subject('🌊 EcoMarine — Confirmation de réservation')
            ->html($content);

        $this->mailer->send($email);
    }

    public function sendQuizConfirmation(
        string $toEmail,
        string $toName,
        string $activityName,
        string $reservationDate,
        int $correctCount,
        int $total,
        string $badge
    ): void {
        $this->assertMailerEnabled();

        $badgeLabel = match($badge) {
            'gold' => '🥇 Badge Or — Explorateur Expert',
            'silver' => '🥈 Badge Argent — Explorateur Confirmé',
            default => '🥉 Badge Bronze — Explorateur Débutant',
        };

        // Génère le contenu via Groq
        $content = $this->generateEmailContent(
            $toName,
            $activityName,
            $reservationDate,
            $correctCount,
            $total,
            $badgeLabel
        );

        $email = (new Email())
            ->from($this->getFromEmail())
            ->to($toEmail)
            ->subject('🌊 EcoMarine — Confirmation de réservation & Badge Explorateur')
            ->html($content);

        $this->mailer->send($email);
    }

    private function generateReservationEmailContent(
        string $name,
        string $activityName,
        string $reservationDate,
    ): string {
        try {
            $prompt = "Tu es l'assistant de EcoMarine, une plateforme d'écotourisme sur l'île de Kuriat en Tunisie.
Génère un email de confirmation en français, chaleureux et professionnel, en HTML (inline styles uniquement).
L'email doit confirmer la réservation de {$name} pour l'activité '{$activityName}' le {$reservationDate}.
Utilise un style marin avec des couleurs #0f766e (vert marin) et #1d4ed8 (bleu).
Ajoute un message de bienvenue personnalisé et 3 conseils pratiques pour la visite.
Retourne uniquement le HTML de l'email, sans balises html/head/body.";

            $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama3-8b-8192',
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => 900,
                    'temperature' => 0.7,
                ],
            ]);

            $data = $response->toArray();

            return $data['choices'][0]['message']['content']
                ?? $this->fallbackReservationContent($name, $activityName, $reservationDate);
        } catch (\Throwable) {
            return $this->fallbackReservationContent($name, $activityName, $reservationDate);
        }
    }

    private function generateEmailContent(
        string $name,
        string $activityName,
        string $reservationDate,
        int $correctCount,
        int $total,
        string $badgeLabel
    ): string {
        try {
            $prompt = "Tu es l'assistant de EcoMarine, une plateforme d'écotourisme sur l'île de Kuriat en Tunisie. 
Génère un email de confirmation en français, chaleureux et professionnel, en HTML inline styles uniquement.
L'email doit confirmer :
- La réservation de {$name} pour l'activité '{$activityName}' le {$reservationDate}
- Le résultat du quiz écologique : {$correctCount}/{$total} bonnes réponses
- Le badge obtenu : {$badgeLabel}
Utilise un style marin avec des couleurs #0f766e (vert marin) et #1d4ed8 (bleu).
Inclus un message de bienvenue personnalisé et des conseils pour la visite.
Retourne uniquement le HTML de l'email, sans balises html/head/body.";

            $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama3-8b-8192',
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => 1000,
                    'temperature' => 0.7,
                ],
            ]);

            $data = $response->toArray();
            return $data['choices'][0]['message']['content'] ?? $this->fallbackContent($name, $activityName, $reservationDate, $correctCount, $total, $badgeLabel);
        } catch (\Throwable) {
            return $this->fallbackContent($name, $activityName, $reservationDate, $correctCount, $total, $badgeLabel);
        }
    }

    private function fallbackContent(
        string $name,
        string $activityName,
        string $reservationDate,
        int $correctCount,
        int $total,
        string $badgeLabel
    ): string {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 2rem; background: #f0f9ff; border-radius: 16px;'>
            <div style='text-align: center; margin-bottom: 2rem;'>
                <h1 style='color: #0f766e; font-size: 1.8rem; margin: 0;'>🌊 EcoMarine</h1>
                <p style='color: #475569; margin: 0.5rem 0 0;'>Plateforme d'écotourisme — Île de Kuriat</p>
            </div>

            <div style='background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; border-left: 4px solid #0f766e;'>
                <h2 style='color: #0f172a; margin: 0 0 1rem;'>Bonjour {$name} ! 👋</h2>
                <p style='color: #334155; margin: 0;'>Votre réservation a été confirmée avec succès.</p>
            </div>

            <div style='background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem;'>
                <h3 style='color: #1d4ed8; margin: 0 0 1rem;'>📋 Détails de la réservation</h3>
                <p style='margin: 0.5rem 0; color: #334155;'><strong>Activité :</strong> {$activityName}</p>
                <p style='margin: 0.5rem 0; color: #334155;'><strong>Date :</strong> {$reservationDate}</p>
            </div>

            <div style='background: linear-gradient(135deg, rgba(15,118,110,0.1), rgba(29,78,216,0.1)); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; text-align: center;'>
                <h3 style='color: #0f172a; margin: 0 0 0.5rem;'>🏆 Résultat du Quiz Écologique</h3>
                <p style='font-size: 2rem; margin: 0.5rem 0;'>{$badgeLabel}</p>
                <p style='color: #475569; margin: 0;'>{$correctCount}/{$total} bonnes réponses</p>
            </div>

            <div style='text-align: center; color: #64748b; font-size: 0.85rem; margin-top: 2rem;'>
                <p>Merci de choisir EcoMarine pour votre aventure écologique 🌿</p>
                <p style='margin: 0;'>© EcoMarine — Île de Kuriat, Tunisie</p>
            </div>
        </div>
        ";
    }

    private function fallbackReservationContent(
        string $name,
        string $activityName,
        string $reservationDate,
    ): string {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 2rem; background: #f0f9ff; border-radius: 16px;'>
            <div style='text-align: center; margin-bottom: 2rem;'>
                <h1 style='color: #0f766e; font-size: 1.8rem; margin: 0;'>🌊 EcoMarine</h1>
                <p style='color: #475569; margin: 0.5rem 0 0;'>Plateforme d'écotourisme — Île de Kuriat</p>
            </div>

            <div style='background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; border-left: 4px solid #0f766e;'>
                <h2 style='color: #0f172a; margin: 0 0 1rem;'>Bonjour {$name} ! 👋</h2>
                <p style='color: #334155; margin: 0;'>Votre réservation a été enregistrée avec succès.</p>
            </div>

            <div style='background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem;'>
                <h3 style='color: #1d4ed8; margin: 0 0 1rem;'>📋 Détails de la réservation</h3>
                <p style='margin: 0.5rem 0; color: #334155;'><strong>Activité :</strong> {$activityName}</p>
                <p style='margin: 0.5rem 0; color: #334155;'><strong>Date :</strong> {$reservationDate}</p>
            </div>

            <div style='background: linear-gradient(135deg, rgba(15,118,110,0.1), rgba(29,78,216,0.1)); border-radius: 12px; padding: 1.25rem; margin-bottom: 1.5rem;'>
                <h3 style='color: #0f172a; margin: 0 0 0.75rem;'>🧭 Conseils pour votre visite</h3>
                <ul style='margin: 0; padding-left: 1.2rem; color: #334155;'>
                    <li style='margin: 0.25rem 0;'>Prévoyez de l'eau, de la crème solaire et un chapeau.</li>
                    <li style='margin: 0.25rem 0;'>Respectez la faune marine : observez sans toucher.</li>
                    <li style='margin: 0.25rem 0;'>Arrivez 10 minutes en avance pour un départ serein.</li>
                </ul>
            </div>

            <div style='text-align: center; color: #64748b; font-size: 0.85rem; margin-top: 2rem;'>
                <p>Merci de choisir EcoMarine pour votre aventure écologique 🌿</p>
                <p style='margin: 0;'>© EcoMarine — Île de Kuriat, Tunisie</p>
            </div>
        </div>
        ";
    }

    private function assertMailerEnabled(): void
    {
        $dsn = trim($this->getMailerDsn());
        $hasFileBasedConfig = trim($this->getMailerUser()) !== '' && trim($this->getMailerPasswordFile()) !== '';

        if (($dsn === '' && !$hasFileBasedConfig) || str_starts_with($dsn, 'null://')) {
            throw new \RuntimeException('MAILER_DSN is not configured (null transport).');
        }
    }

    private function getMailerDsn(): string
    {
        $dsn = getenv('MAILER_DSN');
        if ($dsn !== false) {
            return $dsn;
        }

        return (string) ($_ENV['MAILER_DSN'] ?? $_SERVER['MAILER_DSN'] ?? '');
    }

    private function getMailerUser(): string
    {
        $user = getenv('MAILER_USER');
        if ($user !== false) {
            return $user;
        }

        return (string) ($_ENV['MAILER_USER'] ?? $_SERVER['MAILER_USER'] ?? '');
    }

    private function getMailerPasswordFile(): string
    {
        $file = getenv('MAILER_PASSWORD_FILE');
        if ($file !== false) {
            return $file;
        }

        return (string) ($_ENV['MAILER_PASSWORD_FILE'] ?? $_SERVER['MAILER_PASSWORD_FILE'] ?? '');
    }

    private function getFromEmail(): string
    {
        $from = getenv('MAIL_FROM');
        if ($from !== false && trim($from) !== '') {
            return trim($from);
        }

        $from = (string) ($_ENV['MAIL_FROM'] ?? $_SERVER['MAIL_FROM'] ?? '');
        if (trim($from) !== '') {
            return trim($from);
        }

        return self::DEFAULT_FROM_EMAIL;
    }
}
