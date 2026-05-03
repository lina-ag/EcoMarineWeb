<?php

namespace App\Service;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FaceRecognitionService
{
    public function __construct(
        private UtilisateurRepository $utilisateurRepository,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $pythonFaceServiceUrl
    ) {}

    public function extractEncoding(string $base64Image): ?string
    {
        try {
            $data = $this->requestJson('POST', '/extract_encoding', [
                'json' => ['image' => $base64Image],
            ]);

            if (!is_array($data) || !($data['success'] ?? false)) {
                $this->logger->warning('Extraction visage echouee.', [
                    'message' => $data['message'] ?? 'Erreur inconnue',
                ]);
                return null;
            }

            if (!isset($data['encoding']) || !is_array($data['encoding'])) {
                $this->logger->warning('Encodage facial absent ou invalide dans la reponse Python.');
                return null;
            }

            return json_encode($data['encoding'], JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur extractEncoding : ' . $e->getMessage());
            return null;
        }
    }

    public function recognizeFace(string $base64Image): ?Utilisateur
    {
        try {
            $result = $this->requestJson('POST', '/extract_encoding', [
                'json' => ['image' => $base64Image],
            ]);

            if (!is_array($result) || !($result['success'] ?? false) || !isset($result['encoding']) || !is_array($result['encoding'])) {
                $this->logger->warning('Reconnaissance faciale impossible: extraction visage echouee.', [
                    'message' => $result['message'] ?? '',
                ]);
                return null;
            }

            $loginEncoding = $result['encoding'];

            foreach ($this->utilisateurRepository->findAll() as $user) {
                $storedRaw = $user->getFaceEncoding();
                if (!$storedRaw) {
                    continue;
                }

                $dbEncoding = json_decode($storedRaw, true);
                if (!is_array($dbEncoding)) {
                    continue;
                }

                $compareResult = $this->requestJson('POST', '/compare_faces', [
                    'json' => [
                        'encoding1' => $loginEncoding,
                        'encoding2' => $dbEncoding,
                    ],
                ]);

                if (is_array($compareResult) && ($compareResult['match'] ?? false)) {
                    return $user;
                }
            }

            return null;
        } catch (\Throwable $e) {
            $this->logger->error('Erreur recognizeFace : ' . $e->getMessage());
            return null;
        }
    }

    public function verifyFace(string $base64Image): array
    {
        try {
            $result = $this->requestJson('POST', '/verify_face', [
                'json' => ['image' => $base64Image],
            ]);

            if (!is_array($result)) {
                return ['success' => false, 'face_detected' => false, 'message' => 'Service Python indisponible'];
            }

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Erreur verifyFace : ' . $e->getMessage());
            return ['success' => false, 'face_detected' => false, 'message' => $e->getMessage()];
        }
    }

    public function testPythonService(): bool
    {
        try {
            $response = $this->requestJson('GET', '/health', [], 5);
            return ($response['status'] ?? '') === 'ok';
        } catch (\Throwable) {
            return false;
        }
    }

    private function requestJson(string $method, string $path, array $options = [], int $timeout = 30): ?array
    {
        try {
            $response = $this->httpClient->request(
                $method,
                rtrim($this->pythonFaceServiceUrl, '/') . $path,
                array_merge($options, ['timeout' => $timeout])
            );

            $data = $response->toArray(false);

            return is_array($data) ? $data : null;
        } catch (\Throwable $e) {
            $this->logger->error('Erreur appel service Python.', [
                'path' => $path,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
