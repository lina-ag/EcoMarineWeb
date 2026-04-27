<?php

namespace App\CleaningBundle\Service;

use App\CleaningBundle\Entity\ActionNettoyage;
use App\Entity\Utilisateur;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class AiRecommendationService
{
    private HttpClientInterface $client;
    private ?string $openAiApiKey;

    public function __construct(HttpClientInterface $client, ?string $openAiApiKey = null)
    {
        $this->client = $client;
        $this->openAiApiKey = $openAiApiKey;
    }

    /**
     * @param ActionNettoyage[] $actions
     * @return array<int, array{action: ActionNettoyage, id_action: int, score: float, raison: string}>
     */
    public function recommendActions(array $actions, ?Utilisateur $utilisateur = null): array
    {
        if (!$utilisateur || count($actions) === 0) {
            return [];
        }

        $recommendations = $this->callOpenAi($actions, $utilisateur);

        if (count($recommendations) > 0) {
            return $recommendations;
        }

        return $this->fallbackRecommendations($actions);
    }

    /**
     * @param ActionNettoyage[] $actions
     * @return array<int, array{action: ActionNettoyage, id_action: int, score: float, raison: string}>
     */
    private function callOpenAi(array $actions, Utilisateur $utilisateur): array
    {
        if (!$this->openAiApiKey) {
            return [];
        }

        $prompt = $this->buildPrompt($actions, $utilisateur);

        try {
            $response = $this->client->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->openAiApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => "Tu es un assistant qui recommande des actions de nettoyage utiles pour un utilisateur connecte. "
                                . "Tu dois fournir des raisons courtes, claires et differentes pour chaque recommandation.",
                        ],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.5,
                    'max_tokens' => 500,
                ],
            ]);

            $content = $response->getContent(false);
            $payload = json_decode($content, true);
            $message = $payload['choices'][0]['message']['content'] ?? '';

            return $this->parseOpenAiResponse($message, $actions);
        } catch (ClientExceptionInterface | TransportExceptionInterface $exception) {
            return [];
        }
    }

    /**
     * @param ActionNettoyage[] $actions
     */
    private function buildPrompt(array $actions, Utilisateur $utilisateur): string
    {
        $actionList = [];

        foreach ($actions as $action) {
            $actionList[] = [
                'id_action' => $action->getIdAction(),
                'lieu' => $action->getLieu(),
                'date' => $action->getDateAction()?->format('Y-m-d') ?? 'Non definie',
                'participants' => $action->getNombreVolontaires(),
                'limite' => $action->getLimiteBenevoles(),
                'places_restantes' => $this->getRemainingPlaces($action),
                'jours_avant_action' => $this->getDaysUntilAction($action),
            ];
        }

        return sprintf(
            "Voici les actions de nettoyage disponibles pour l'utilisateur %s %s. "
            . "Exclus les actions completes et celles deja rejointes. "
            . "Choisis exactement les 3 meilleures recommandations si possible. "
            . "Pour chaque recommandation, donne une raison courte et differente des autres. "
            . "Varie les raisons entre date proche, urgence, places restantes, impact potentiel et opportunite de participation. "
            . "Retourne uniquement un objet JSON valide avec la cle 'recommendations' contenant une liste d'objets {id_action, score, raison}. "
            . "Le score doit etre un nombre entre 0 et 1.\n\nActions disponibles : %s",
            $utilisateur->getNom() ?? 'Utilisateur',
            $utilisateur->getPrenom() ?? '',
            json_encode($actionList, JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * @param ActionNettoyage[] $actions
     * @return array<int, array{action: ActionNettoyage, id_action: int, score: float, raison: string}>
     */
    private function parseOpenAiResponse(string $message, array $actions): array
    {
        $json = trim($message);
        $json = preg_replace('/^```json\s*/', '', $json);
        $json = preg_replace('/```$/', '', $json);

        if (preg_match('/(\{.*\})/s', $json, $matches)) {
            $json = $matches[1];
        }

        $data = json_decode($json, true);

        if (!is_array($data) || !isset($data['recommendations']) || !is_array($data['recommendations'])) {
            return [];
        }

        $recommendations = [];
        $usedReasons = [];
        $actionsById = [];

        foreach ($actions as $action) {
            $actionsById[$action->getIdAction()] = $action;
        }

        foreach (array_slice($data['recommendations'], 0, 3) as $recommendation) {
            if (!isset($recommendation['id_action'], $recommendation['score'], $recommendation['raison'])) {
                continue;
            }

            $id = (int) $recommendation['id_action'];
            if (!isset($actionsById[$id])) {
                continue;
            }

            $raison = $this->normalizeReason((string) $recommendation['raison']);
            if ($raison === '' || in_array(mb_strtolower($raison), $usedReasons, true)) {
                $raison = $this->buildFallbackReason($actionsById[$id], count($recommendations));
            }

            $recommendations[] = [
                'action' => $actionsById[$id],
                'id_action' => $id,
                'score' => max(0.0, min(1.0, (float) $recommendation['score'])),
                'raison' => $raison,
            ];

            $usedReasons[] = mb_strtolower($raison);
        }

        return $recommendations;
    }

    /**
     * @param ActionNettoyage[] $actions
     * @return array<int, array{action: ActionNettoyage, id_action: int, score: float, raison: string}>
     */
    private function fallbackRecommendations(array $actions): array
    {
        usort($actions, fn(ActionNettoyage $a, ActionNettoyage $b): int => $this->compareActions($a, $b));

        $recommendations = [];

        foreach (array_slice($actions, 0, 3) as $index => $action) {
            $recommendations[] = [
                'action' => $action,
                'id_action' => $action->getIdAction(),
                'score' => max(0.5, 0.9 - ($index * 0.1)),
                'raison' => $this->buildFallbackReason($action, $index),
            ];
        }

        return $recommendations;
    }

    private function compareActions(ActionNettoyage $a, ActionNettoyage $b): int
    {
        $daysA = $this->getDaysUntilAction($a);
        $daysB = $this->getDaysUntilAction($b);

        if ($daysA !== $daysB) {
            return $daysA <=> $daysB;
        }

        return $this->getRemainingPlaces($a) <=> $this->getRemainingPlaces($b);
    }

    private function buildFallbackReason(ActionNettoyage $action, int $index): string
    {
        $daysUntilAction = $this->getDaysUntilAction($action);
        $remainingPlaces = $this->getRemainingPlaces($action);

        return match ($index) {
            0 => $daysUntilAction <= 2
                ? "Cette action arrive tres bientot, elle merite donc une priorite immediate."
                : "Cette action fait partie des plus proches dans le temps, ce qui facilite une participation rapide.",
            1 => $remainingPlaces <= 3
                ? "Il reste peu de places disponibles, votre inscription serait utile des maintenant."
                : "Cette action garde encore des places libres, avec une bonne opportunite pour vous engager facilement.",
            default => $remainingPlaces > 5
                ? "Cette action offre encore une bonne marge de places, ideale pour rejoindre l'effort sans pression immediate."
                : "Cette action complete bien les autres choix avec un bon equilibre entre disponibilite et proximite.",
        };
    }

    private function getDaysUntilAction(ActionNettoyage $action): int
    {
        $dateAction = $action->getDateAction();
        if (!$dateAction) {
            return PHP_INT_MAX;
        }

        $today = new \DateTimeImmutable('today');
        $actionDate = \DateTimeImmutable::createFromInterface($dateAction)->setTime(0, 0);

        return max(0, (int) $today->diff($actionDate)->format('%r%a'));
    }

    private function getRemainingPlaces(ActionNettoyage $action): int
    {
        return max(0, ($action->getLimiteBenevoles() ?? 0) - $action->getNombreVolontaires());
    }

    private function normalizeReason(string $reason): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $reason));
    }
}
