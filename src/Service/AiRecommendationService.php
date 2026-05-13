<?php

namespace App\Service;

use App\Entity\ActionNettoyage;
use App\Entity\Utilisateur;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class AiRecommendationService
{
    private HttpClientInterface $client;
    private ?string $openAiApiKey;

    public function __construct(HttpClientInterface $client, string $openAiApiKey = null)
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
                        ['role' => 'system', 'content' => 'Tu es un assistant qui recommande des actions de nettoyage utiles pour un utilisateur connecté.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 400,
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
                'date' => $action->getDateAction()?->format('Y-m-d') ?? 'Non définie',
                'participants' => $action->getNombreVolontaires(),
                'limite' => $action->getLimiteBenevoles(),
            ];
        }

        return sprintf(
            "Voici les actions de nettoyage disponibles pour l'utilisateur %s %s. Exclue les actions complètes et les actions auxquelles l'utilisateur est déjà inscrit. Analyse ces actions et choisis les 3 meilleures. Retourne uniquement un objet JSON valide avec la clé 'recommendations' contenant une liste d'objets {id_action, score, raison}.\n\nActions disponibles : %s",
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
        $actionsById = [];
        foreach ($actions as $action) {
            $actionsById[$action->getIdAction()] = $action;
        }

        foreach ($data['recommendations'] as $recommendation) {
            if (!isset($recommendation['id_action'], $recommendation['score'], $recommendation['raison'])) {
                continue;
            }

            $id = (int) $recommendation['id_action'];
            if (!isset($actionsById[$id])) {
                continue;
            }

            $recommendations[] = [
                'action' => $actionsById[$id],
                'id_action' => $id,
                'score' => (float) $recommendation['score'],
                'raison' => (string) $recommendation['raison'],
            ];
        }

        return $recommendations;
    }

    /**
     * @param ActionNettoyage[] $actions
     * @return array<int, array{action: ActionNettoyage, id_action: int, score: float, raison: string}>
     */
    private function fallbackRecommendations(array $actions): array
    {
        usort(
            $actions,
            static fn(ActionNettoyage $a, ActionNettoyage $b): int => ($a->getDateAction()?->getTimestamp() ?? 0) <=> ($b->getDateAction()?->getTimestamp() ?? 0)
        );

        $recommendations = [];
        foreach (array_slice($actions, 0, 3) as $action) {
            $recommendations[] = [
                'action' => $action,
                'id_action' => $action->getIdAction(),
                'score' => 0.5,
                'raison' => 'Action proche dans le temps et disponible, recommandée par défaut.',
            ];
        }

        return $recommendations;
    }
}
