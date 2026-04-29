<?php

namespace App\Service;

use App\Entity\PredictionEchouage;
use App\Repository\FauneMarineRepository;
use App\Repository\ObservationRepository;
use Psr\Log\LoggerInterface;

class PredictionService
{
    private WeatherServices $weatherServices;
    private FauneMarineRepository $fauneMarineRepository;
    private ObservationRepository $observationRepository;
    private LoggerInterface $logger;

    public function __construct(
        WeatherService $weatherService,
        FauneMarineRepository $fauneMarineRepository,
        ObservationRepository $observationRepository,
        LoggerInterface $logger
    ) {
        $this->weatherService = $weatherService;
        $this->fauneMarineRepository = $fauneMarineRepository;
        $this->observationRepository = $observationRepository;
        $this->logger = $logger;
    }

    /**
     * Calcule le risque d'échouage basé sur les données météorologiques et environnementales
     */
    public function calculateRiskLevel(float $lat, float $lon, \DateTimeInterface $date, string $zone): array
    {
        $risk = 1; // Niveau de base
        $factors = [];
        $recommendations = [];

        // Récupération des données météorologiques
        $weatherData = $this->weatherServices->getCurrentWeather($lat, $lon);

        if ($weatherData) {
            // Analyse de la température de l'eau (facteur important pour les mammifères marins)
            if (isset($weatherData['temperature'])) {
                $temp = $weatherData['temperature'];
                if ($temp < 10) {
                    $risk += 2;
                    $factors[] = "Température de l'eau très basse ({$temp}°C) - risque élevé d'échouage";
                    $recommendations[] = "Surveiller particulièrement les zones côtières";
                } elseif ($temp < 15) {
                    $risk += 1;
                    $factors[] = "Température de l'eau basse ({$temp}°C) - risque modéré";
                } else {
                    $factors[] = "Température de l'eau normale ({$temp}°C)";
                }
            }

            // Analyse des conditions météorologiques
            if (isset($weatherData['main'])) {
                $weatherCategory = $this->weatherServices->mapWeatherToCategory($weatherData['main'], $weatherData['description']);

                switch ($weatherCategory) {
                    case 'Tempête':
                        $risk += 3;
                        $factors[] = "Conditions météorologiques dangereuses (tempête)";
                        $recommendations[] = "Éviter les sorties en mer et renforcer la surveillance côtière";
                        break;
                    case 'Pluvieux':
                        $risk += 1;
                        $factors[] = "Conditions pluvieuses - visibilité réduite";
                        $recommendations[] = "Augmenter la vigilance lors des patrouilles";
                        break;
                    case 'Venteux':
                        $risk += 2;
                        $factors[] = "Vent fort - vagues importantes possibles";
                        $recommendations[] = "Surveiller les courants et les vagues";
                        break;
                    case 'Brumeux':
                        $risk += 1;
                        $factors[] = "Conditions brumeuses - visibilité réduite";
                        $recommendations[] = "Utiliser des équipements de navigation appropriés";
                        break;
                }
            }

            // Analyse de la vitesse du vent
            if (isset($weatherData['wind_speed']) && $weatherData['wind_speed'] > 20) {
                $risk += 1;
                $factors[] = "Vent fort ({$weatherData['wind_speed']} m/s) - peut affecter les courants";
            }
        } else {
            $factors[] = "Données météorologiques non disponibles";
            $recommendations[] = "Vérifier manuellement les conditions météorologiques";
        }

        // Analyse saisonnière
        $month = (int) $date->format('m');
        if (in_array($month, [12, 1, 2])) { // Hiver
            $risk += 1;
            $factors[] = "Saison hivernale - risque naturellement plus élevé";
            $recommendations[] = "Renforcer les patrouilles pendant la saison froide";
        } elseif (in_array($month, [6, 7, 8])) { // Été
            $risk -= 0.5; // Légère réduction du risque
            $factors[] = "Saison estivale - conditions généralement plus favorables";
        }

        // Analyse des observations récentes dans la zone
        $recentObservations = $this->getRecentObservationsInZone($zone, $date);
        if (count($recentObservations) > 0) {
            $risk += 0.5;
            $factors[] = count($recentObservations) . " observation(s) récente(s) dans cette zone";
            $recommendations[] = "Consulter les rapports d'observation récents";
        }

        // Normalisation du risque entre 1 et 5
        $risk = max(1, min(5, round($risk)));

        // Détermination du niveau de risque textuel
        $riskLevels = [
            1 => 'Faible',
            2 => 'Faible',
            3 => 'Moyen',
            4 => 'Élevé',
            5 => 'Élevé'
        ];

        return [
            'riskLevel' => $riskLevels[$risk] ?? 'Moyen',
            'riskLevelNumeric' => (int) $risk,
            'factors' => [
                'observations' => count($recentObservations) > 0 ? 'Élevé' : 'Faible',
                'seasonality' => in_array($month, [12, 1, 2]) ? 'Élevé' : 'Faible',
                'weather' => $weatherData ? 'Moyen' : 'Faible',
                'humanActivity' => 'Faible'
            ],
            'recommendations' => $recommendations,
            'weather' => $weatherData ? [
                'temperature' => $weatherData['temperature'] ?? null,
                'windSpeed' => $weatherData['wind_speed'] ?? null,
                'humidity' => $weatherData['humidity'] ?? null,
                'description' => sprintf(
                    '%s, %s - %s',
                    $weatherData['country'] ?? 'Pays inconnu',
                    $weatherData['location'] ?? 'Lieu inconnu',
                    $weatherData['description'] ?? 'Non disponible'
                ),
                'category' => $this->weatherServices->mapWeatherToCategory(
                    $weatherData['main'] ?? null,
                    $weatherData['description'] ?? null
                )
            ] : null,
            'zone' => $zone,
            'date' => $date->format('Y-m-d H:i:s'),
            'latitude' => $lat,
            'longitude' => $lon
        ];
    }

