<?php

namespace App\Service;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class FaceRecognitionService
{
    private const PYTHON_API = 'http://127.0.0.1:5000';

    public function __construct(
        private UtilisateurRepository $utilisateurRepository,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger
    ) {}

    /**
     * Extrait l'encodage facial depuis une image base64
     * Retourne le JSON de l'encodage (tableau de floats) ou null
     */
   public function extractEncoding(string $base64Image): ?string
{
    try {
        $response = $this->httpClient->request('POST', self::PYTHON_API . '/extract_encoding', [
            'json'    => ['image' => $base64Image],
            'timeout' => 30,
        ]);

        // ✅ Lire le contenu AVANT de vérifier le statut
        $data = $response->toArray(false); // false = ne pas lever d'exception sur 4xx

        if (!$data['success']) {
            $this->logger->warning('Extraction échouée : ' . ($data['message'] ?? 'Erreur inconnue'));
            return null;
        }

        return json_encode($data['encoding']);

    } catch (\Exception $e) {
        $this->logger->error('Erreur extractEncoding : ' . $e->getMessage());
        return null;
    }
}

    /**
     * Reconnaît un utilisateur depuis une image base64
     * Retourne l'utilisateur trouvé ou null
     */
    public function recognizeFace(string $base64Image): ?Utilisateur
    {
        try {
            // 1. Extraire l'encodage de l'image capturée
            $response = $this->httpClient->request('POST', self::PYTHON_API . '/extract_encoding', [
                'json'    => ['image' => $base64Image],
                'timeout' => 30,
            ]);

            $result = $response->toArray();

            if (!$result['success']) {
                $this->logger->warning('Visage non détecté : ' . ($result['message'] ?? ''));
                return null;
            }

            $loginEncoding = $result['encoding'];

            // 2. Comparer avec tous les utilisateurs en base
            $users = $this->utilisateurRepository->findAll();

            foreach ($users as $user) {
                $storedRaw = $user->getFaceEncoding();
                if (!$storedRaw) continue;

                $dbEncoding = json_decode($storedRaw, true);
                if (!is_array($dbEncoding)) continue;

                $compare = $this->httpClient->request('POST', self::PYTHON_API . '/compare_faces', [
                    'json' => [
                        'encoding1' => $loginEncoding,
                        'encoding2' => $dbEncoding,
                    ],
                    'timeout' => 30,
                ]);

                $compareResult = $compare->toArray();

                if ($compareResult['match']) {
                    return $user;
                }
            }

            return null;

        } catch (\Exception $e) {
            $this->logger->error('Erreur recognizeFace : ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Vérifie si une image contient un visage valide
     */
    public function verifyFace(string $base64Image): array
    {
        try {
            $response = $this->httpClient->request('POST', self::PYTHON_API . '/verify_face', [
                'json'    => ['image' => $base64Image],
                'timeout' => 30,
            ]);
            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Erreur verifyFace : ' . $e->getMessage());
            return ['success' => false, 'face_detected' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Teste la connexion avec le service Python
     */
    public function testPythonService(): bool
    {
        try {
            $response = $this->httpClient->request('GET', self::PYTHON_API . '/health', ['timeout' => 5]);
            return ($response->toArray()['status'] ?? '') === 'ok';
        } catch (\Exception $e) {
            return false;
        }
    }
    
}