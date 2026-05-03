<?php

namespace App\Service;

use App\Entity\Dechet;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WasteOriginAnalyzerService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $openAiApiKey = '',
        private string $openAiModel = 'gpt-4.1-mini'
    ) {
    }

    public function analyze(Dechet $dechet): array
    {
        $fallback = $this->buildFallback($dechet, 'Analyse locale active.');

        try {
            if (!$this->openAiApiKey) {
                return $fallback;
            }

            $context = [
                'type' => $dechet->getAiTypeSuggestion() ?: $dechet->getType(),
                'description' => $dechet->getDescription(),
                'zone' => $dechet->getZone(),
                'quantity' => $dechet->getQuantite(),
                'weather_main' => $dechet->getWeatherMain(),
                'weather_wind' => $dechet->getWeatherWind(),
                'ai_summary' => $dechet->getAiSummary(),
                'recommended_action' => $dechet->getRecommendedAction(),
            ];

            $prompt = <<<PROMPT
Analyse ce signalement de déchets littoraux et réponds UNIQUEMENT en JSON valide.

Champs attendus :
{
  "detected_types": [
    {"label": "plastique", "percent": 65},
    {"label": "canettes", "percent": 20},
    {"label": "filets de peche", "percent": 15}
  ],
  "estimated_origins": [
    {"label": "tourisme", "percent": 70},
    {"label": "restaurants", "percent": 20},
    {"label": "peche", "percent": 10}
  ],
  "detected_brands": ["bouteilles", "emballages alimentaires"],
  "generated_insight": "La majorité des déchets provient des activités touristiques intensives sur cette zone."
}

Contexte :
PROMPT;

            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/responses', [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->openAiApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->openAiModel,
                    'input' => [[
                        'role' => 'user',
                        'content' => [[
                            'type' => 'input_text',
                            'text' => $prompt."\n".json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                        ]],
                    ]],
                ],
                'timeout' => 45,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode >= 400) {
                return $this->buildFallback($dechet, 'Fallback origine après erreur API OpenAI (HTTP '.$statusCode.').');
            }

            $data = $response->toArray(false);
            $text = $this->extractTextFromResponsesApi($data);
            $decoded = json_decode($text, true);

            if (!is_array($decoded)) {
                return $this->buildFallback($dechet, 'Fallback origine car la réponse IA n’était pas exploitable.');
            }

            return [
                'detected_types' => $this->normalizePercentList($decoded['detected_types'] ?? []),
                'estimated_origins' => $this->normalizePercentList($decoded['estimated_origins'] ?? []),
                'detected_brands' => is_array($decoded['detected_brands'] ?? null) ? $decoded['detected_brands'] : [],
                'generated_insight' => (string) ($decoded['generated_insight'] ?? 'Analyse générée par IA.'),
                'source' => 'openai',
            ];
        } catch (\Throwable $e) {
            return $this->buildFallback($dechet, 'Fallback origine après exception API OpenAI.');
        }
    }

    private function extractTextFromResponsesApi(array $data): string
    {
        if (!empty($data['output_text']) && is_string($data['output_text'])) {
            return $data['output_text'];
        }

        if (!empty($data['output']) && is_array($data['output'])) {
            foreach ($data['output'] as $item) {
                if (!empty($item['content']) && is_array($item['content'])) {
                    foreach ($item['content'] as $content) {
                        if (($content['type'] ?? null) === 'output_text' && !empty($content['text'])) {
                            return $content['text'];
                        }
                        if (!empty($content['text']) && is_string($content['text'])) {
                            return $content['text'];
                        }
                    }
                }
            }
        }

        return '{}';
    }

    private function buildFallback(Dechet $dechet, string $reason): array
    {
        $text = mb_strtolower(trim(
            ($dechet->getAiTypeSuggestion() ?: $dechet->getType() ?: '').' '.
            ($dechet->getDescription() ?: '').' '.
            ($dechet->getZone() ?: '').' '.
            ($dechet->getAiSummary() ?: '')
        ));

        $detectedTypes = [];
        $originScores = [
            'tourisme' => 0,
            'restaurants' => 0,
            'peche' => 0,
            'activite locale' => 0,
        ];
        $brands = [];

        if (str_contains($text, 'plastique') || str_contains($text, 'bouteille') || str_contains($text, 'sac')) {
            $detectedTypes[] = ['label' => 'plastique', 'percent' => 65];
            $originScores['tourisme'] += 5;
            $originScores['restaurants'] += 2;
            $brands[] = 'bouteilles';
        }

        if (str_contains($text, 'metal') || str_contains($text, 'canette') || str_contains($text, 'aluminium')) {
            $detectedTypes[] = ['label' => 'canettes', 'percent' => 20];
            $originScores['tourisme'] += 2;
            $originScores['restaurants'] += 2;
        }

        if (str_contains($text, 'filet') || str_contains($text, 'corde') || str_contains($text, 'peche')) {
            $detectedTypes[] = ['label' => 'filets de peche', 'percent' => 15];
            $originScores['peche'] += 5;
        }

        if (str_contains($text, 'emballage') || str_contains($text, 'food') || str_contains($text, 'resto')) {
            $originScores['restaurants'] += 4;
            $brands[] = 'emballages alimentaires';
        }

        if (empty($detectedTypes)) {
            $detectedTypes[] = [
                'label' => $dechet->getAiTypeSuggestion() ?: ($dechet->getType() ?: 'autre'),
                'percent' => 100
            ];
            $originScores['activite locale'] += 2;
        }

        if (($dechet->getQuantite() ?? 0) >= 10) {
            $originScores['tourisme'] += 1;
            $originScores['activite locale'] += 1;
        }

        $estimatedOrigins = $this->scoresToPercentList($originScores);

        $mainOrigin = $estimatedOrigins[0]['label'] ?? 'activité humaine';
        $zone = $dechet->getZone() ?: 'cette zone';

        $insight = 'La majorité des déchets semble provenir de '.$mainOrigin.' sur '.$zone.'. '.$reason;

        return [
            'detected_types' => $detectedTypes,
            'estimated_origins' => $estimatedOrigins,
            'detected_brands' => array_values(array_unique($brands)),
            'generated_insight' => $insight,
            'source' => 'fallback',
        ];
    }

    private function scoresToPercentList(array $scores): array
    {
        $scores = array_filter($scores, fn ($v) => $v > 0);

        if (empty($scores)) {
            return [
                ['label' => 'activite locale', 'percent' => 100]
            ];
        }

        $sum = array_sum($scores);
        $result = [];

        foreach ($scores as $label => $value) {
            $result[] = [
                'label' => $label,
                'percent' => (int) round(($value / $sum) * 100),
            ];
        }

        usort($result, fn ($a, $b) => $b['percent'] <=> $a['percent']);

        return $this->rebalancePercentages($result);
    }

    private function normalizePercentList(array $list): array
    {
        $normalized = [];

        foreach ($list as $item) {
            if (!is_array($item)) {
                continue;
            }

            $label = trim((string) ($item['label'] ?? ''));
            $percent = (int) ($item['percent'] ?? 0);

            if ($label === '') {
                continue;
            }

            $normalized[] = [
                'label' => $label,
                'percent' => max(0, min(100, $percent)),
            ];
        }

        if (empty($normalized)) {
            return [['label' => 'autre', 'percent' => 100]];
        }

        usort($normalized, fn ($a, $b) => $b['percent'] <=> $a['percent']);

        return $this->rebalancePercentages($normalized);
    }

    private function rebalancePercentages(array $items): array
    {
        $sum = array_sum(array_column($items, 'percent'));

        if ($sum === 100 || $sum === 0) {
            return $items;
        }

        $diff = 100 - $sum;
        $items[0]['percent'] += $diff;

        return $items;
    }
}