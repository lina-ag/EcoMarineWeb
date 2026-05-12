<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiWasteAnalyzerService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $openAiApiKey,
        private string $openAiModel
    ) {
    }

    public function analyzeImage(
        string $absoluteImagePath,
        ?string $declaredType = null,
        ?string $description = null,
        ?string $zone = null,
        ?float $quantite = null,
        ?string $statut = null
    ): array {
        $fallback = $this->buildLocalFallback(
            $declaredType,
            $description,
            $absoluteImagePath,
            $zone,
            $quantite,
            $statut,
            'Fallback local active.'
        );

        if (!is_file($absoluteImagePath)) {
            return $fallback;
        }

        try {
            $base64 = base64_encode(file_get_contents($absoluteImagePath));
            $mime = mime_content_type($absoluteImagePath) ?: 'image/jpeg';

            $payload = [
                'model' => $this->openAiModel,
                'input' => [[
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => 'Analyse cette image de dechet sur plage. Reponds uniquement en JSON valide avec les cles: type_suggestion, confidence, summary, recommended_action.'
                        ],
                        [
                            'type' => 'input_image',
                            'image_url' => "data:$mime;base64,$base64"
                        ]
                    ]
                ]]
            ];

            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/responses', [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->openAiApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                'timeout' => 60,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 400) {
                return $this->buildLocalFallback(
                    $declaredType,
                    $description,
                    $absoluteImagePath,
                    $zone,
                    $quantite,
                    $statut,
                    'Fallback local active apres erreur API OpenAI (HTTP '.$statusCode.').'
                );
            }

            $data = $response->toArray(false);
            $text = $this->extractTextFromResponsesApi($data);
            $decoded = json_decode($text, true);

            if (!is_array($decoded)) {
                return $this->buildLocalFallback(
                    $declaredType,
                    $description,
                    $absoluteImagePath,
                    $zone,
                    $quantite,
                    $statut,
                    'Fallback local active car la reponse IA n etait pas un JSON exploitable.'
                );
            }

            $typeSuggestion = $decoded['type_suggestion'] ?? 'autre';
            $confidence = (float) ($decoded['confidence'] ?? 0);
            $summary = $decoded['summary'] ?? null;
            $recommendedAction = $decoded['recommended_action'] ?? null;

            if (!$summary || $confidence <= 0) {
                return $this->buildLocalFallback(
                    $declaredType,
                    $description,
                    $absoluteImagePath,
                    $zone,
                    $quantite,
                    $statut,
                    'Fallback local active car la reponse IA etait incomplete.'
                );
            }

            return [
                'type_suggestion' => $typeSuggestion,
                'confidence' => $confidence,
                'summary' => $summary,
                'recommended_action' => $recommendedAction ?: 'Verification terrain recommandee.',
            ];
        } catch (\Throwable $e) {
            return $this->buildLocalFallback(
                $declaredType,
                $description,
                $absoluteImagePath,
                $zone,
                $quantite,
                $statut,
                'Fallback local active apres exception API OpenAI.'
            );
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

    private function buildLocalFallback(
        ?string $declaredType,
        ?string $description,
        string $absoluteImagePath,
        ?string $zone,
        ?float $quantite,
        ?string $statut,
        string $reason
    ): array {
        $text = mb_strtolower(trim(
            ($declaredType ?? '').' '.
            ($description ?? '').' '.
            basename($absoluteImagePath).' '.
            ($zone ?? '').' '.
            ($statut ?? '')
        ));

        $typeScores = [
            'plastique' => 0,
            'metal' => 0,
            'verre' => 0,
            'papier' => 0,
            'organique' => 0,
            'autre' => 0,
        ];

        $rules = [
            'plastique' => [
                'plastique' => 5, 'bouteille' => 4, 'bouteilles' => 4, 'sac' => 3, 'sachet' => 3,
                'pet' => 4, 'gobelet' => 3, 'emballage' => 3, 'filet' => 2
            ],
            'metal' => [
                'metal' => 5, 'canette' => 4, 'fer' => 4, 'aluminium' => 4, 'boite' => 2,
                'barre' => 2, 'capsule' => 2
            ],
            'verre' => [
                'verre' => 5, 'bocal' => 4, 'vitre' => 4, 'bouteille en verre' => 5, 'tesson' => 4
            ],
            'papier' => [
                'papier' => 5, 'carton' => 4, 'journal' => 3, 'magazine' => 3, 'boite carton' => 4
            ],
            'organique' => [
                'organique' => 5, 'algue' => 4, 'feuille' => 3, 'restes' => 4, 'reste' => 4,
                'dechet vert' => 4, 'aliment' => 3
            ],
        ];

        foreach ($rules as $type => $keywords) {
            foreach ($keywords as $keyword => $weight) {
                if ($keyword !== '' && str_contains($text, $keyword)) {
                    $typeScores[$type] += $weight;
                }
            }
        }

        $declaredTypeNormalized = $this->normalizeType($declaredType);
        if ($declaredTypeNormalized && isset($typeScores[$declaredTypeNormalized])) {
            $typeScores[$declaredTypeNormalized] += 6;
        }

        arsort($typeScores);
        $bestType = array_key_first($typeScores);
        $bestScore = $typeScores[$bestType] ?? 0;
        $secondScore = array_values($typeScores)[1] ?? 0;

        if ($bestScore <= 0) {
            $bestType = $declaredTypeNormalized ?: 'autre';
            $bestScore = $declaredTypeNormalized ? 5 : 1;
        }

        $confidence = 0.45;

        if ($bestScore >= 10) {
            $confidence = 0.88;
        } elseif ($bestScore >= 7) {
            $confidence = 0.78;
        } elseif ($bestScore >= 5) {
            $confidence = 0.70;
        } elseif ($bestScore >= 3) {
            $confidence = 0.62;
        } else {
            $confidence = 0.52;
        }

        if ($bestScore - $secondScore <= 1) {
            $confidence -= 0.10;
        }

        if ($declaredTypeNormalized && $declaredTypeNormalized === $bestType) {
            $confidence += 0.06;
        }

        $confidence = max(0.35, min(0.95, round($confidence, 2)));

        $riskLevel = 'modere';
        if (($quantite ?? 0) >= 20) {
            $riskLevel = 'eleve';
        } elseif (($quantite ?? 0) >= 8) {
            $riskLevel = 'soutenu';
        }

        $zoneText = $zone ?: 'zone non precisee';
        $statutText = $statut ?: 'statut non precise';
        $qtyText = $quantite !== null ? rtrim(rtrim(number_format($quantite, 2, '.', ''), '0'), '.') : 'non precisee';

        $recommendedAction = match ($bestType) {
            'plastique' => 'Prioriser le ramassage, separer les plastiques et orienter vers le tri/recyclage.',
            'metal' => 'Collecter separement et orienter vers la filiere metal.',
            'verre' => 'Manipuler avec precaution, securiser la zone et separer le verre.',
            'papier' => 'Maintenir au sec puis orienter vers la filiere papier/carton.',
            'organique' => 'Collecter rapidement et orienter vers compostage ou traitement organique.',
            default => 'Verifier manuellement puis integrer au circuit de traitement approprie.',
        };

        if (($quantite ?? 0) >= 10) {
            $recommendedAction .= ' Intervention terrain recommandee a court terme.';
        }

        if ($statut && mb_strtolower($statut) === 'traite') {
            $recommendedAction .= ' Element deja marque comme traite, verification de cloture conseillee.';
        }

        $summary = sprintf(
            'Classification locale amelioree : type probable "%s" avec confiance %.2f. Zone : %s. Quantite estimee : %s. Statut : %s. Niveau d attention : %s. %s',
            $bestType,
            $confidence,
            $zoneText,
            $qtyText,
            $statutText,
            $riskLevel,
            $reason
        );

        return [
            'type_suggestion' => $bestType,
            'confidence' => $confidence,
            'summary' => $summary,
            'recommended_action' => $recommendedAction,
        ];
    }

    private function normalizeType(?string $type): ?string
    {
        $type = mb_strtolower(trim((string) $type));

        return match ($type) {
            'plastique' => 'plastique',
            'metal', 'métal' => 'metal',
            'verre' => 'verre',
            'papier' => 'papier',
            'organique' => 'organique',
            'autre' => 'autre',
            default => null,
        };
    }
}