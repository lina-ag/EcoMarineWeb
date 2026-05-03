<?php

namespace App\Service;

use App\Entity\Dechet;

class DechetCrossModuleInsightService
{
    private array $zoneProfiles = [
        'kuriat nord' => [
            'zone_criticality' => 'moyenne',
            'biodiversity_context' => 'Zone proche d habitats marins sensibles et d oiseaux marins proteges.',
            'event_context' => 'Zone touristique avec pression saisonniere elevee en ete.',
        ],
        'kuriat sud' => [
            'zone_criticality' => 'moyenne',
            'biodiversity_context' => 'Zone sensible a la perturbation des milieux marins et littoraux.',
            'event_context' => 'Frequentation saisonniere et activites balneaires ponctuelles.',
        ],
        'monastir' => [
            'zone_criticality' => 'moyenne',
            'biodiversity_context' => 'Proximite avec zones littorales sensibles et biodiversite marine a surveiller.',
            'event_context' => 'Activite touristique et estivale importante sur la periode chaude.',
        ],
        'sousse' => [
            'zone_criticality' => 'elevee',
            'biodiversity_context' => 'Zone urbaine littorale soumise a forte pression humaine sur les ecosystemes.',
            'event_context' => 'Probabilite elevee de pics de dechets apres evenements ou affluence touristique.',
        ],
        'mahdia' => [
            'zone_criticality' => 'moyenne',
            'biodiversity_context' => 'Zone cotiere avec habitats sensibles et enjeu de preservation du littoral.',
            'event_context' => 'Activites estivales et frequentation touristique moderee a forte selon la saison.',
        ],
        'plage skanes' => [
            'zone_criticality' => 'elevee',
            'biodiversity_context' => 'Zone balneaire sensible avec risque de dispersion des dechets en mer.',
            'event_context' => 'Hausse probable des dechets en periode estivale ou evenementielle.',
        ],
    ];

    public function buildInsight(Dechet $dechet): array
    {
        $zone = mb_strtolower(trim((string) ($dechet->getZone() ?? '')));
        $profile = $this->zoneProfiles[$zone] ?? [
            'zone_criticality' => 'faible',
            'biodiversity_context' => 'Aucun signal ecologique critique detecte, surveillance standard recommandee.',
            'event_context' => 'Aucun contexte evenementiel fort detecte a partir des donnees locales.',
        ];

        $type = mb_strtolower((string) ($dechet->getAiTypeSuggestion() ?: $dechet->getType() ?: 'autre'));
        $quantite = (float) ($dechet->getQuantite() ?? 0);
        $wind = (float) ($dechet->getWeatherWind() ?? 0);
        $confidence = (float) ($dechet->getAiConfidence() ?? 0);
        $summary = (string) ($dechet->getAiSummary() ?? '');
        $month = null;

        if ($dechet->getDateSignalement()) {
            try {
                $month = (int) $dechet->getDateSignalement()->format('n');
            } catch (\Throwable $e) {
                $month = null;
            }
        }

        $ecologicalRisk = 'faible';
        $eventProbability = 'faible';
        $zoneCriticality = $profile['zone_criticality'];

        if (in_array($type, ['plastique', 'verre'], true)) {
            $ecologicalRisk = 'moyen';
        }

        if ($type === 'plastique' && str_contains(mb_strtolower($profile['biodiversity_context']), 'sensible')) {
            $ecologicalRisk = 'eleve';
        }

        if ($quantite >= 12) {
            $ecologicalRisk = $this->upgradeLevel($ecologicalRisk);
            $zoneCriticality = $this->upgradeLevel($zoneCriticality);
        }

        if ($wind >= 6) {
            $ecologicalRisk = $this->upgradeLevel($ecologicalRisk);
        }

        if ($month !== null && in_array($month, [6, 7, 8, 9], true)) {
            $eventProbability = 'moyen';
        }

        if (
            in_array($zone, ['sousse', 'monastir', 'plage skanes', 'kuriat nord', 'kuriat sud'], true)
            && $month !== null
            && in_array($month, [6, 7, 8, 9], true)
        ) {
            $eventProbability = 'eleve';
        }

        if ($quantite >= 15) {
            $eventProbability = $this->upgradeLevel($eventProbability);
        }

        $recommendation = $this->buildRecommendation(
            $type,
            $ecologicalRisk,
            $zoneCriticality,
            $eventProbability
        );

        $aiSource = 'fallback';
        if ($confidence >= 0.8 && !str_contains(mb_strtolower($summary), 'fallback local')) {
            $aiSource = 'openai';
        } elseif ($confidence > 0) {
            $aiSource = 'hybrid';
        }

        return [
            'location' => $dechet->getZone() ?: 'Zone non precisee',
            'biodiversity_context' => $profile['biodiversity_context'],
            'event_context' => $profile['event_context'],
            'waste_type' => $type,
            'ecological_risk' => $ecologicalRisk,
            'zone_criticality' => $zoneCriticality,
            'event_probability' => $eventProbability,
            'recommendation' => $recommendation,
            'ai_source' => $aiSource,
            'ai_confidence' => $confidence,
            'ai_summary' => $summary,
            'narrative' => sprintf(
                'Le dechet situe dans %s est interprete comme un cas %s avec un risque ecologique %s, une criticite de zone %s et une probabilite evenementielle %s.',
                $dechet->getZone() ?: 'une zone non precisee',
                $type,
                $ecologicalRisk,
                $zoneCriticality,
                $eventProbability
            ),
        ];
    }

    private function buildRecommendation(string $type, string $eco, string $zone, string $event): string
    {
        $parts = [];

        if ($type === 'plastique') {
            $parts[] = 'Ramassage prioritaire et tri plastique recommande';
        } elseif ($type === 'verre') {
            $parts[] = 'Securisation de la zone et collecte prudente du verre';
        } elseif ($type === 'metal') {
            $parts[] = 'Collecte separee pour recyclage metal';
        } elseif ($type === 'papier') {
            $parts[] = 'Ramassage rapide et orientation vers la filiere papier';
        } elseif ($type === 'organique') {
            $parts[] = 'Collecte et traitement organique ou compostage';
        } else {
            $parts[] = 'Verification terrain complementaire recommandee';
        }

        if (in_array($eco, ['eleve', 'critique'], true)) {
            $parts[] = 'Intervention rapide en raison du risque pour la biodiversite';
        }

        if (in_array($zone, ['elevee', 'critique'], true)) {
            $parts[] = 'Zone plage a surveiller de facon renforcee';
        }

        if (in_array($event, ['eleve', 'critique'], true)) {
            $parts[] = 'Prevoir nettoyage preventif ou post-evenement';
        }

        return implode(' — ', $parts).'.';
    }

    private function upgradeLevel(string $level): string
    {
        return match ($level) {
            'faible' => 'moyen',
            'moyen' => 'eleve',
            'elevee' => 'critique',
            'eleve' => 'critique',
            default => $level,
        };
    }
}