    /**
     * Récupère les observations récentes dans une zone donnée
     */
    private function getRecentObservationsInZone(string $zone, \DateTimeInterface $date): array
    {
        // Calcul de la date il y a 30 jours
        $thirtyDaysAgo = \DateTimeImmutable::createFromInterface($date)->modify('-30 days');

        // Ici nous devrions faire une requête pour trouver les observations récentes
        // Pour l'instant, on retourne un tableau vide (à implémenter selon la logique métier)
        return [];
    }

    /**
     * Génère une prédiction complète avec toutes les données
     */
    public function generatePrediction(float $lat, float $lon, \DateTimeInterface $date, string $zone): PredictionEchouage
    {
        $analysis = $this->calculateRiskLevel($lat, $lon, $date, $zone);

        $prediction = new PredictionEchouage();
        $prediction->setDatePrediction($date);
        $prediction->setZone($zone);
        $prediction->setLatitude($lat);
        $prediction->setLongitude($lon);
        $prediction->setNiveauRisque($analysis['risk_level']);

        // Espèce concernée basée sur les observations récentes (logique simplifiée)
        $species = $this->getMostObservedSpeciesInZone($zone);
        if ($species) {
            $prediction->setEspeceConcernee($species);
        }

        // Température de l'eau depuis les données météo
        if (isset($analysis['weather_data']['temperature'])) {
            $prediction->setTemperatureEau($analysis['weather_data']['temperature']);
        }

        // Conditions météo
        if (isset($analysis['weather_data']['main'])) {
            $weatherCategory = $this->weatherServices->mapWeatherToCategory(
                $analysis['weather_data']['main'],
                $analysis['weather_data']['description']
            );
            $prediction->setConditionsMeteo($weatherCategory);
        }

        // Recommandations
        $recommendations = implode("\n• ", $analysis['recommendations']);
        if (!empty($recommendations)) {
            $prediction->setRecommandations("• " . $recommendations);
        }

        return $prediction;
    }

    /**
     * Récupère l'espèce la plus observée dans une zone (logique simplifiée)
     */
    private function getMostObservedSpeciesInZone(string $zone): ?string
    {
        // Logique simplifiée - à implémenter selon les besoins réels
        return null;
    }
}