<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GroqReportService
{
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const MODEL = 'llama-3.3-70b-versatile';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $groqApiKey,
    ) {}

    /**
     * @param array<int, array{nom: string, date: string, capacite: int, booked: int, reservations: int}> $activites
     * @param array<int, array{nom: string, activite: string, date: string, nombrePersonnes: int, statut: string}> $reservations
     */
    public function generateReport(array $activites, array $reservations): string
    {
        if ($activites === [] && $reservations === []) {
            return 'Aucune donnée disponible pour générer le rapport.';
        }

        $activitesSummary = '';
        foreach ($activites as $a) {
            $taux = $a['capacite'] > 0 ? round($a['booked'] / $a['capacite'] * 100) : 0;
            $activitesSummary .= sprintf(
                "- %s (date: %s, capacité: %d, réservé: %d, taux de remplissage: %d%%, nombre de réservations: %d)\n",
                $a['nom'], $a['date'], $a['capacite'], $a['booked'], $taux, $a['reservations']
            );
        }

        $reservationsSummary = '';
        $totalPersonnes = 0;
        $statusCounts = ['confirmed' => 0, 'pending' => 0, 'cancelled' => 0];
        foreach ($reservations as $r) {
            $totalPersonnes += $r['nombrePersonnes'];
            $statusCounts[$r['statut']] = ($statusCounts[$r['statut']] ?? 0) + 1;
        }

        $reservationsSummary = sprintf(
            "Total réservations: %d | Personnes: %d | Confirmées: %d | En attente: %d | Annulées: %d",
            count($reservations),
            $totalPersonnes,
            $statusCounts['confirmed'],
            $statusCounts['pending'],
            $statusCounts['cancelled']
        );

        $prompt = <<<PROMPT
Tu es un analyste spécialisé en écotourisme marin pour EcoMarine, une plateforme dédiée à la protection de l'île de Kuriat (Monastir, Tunisie).

Voici les données actuelles sur les activités écologiques et réservations :

## Activités écologiques
{$activitesSummary}

## Résumé des réservations
{$reservationsSummary}

Génère un rapport d'analyse structuré en français avec les sections suivantes :
1. **Vue d'ensemble** — synthèse globale en 2-3 phrases
2. **Points forts** — activités les plus populaires / meilleures performances
3. **Points d'attention** — activités sous-remplies ou surréservées, tendances préoccupantes
4. **Recommandations** — 3 à 5 actions concrètes pour améliorer la gestion des réservations et l'impact écologique
5. **Indicateur de santé** — donne une note globale /10 avec justification courte

Sois précis, professionnel et orienté vers la durabilité écologique. Utilise des emojis pour rendre le rapport lisible.
PROMPT;

        try {
            $response = $this->httpClient->request('POST', self::GROQ_API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => self::MODEL,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un expert en ecotourisme marin. Tu generes des rapports professionnels en francais.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 1024,
                ],
            ]);

            $data = $response->toArray();

            return $data['choices'][0]['message']['content'] ?? 'Aucune réponse générée.';
        } catch (HttpExceptionInterface $exception) {
            $response = $exception->getResponse();
            $details = trim($response->getContent(false));

            throw new \RuntimeException($details !== '' ? $details : 'Erreur HTTP lors de l appel a Groq.', 0, $exception);
        } catch (\Throwable $exception) {
            throw new \RuntimeException($exception->getMessage(), 0, $exception);
        }
    }
}
