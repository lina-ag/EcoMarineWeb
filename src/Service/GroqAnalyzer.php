<?php
// src/Service/GroqAnalyzer.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GroqAnalyzer
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $groqApiKey
    ) {}

    public function analyserSurveillances(array $survzones): string
    {
        $donnees = [];
        foreach ($survzones as $s) {
            $donnees[] = sprintf(
                '- Date: %s | Zone: %s | Observation: %s',
                $s->getDateSurv()?->format('d/m/Y') ?? 'N/A',
                $s->getZone()?->getNomZone() ?? 'N/A',
                $s->getObservation() ?? 'Aucune'
            );
        }

        $prompt = "Tu es un expert en protection des zones marines. Voici les données de surveillance :\n\n"
            . implode("\n", $donnees)
            . "\n\nGénère un rapport professionnel en français qui inclut :\n"
            . "1. Un résumé global des surveillances\n"
            . "2. Les zones les plus surveillées\n"
            . "3. Les observations récurrentes ou préoccupantes\n"
            . "4. Des recommandations concrètes\n"
            . "Sois concis et structuré.";

        $response = $this->httpClient->request('POST',
            'https://api.groq.com/openai/v1/chat/completions',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'    => 'llama-3.3-70b-versatile',
                    'messages' => [
                        [
                            'role'    => 'system',
                            'content' => 'Tu es un expert en protection des zones marines. Tu génères des rapports professionnels en français.'
                        ],
                        [
                            'role'    => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 0.7,
                    'max_tokens'  => 1024,
                ]
            ]
        );

        $data = $response->toArray();

        return $data['choices'][0]['message']['content'] ?? 'Erreur lors de la génération du rapport.';
    }
}