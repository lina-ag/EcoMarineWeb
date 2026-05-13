<?php

namespace App\Service;

class ZoneRisqueAI
{
    /**
     * @var array<int, array<int, float|int>>
     */
    private array $samples = [];

    /**
     * @var array<int, string>
     */
    private array $labels = [];

    public function __construct()
    {
        // [temperature, activite_humaine(0-4), pollution(0-4), biodiversite(0-4)]
        $this->samples = [
            [18, 0, 0, 4],
            [20, 1, 0, 4],
            [22, 1, 1, 3],
            [24, 2, 1, 3],
            [26, 2, 2, 2],
            [27, 3, 2, 2],
            [28, 3, 3, 1],
            [30, 4, 3, 1],
            [32, 4, 4, 0],
            [34, 4, 4, 0],
        ];

        $this->labels = [
            'Faible', 'Faible', 'Faible',
            'Moyen',  'Moyen',  'Moyen',
            'Élevé',  'Élevé',
            'Critique', 'Critique',
        ];
    }

    public function predireRisque(
        float $temperature,
        int   $activiteHumaine,
        int   $pollution,
        int   $biodiversite
    ): array {
        $niveau = $this->predireNiveau([
            $temperature,
            $activiteHumaine,
            $pollution,
            $biodiversite,
        ]);

        $scores = [
            'Faible'   => rand(10, 30),
            'Moyen'    => rand(35, 60),
            'Élevé'    => rand(65, 85),
            'Critique' => rand(88, 100),
        ];

        $risques = [
            'Faible'   => ['Fréquentation touristique légère', 'Variations saisonnières normales'],
            'Moyen'    => ['Activité de pêche non contrôlée', 'Légère dégradation du corail', 'Présence de déchets marins'],
            'Élevé'    => ['Braconnage intensifié', 'Température critique pour la faune', 'Pollution chimique détectée'],
            'Critique' => ["Destruction massive de l'écosystème", 'Présence de marée noire', 'Extinction locale imminente'],
        ];

        $recommandations = [
            'Faible'   => ['Maintenir la surveillance mensuelle', 'Sensibiliser les visiteurs', 'Continuer les relevés réguliers'],
            'Moyen'    => ['Augmenter la fréquence des patrouilles', "Limiter l'accès aux zones sensibles", 'Contrôler les activités de pêche'],
            'Élevé'    => ['Intervention immédiate requise', "Restreindre l'accès humain", 'Alerter les autorités environnementales'],
            'Critique' => ['Fermeture de zone recommandée', "Déploiement d'équipe d'urgence", 'Rapport officiel obligatoire'],
        ];

        return [
            'niveau_risque'   => $niveau,
            'score'           => $scores[$niveau],
            'urgence'         => in_array($niveau, ['Élevé', 'Critique']),
            'risques'         => $risques[$niveau],
            'recommandations' => $recommandations[$niveau],
        ];
    }

    /**
     * @param array<int, float|int> $point
     */
    private function predireNiveau(array $point): string
    {
        $meilleureDistance = null;
        $meilleurLabel = 'Moyen';

        foreach ($this->samples as $index => $sample) {
            $distance = $this->distanceEuclidienne($point, $sample);

            if ($meilleureDistance === null || $distance < $meilleureDistance) {
                $meilleureDistance = $distance;
                $meilleurLabel = $this->labels[$index];
            }
        }

        return $meilleurLabel;
    }

    /**
     * @param array<int, float|int> $a
     * @param array<int, float|int> $b
     */
    private function distanceEuclidienne(array $a, array $b): float
    {
        $somme = 0.0;

        foreach ($a as $index => $valeur) {
            $difference = (float) $valeur - (float) $b[$index];
            $somme += $difference * $difference;
        }

        return sqrt($somme);
    }
}