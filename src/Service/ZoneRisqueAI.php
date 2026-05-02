<?php

namespace App\Service;

use Phpml\Classification\KNearestNeighbors;


class ZoneRisqueAI
{
    private KNearestNeighbors $model;

    public function __construct()
    {
        // [temperature, activite_humaine(0-4), pollution(0-4), biodiversite(0-4)]
        $samples = [
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

        $labels = [
            'Faible', 'Faible', 'Faible',
            'Moyen',  'Moyen',  'Moyen',
            'Élevé',  'Élevé',
            'Critique', 'Critique',
        ];

        $this->model = new KNearestNeighbors(k: 3);
        $this->model->train($samples, $labels);
    }

    public function predireRisque(
        float $temperature,
        int   $activiteHumaine,
        int   $pollution,
        int   $biodiversite
    ): array {
        $prediction = $this->model->predict([
            [$temperature, $activiteHumaine, $pollution, $biodiversite]
        ]);

        $niveau = $prediction[0];

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
}