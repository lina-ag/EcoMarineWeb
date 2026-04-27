<?php

namespace App\Service;

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
}