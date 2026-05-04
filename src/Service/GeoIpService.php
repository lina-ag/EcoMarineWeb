<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeoIpService
{
    public function __construct(private HttpClientInterface $client) {}

    public function getCountryFromIp(string $ip): array
    {
        try {
            // API gratuite ipapi.co
            $response = $this->client->request('GET', "https://ipapi.co/{$ip}/json/");
            $data = $response->toArray();

            return [
                'country_code' => $data['country_code'] ?? 'XX',
                'country_name' => $data['country_name'] ?? 'Unknown',
                'ip'           => $ip,
            ];
        } catch (\Exception $e) {
            return [
                'country_code' => 'XX',
                'country_name' => 'Unknown',
                'ip'           => $ip,
            ];
        }
    }
}