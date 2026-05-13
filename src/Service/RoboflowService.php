<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class RoboflowService
{
    private $httpClient;

    /**
     * The URL of your local Python Flask AI API.
     * Make sure to run: python python_ai/api.py
     */
    private string $pythonApiUrl = 'http://127.0.0.1:5000/predict';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function analyzeImage(UploadedFile $file): array
    {
        try {
            // Read the image and encode it as base64 to send as JSON
            $imageData = file_get_contents($file->getPathname());
            $base64Image = base64_encode($imageData);

            // Send JSON to the Python Flask API
            $response = $this->httpClient->request('POST', $this->pythonApiUrl, [
                'json' => [
                    'image_base64' => $base64Image,
                    'mime_type'    => $file->getMimeType(),
                ],
                'timeout' => 30,
            ]);

            $data = $response->toArray();

            return [
                'success'          => true,
                'espece'           => $data['espece'] ?? 'Inconnu',
                'nombre_individus' => $data['nombre_individus'] ?? 1,
                'comportement'     => $data['comportement'] ?? 'Observation',
                'confiance_ia'     => $data['confiance_ia'] ?? '0%',
                'description'      => $data['description'] ?? '',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Vérifiez que l\'API Python tourne: python python_ai/api.py - Erreur: ' . $e->getMessage(),
            ];
        }
    }
}
