<?php

namespace App\Service;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class FaceRecognitionService
{
    public function __construct(
        private UtilisateurRepository $utilisateurRepository,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $pythonFaceServiceUrl
    ) {}

    private function pythonApiUrl(string $path): string
    {
        return rtrim($this->pythonFaceServiceUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Extrait l'encodage facial depuis une image base64
     * Retourne le JSON de l'encodage (tableau de floats) ou null
     */
   public function extractEncoding(string $base64Image): ?string
{
    try {
        $response = $this->httpClient->request('POST', $this->pythonApiUrl('/extract_encoding'), [
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

    } catch (\Throwable $e) {
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
            $response = $this->httpClient->request('POST', $this->pythonApiUrl('/extract_encoding'), [
                'json'    => ['image' => $base64Image],
                'timeout' => 30,
            ]);

            $result = $response->toArray(false);

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

                if ($this->isFaceMatch($loginEncoding, $dbEncoding)) {
                    return $user;
                }
            }

            return null;

        } catch (\Throwable $e) {
            $this->logger->error('Erreur recognizeFace : ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Compare deux encodages localement pour éviter un appel HTTP par utilisateur.
     *
     * @param array<int, float|int|string> $encoding1
     * @param array<int, float|int|string> $encoding2
     */
    private function isFaceMatch(array $encoding1, array $encoding2): bool
    {
        if (count($encoding1) === 0 || count($encoding1) !== count($encoding2)) {
            return false;
        }

        $dotProduct = 0.0;
        $norm1 = 0.0;
        $norm2 = 0.0;

        foreach ($encoding1 as $index => $value1) {
            $value2 = (float) ($encoding2[$index] ?? 0);
            $float1 = (float) $value1;

            $dotProduct += $float1 * $value2;
            $norm1 += $float1 * $float1;
            $norm2 += $value2 * $value2;
        }

        $denominator = sqrt($norm1) * sqrt($norm2);
        if ($denominator <= 0.0) {
            return false;
        }

        $cosineDistance = 1 - ($dotProduct / $denominator);

        return $cosineDistance < 0.4;
    }

    /**
     * Vérifie si une image contient un visage valide
     */
    public function verifyFace(string $base64Image): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->pythonApiUrl('/verify_face'), [
                'json'    => ['image' => $base64Image],
                'timeout' => 30,
            ]);
            return $response->toArray(false);
        } catch (\Throwable $e) {
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
            $response = $this->httpClient->request('GET', $this->pythonApiUrl('/health'), ['timeout' => 5]);
            return ($response->toArray(false)['status'] ?? '') === 'ok';
        } catch (\Throwable $e) {
            return false;
        }
    }
    
}