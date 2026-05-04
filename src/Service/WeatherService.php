<?php

namespace App\Service;

<<<<<<< HEAD
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $openWeatherApiKey
    ) {
    }

    public function getCurrentWeather(float $lat, float $lon): array
    {
        $response = $this->httpClient->request('GET', 'https://api.openweathermap.org/data/2.5/weather', [
            'query' => [
                'lat' => $lat,
                'lon' => $lon,
                'appid' => $this->openWeatherApiKey,
                'units' => 'metric',
                'lang' => 'fr',
            ],
        ]);

        $data = $response->toArray(false);

        return [
            'main' => $data['weather'][0]['main'] ?? null,
            'description' => $data['weather'][0]['description'] ?? null,
            'wind_speed' => $data['wind']['speed'] ?? 0,
            'temp' => $data['main']['temp'] ?? null,
        ];
    }
=======
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class WeatherService
{
    private const ENDPOINT = 'https://api.api-ninjas.com/v1/weather';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(API_NINJAS_KEY)%')]
        private readonly string $apiKey,
    ) {
    }

   public function getWeatherForCity(string $city): array
{
    try {
        $response = $this->httpClient->request('GET', self::ENDPOINT, [
            'query' => [
                'lat' => 35.7643,
                'lon' => 10.8113,
            ],
            'headers' => [
                'X-Api-Key' => $this->apiKey,
            ],
            'timeout' => 10,
        ]);

        $data = $response->toArray();
    } catch (\Throwable $exception) {
        return [
            'city' => 'Monastir',
            'error' => 'Unable to fetch weather data right now.',
        ];
    }

    $cloudPct = isset($data['cloud_pct']) ? (int) $data['cloud_pct'] : null;

    return $data + [
        'city' => 'Monastir',
        'condition' => $this->inferCondition($cloudPct),
    ];
}

    private function inferCondition(?int $cloudPct): string
    {
        if ($cloudPct === null) {
            return 'unknown';
        }

        if ($cloudPct >= 75) {
            return 'cloudy';
        }

        if ($cloudPct >= 35) {
            return 'partly_cloudy';
        }

        return 'clear';
    }
>>>>>>> 163f23d3ff1ed003948dbcee8c3696b66c6027ca
}