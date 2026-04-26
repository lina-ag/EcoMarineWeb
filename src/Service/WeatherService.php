<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Psr\Log\LoggerInterface;

class WeatherService
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $apiKey;
    private string $baseUrl;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger, string $openWeatherApiKey)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = $openWeatherApiKey;
        $this->baseUrl = 'https://api.openweathermap.org/data/2.5';
    }

    /**
     * Récupère les données météorologiques actuelles pour des coordonnées GPS
     */
    public function getCurrentWeather(float $lat, float $lon): ?array
    {
        // Si la clé API n'est pas configurée, on utilise des données de fallback
        if (empty($this->apiKey) || $this->apiKey === 'votre_cle_api_ici') {
            $this->logger->warning('Clé API OpenWeather non configurée, utilisation des données de fallback.');
            return $this->getFallbackWeatherData();
        }

        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . '/weather', [
                'query' => [
                    'lat' => $lat,
                    'lon' => $lon,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                    'lang' => 'fr'
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();

                return [
                    'temperature' => $data['main']['temp'] ?? null,
                    'humidity' => $data['main']['humidity'] ?? null,
                    'wind_speed' => $data['wind']['speed'] ?? null,
                    'wind_direction' => $data['wind']['deg'] ?? null,
                    'description' => $data['weather'][0]['description'] ?? null,
                    'main' => $data['weather'][0]['main'] ?? null,
                    'pressure' => $data['main']['pressure'] ?? null,
                    'visibility' => $data['visibility'] ?? null,
                    'clouds' => $data['clouds']['all'] ?? null,
                    'location' => $data['name'] ?? null,
                    'country' => $data['sys']['country'] ?? null,
                ];
            }

            $this->logger->error('Erreur API météo: ' . $response->getStatusCode());
            return $this->getFallbackWeatherData();

        } catch (\Exception $e) {
            $this->logger->error('Erreur de connexion à l\'API météo: ' . $e->getMessage());
            return $this->getFallbackWeatherData();
        }
    }

    /**
     * Données météo de fallback quand l'API n'est pas disponible
     */
    private function getFallbackWeatherData(): array
    {
        return [
            'temperature' => 20.0,
            'humidity' => 60,
            'wind_speed' => 10.0,
            'wind_direction' => 180,
            'description' => 'données estimées',
            'main' => 'Clear',
            'pressure' => 1013,
            'visibility' => 10000,
            'clouds' => 20,
            'location' => 'Inconnu',
            'country' => 'TN',
        ];
    }

    /**
     * Convertit les conditions météorologiques en catégories prédéfinies
     */
    public function mapWeatherToCategory(?string $weatherMain, ?string $description): string
    {
        if (!$weatherMain) {
            return 'Nuageux'; // Valeur par défaut
        }

        $weatherMain = strtolower($weatherMain);

        switch ($weatherMain) {
            case 'clear':
                return 'Ensoleillé';
            case 'clouds':
                return 'Nuageux';
            case 'rain':
            case 'drizzle':
                return 'Pluvieux';
            case 'thunderstorm':
                return 'Tempête';
            case 'mist':
            case 'fog':
            case 'haze':
                return 'Brumeux';
            case 'snow':
                return 'Nuageux'; // Pas de catégorie neige, on utilise nuageux
            default:
                return 'Nuageux'; // Valeur par défaut
        }
    }

    /**
     * Récupère les prévisions météorologiques pour les prochains jours
     */
    public function getForecast(float $lat, float $lon, int $days = 5): ?array
    {
        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . '/forecast', [
                'query' => [
                    'lat' => $lat,
                    'lon' => $lon,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                    'lang' => 'fr',
                    'cnt' => $days * 8 // 8 mesures par jour (3h)
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                return $response->toArray();
            }

            $this->logger->error('Erreur API prévisions: ' . $response->getStatusCode());
            return null;

        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Erreur de connexion à l\'API prévisions: ' . $e->getMessage());
            return null;
        }
    }

    public function getWeatherForCity(string $city): ?array
{
    if (empty($this->apiKey) || $this->apiKey === 'votre_cle_api_ici') {
        $this->logger->warning('Clé API OpenWeather non configurée, utilisation des données de fallback.');
        return $this->getFallbackWeatherData();
    }

    try {
        $response = $this->httpClient->request('GET', $this->baseUrl . '/weather', [
            'query' => [
                'q'     => $city,
                'appid' => $this->apiKey,
                'units' => 'metric',
                'lang'  => 'fr',
            ]
        ]);

        if ($response->getStatusCode() === 200) {
            $data = $response->toArray();
            return [
                'temperature'    => $data['main']['temp'] ?? null,
                'humidity'       => $data['main']['humidity'] ?? null,
                'wind_speed'     => $data['wind']['speed'] ?? null,
                'wind_direction' => $data['wind']['deg'] ?? null,
                'description'    => $data['weather'][0]['description'] ?? null,
                'main'           => $data['weather'][0]['main'] ?? null,
                'pressure'       => $data['main']['pressure'] ?? null,
                'visibility'     => $data['visibility'] ?? null,
                'clouds'         => $data['clouds']['all'] ?? null,
                'location'       => $data['name'] ?? null,
                'country'        => $data['sys']['country'] ?? null,
            ];
        }

        $this->logger->error('Erreur API météo: ' . $response->getStatusCode());
        return $this->getFallbackWeatherData();

    } catch (\Exception $e) {
        $this->logger->error('Erreur météo ville: ' . $e->getMessage());
        return $this->getFallbackWeatherData();
    }
  }
}