<?php

namespace App\Service;

class DechetPriorityService
{
    public function compute(
        float $quantite,
        ?string $weatherMain,
        float $windSpeed,
        ?string $aiType,
        float $aiConfidence,
        int $sameZoneCount
    ): array {
        $score = 0;

        if ($quantite >= 20) {
            $score += 35;
        } elseif ($quantite >= 10) {
            $score += 20;
        } elseif ($quantite >= 5) {
            $score += 10;
        }

        if ($windSpeed >= 10) {
            $score += 20;
        } elseif ($windSpeed >= 5) {
            $score += 10;
        }

        if (in_array($weatherMain, ['Rain', 'Thunderstorm', 'Wind'], true)) {
            $score += 10;
        }

        if (in_array($aiType, ['plastique', 'metal', 'verre'], true)) {
            $score += 15;
        }

        if ($aiConfidence >= 0.8) {
            $score += 10;
        }

        if ($sameZoneCount >= 5) {
            $score += 10;
        } elseif ($sameZoneCount >= 3) {
            $score += 5;
        }

        $score = min(100, $score);

        $label = match (true) {
            $score >= 75 => 'critique',
            $score >= 50 => 'elevee',
            $score >= 25 => 'moyenne',
            default => 'faible',
        };

        $recommendation = match ($label) {
            'critique' => 'Intervention immediate et nettoyage prioritaire',
            'elevee' => 'Planifier une intervention rapide',
            'moyenne' => 'Surveiller et traiter sous 48h',
            default => 'Suivi normal et regroupement avec autres actions',
        };

        return [
            'score' => $score,
            'label' => $label,
            'recommendation' => $recommendation,
        ];
    }
}