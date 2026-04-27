<?php

namespace App\Controller;

use App\Entity\PredictionEchouage;
use App\Form\PredictionEchouageType;
use App\Repository\PredictionEchouageRepository;
use App\Service\WeatherService;
use App\Service\PredictionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/prediction/map')]
final class PredictionMapController extends AbstractController
{
    private WeatherService $weatherService;
    private PredictionService $predictionService;

    public function __construct(WeatherService $weatherService, PredictionService $predictionService)
    {
        $this->weatherService = $weatherService;
        $this->predictionService = $predictionService;
    }

    #[Route('', name: 'app_prediction_map', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('prediction_map/index.html.twig', [
            'controller_name' => 'PredictionMapController',
        ]);
    }

    #[Route('/weather/{lat}/{lon}', name: 'app_prediction_weather', methods: ['GET'])]
    public function getWeather(float $lat, float $lon): JsonResponse
    {
        try {
            $weatherData = $this->weatherService->getCurrentWeather($lat, $lon);

            if ($weatherData) {
                return new JsonResponse([
                    'success' => true,
                    'data' => $weatherData
                ]);
            }

            return new JsonResponse([
                'success' => false,
                'message' => 'Impossible de récupérer les données météorologiques'
            ], 404);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données météorologiques'
            ], 500);
        }
    }

    #[Route('/predict', name: 'app_prediction_calculate', methods: ['POST'])]
    public function calculatePrediction(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data || !isset($data['lat']) || !isset($data['lon']) || !isset($data['zone'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Données manquantes (latitude, longitude, zone)'
                ], 400);
            }

            $lat = (float) $data['lat'];
            $lon = (float) $data['lon'];
            $zone = $data['zone'];
            $date = isset($data['date']) ? new \DateTime($data['date']) : new \DateTime();

            // Calcul de la prédiction
            $analysis = $this->predictionService->calculateRiskLevel($lat, $lon, $date, $zone);

            return new JsonResponse([
                'success' => true,
                'prediction' => $analysis
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors du calcul de la prédiction: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/save', name: 'app_prediction_save', methods: ['POST'])]
    public function savePrediction(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data || !isset($data['latitude']) || !isset($data['longitude'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Données manquantes (latitude, longitude)'
                ], 400);
            }

            // Validations
            $zone = $data['zone'] ?? null;
            $validZones = ['Nord', 'Nord-Est', 'Est', 'Sud-Est', 'Sud', 'Sud-Ouest', 'Ouest', 'Nord-Ouest'];
            if (empty($zone) || !in_array($zone, $validZones)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Zone invalide ou manquante'
                ], 400);
            }

            $latitude = (float) $data['latitude'];
            $longitude = (float) $data['longitude'];

            // Vérifier les plages
            if ($latitude < -90 || $latitude > 90) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Latitude invalide (doit être entre -90 et 90)'
                ], 400);
            }

            if ($longitude < -180 || $longitude > 180) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Longitude invalide (doit être entre -180 et 180)'
                ], 400);
            }

            $prediction = new PredictionEchouage();
            $prediction->setLatitude($latitude);
            $prediction->setLongitude($longitude);
            $prediction->setZone($zone);
            
            $predictionDate = new \DateTime();
            if (isset($data['date_prediction']) && !empty($data['date_prediction'])) {
                try {
                    $predictionDate = new \DateTime($data['date_prediction']);
                } catch (\Exception $e) {
                    $predictionDate = new \DateTime();
                }
            }
            $prediction->setDatePrediction($predictionDate);

            // Convertir le niveau de risque en int
            $riskLevel = $data['niveau_risque'] ?? 1;
            if (is_string($riskLevel)) {
                $riskMapping = ['Faible' => 1, 'Moyen' => 2, 'Élevé' => 3, 'Très élevé' => 4, 'Critique' => 5];
                $riskLevel = $riskMapping[$riskLevel] ?? 1;
            }
            $prediction->setNiveauRisque((int) $riskLevel);

            $recommandationsStr = "";
            if (isset($data['description']) && !empty($data['description'])) {
                $recommandationsStr .= "Météo: " . $data['description'] . "\n\n";
            }

            if (isset($data['recommandations']) && !empty($data['recommandations'])) {
                $recs = is_array($data['recommandations']) 
                    ? implode("\n• ", $data['recommandations']) 
                    : $data['recommandations'];
                if (is_array($data['recommandations']) && !empty($recs)) {
                    $recs = "• " . $recs;
                }
                $recommandationsStr .= $recs;
            }

            if (!empty($recommandationsStr)) {
                $prediction->setRecommandations($recommandationsStr);
            }

            if (isset($data['temperature_eau'])) {
                $prediction->setTemperatureEau((float) $data['temperature_eau']);
            }
            if (isset($data['conditions_meteo'])) {
                $prediction->setConditionsMeteo($data['conditions_meteo']);
            }

            // Sauvegarder en base de données
            $entityManager->persist($prediction);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Prédiction sauvegardée avec succès',
                'prediction_id' => $prediction->getIdPrediction()
            ]);

        } catch (\Exception $e) {
            error_log('Erreur savePrediction: ' . $e->getMessage());
            error_log('Stack: ' . $e->getTraceAsString());
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/zones', name: 'app_prediction_zones', methods: ['GET'])]
    public function getZones(): JsonResponse
    {
        // Définition des zones côtières tunisiennes avec leurs coordonnées approximatives
        $zones = [
            'Nord' => ['lat' => 37.0, 'lon' => 10.0, 'name' => 'Nord'],
            'Nord-Est' => ['lat' => 36.8, 'lon' => 10.5, 'name' => 'Nord-Est'],
            'Est' => ['lat' => 36.5, 'lon' => 10.8, 'name' => 'Est'],
            'Sud-Est' => ['lat' => 35.8, 'lon' => 10.6, 'name' => 'Sud-Est'],
            'Sud' => ['lat' => 33.8, 'lon' => 10.1, 'name' => 'Sud'],
            'Sud-Ouest' => ['lat' => 34.2, 'lon' => 9.8, 'name' => 'Sud-Ouest'],
            'Ouest' => ['lat' => 35.5, 'lon' => 9.5, 'name' => 'Ouest'],
            'Nord-Ouest' => ['lat' => 36.2, 'lon' => 9.8, 'name' => 'Nord-Ouest'],
        ];

        return new JsonResponse([
            'success' => true,
            'zones' => $zones
        ]);
    }

    #[Route('/api/list', name: 'app_prediction_api_list', methods: ['GET'])]
    public function listPredictions(PredictionEchouageRepository $predictionRepository): JsonResponse
    {
        try {
            $predictions = $predictionRepository->findAll();

            $data = array_map(function($prediction) {
                return [
                    'id_prediction' => $prediction->getIdPrediction(),
                    'latitude' => $prediction->getLatitude(),
                    'longitude' => $prediction->getLongitude(),
                    'zone' => $prediction->getZone(),
                    'niveau_risque' => $prediction->getNiveauRisque(),
                    'date_prediction' => $prediction->getDatePrediction()->format('Y-m-d'),
                    'espece_concernee' => $prediction->getEspeceConcernee(),
                    'temperature_eau' => $prediction->getTemperatureEau(),
                    'conditions_meteo' => $prediction->getConditionsMeteo(),
                    'recommandations' => $prediction->getRecommandations(),
                ];
            }, $predictions);

            return new JsonResponse([
                'success' => true,
                'predictions' => $data
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération des prédictions: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/delete/{id}', name: 'app_prediction_api_delete', methods: ['DELETE'])]
    public function deletePrediction(int $id, PredictionEchouageRepository $predictionRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $prediction = $predictionRepository->find($id);

            if (!$prediction) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Prédiction non trouvée'
                ], 404);
            }

            $entityManager->remove($prediction);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Prédiction supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }
}