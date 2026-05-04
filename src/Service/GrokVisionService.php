<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class GrokVisionService
{
    private string $grokApiKey;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    private const API_URL = 'https://api.x.ai/v1/chat/completions';
    private const MODEL = 'grok-2-vision-latest';

    // Marine and coastal animal species commonly found in Mediterranean/Kuriat
    private const KNOWN_SPECIES = [
        'Caretta caretta', 'Tursiops truncatus', 'Delphinus delphis',
        'Monachus monachus', 'Stenella coeruleoalba', 'Chelonia mydas',
        'Dermochelys coriacea', 'Phocoena phocoena', 'Balaenoptera physalus',
        'Larus michahellis', 'Phalacrocorax aristotelis', 'Calonectris diomedea',
        'Octopus vulgaris', 'Sepia officinalis', 'Hippocampus hippocampus',
        'Epinephelus marginatus', 'Mullus barbatus', 'Posidonia oceanica',
    ];

    public function __construct(
        string $grokApiKey,
        HttpClientInterface $httpClient,
        LoggerInterface $logger
    ) {
        $this->grokApiKey = $grokApiKey;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Analyze a drone image for marine animal recognition using Grok Vision API.
     *
     * @param string $base64Image Base64-encoded image data
     * @param string $mimeType    MIME type of the image (e.g. image/jpeg)
     * @return array{
     *     success: bool,
     *     espece: ?string,
     *     nombre_individus: ?int,
     *     comportement: ?string,
     *     confiance_ia: ?string,
     *     description: ?string,
     *     error: ?string
     * }
     */
    public function analyzeImage(string $base64Image, string $mimeType = 'image/jpeg'): array
    {
        if (empty($this->grokApiKey) || $this->grokApiKey === 'your_grok_api_key_here') {
            return [
                'success' => false,
                'error' => 'Clé API Grok non configurée. Ajoutez GROK_API_KEY dans le fichier .env',
                'espece' => null,
                'nombre_individus' => null,
                'comportement' => null,
                'confiance_ia' => null,
                'description' => null,
            ];
        }

        $speciesList = implode(', ', self::KNOWN_SPECIES);

        $systemPrompt = <<<PROMPT
Tu es un expert en biologie marine et en reconnaissance d'espèces animales à partir d'images aériennes de drones. 
Tu travailles pour EcoMarine, une plateforme d'écotourisme et de recherche marine basée à Kuriat, Monastir (Tunisie).

Analyse l'image fournie et identifie les animaux marins ou côtiers présents.

Espèces courantes dans cette région : {$speciesList}

Tu DOIS répondre UNIQUEMENT avec un objet JSON valide (sans markdown, sans backticks, sans texte avant ou après) avec cette structure exacte :
{
    "espece": "Nom scientifique ou commun de l'espèce principale détectée",
    "nombre_individus": <nombre entier d'individus visibles, minimum 1>,
    "comportement": "<exactement l'une de ces valeurs: Normal, Alerte, Danger>",
    "confiance_ia": "<exactement l'une de ces valeurs: Très faible, Faible, Moyen, Fort, Très fort>",
    "description": "Description courte en français de ce que tu observes dans l'image (max 200 caractères)"
}

Règles :
- Si tu ne détectes aucun animal, mets espece à "Aucune espèce détectée" et nombre_individus à 0
- comportement doit être EXACTEMENT : "Normal", "Alerte" ou "Danger"
- confiance_ia doit être EXACTEMENT : "Très faible", "Faible", "Moyen", "Fort" ou "Très fort"
- Réponds UNIQUEMENT avec le JSON, rien d'autre
PROMPT;

        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->grokApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => self::MODEL,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemPrompt,
                        ],
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Analyse cette image prise par un drone de surveillance marine et identifie les animaux présents. Réponds uniquement en JSON.',
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => "data:{$mimeType};base64,{$base64Image}",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => 500,
                ],
                'timeout' => 60,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $this->logger->error('Grok API returned status ' . $statusCode, [
                    'response' => $response->getContent(false),
                ]);
                return $this->errorResponse('Erreur API Grok (HTTP ' . $statusCode . ')');
            }

            $data = $response->toArray();
            $content = $data['choices'][0]['message']['content'] ?? null;

            if (!$content) {
                $this->logger->error('Grok API returned empty content', ['data' => $data]);
                return $this->errorResponse('Réponse vide de l\'API Grok');
            }

            // Clean the response - remove possible markdown code fences
            $content = trim($content);
            $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
            $content = preg_replace('/\s*```$/i', '', $content);
            $content = trim($content);

            $result = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Failed to parse Grok response as JSON', [
                    'content' => $content,
                    'error' => json_last_error_msg(),
                ]);
                return $this->errorResponse('Impossible de parser la réponse IA');
            }

            // Validate and sanitize the response
            $validBehaviors = ['Normal', 'Alerte', 'Danger'];
            $validConfidences = ['Très faible', 'Faible', 'Moyen', 'Fort', 'Très fort'];

            $espece = $result['espece'] ?? 'Espèce inconnue';
            $nombreIndividus = max(0, intval($result['nombre_individus'] ?? 1));
            $comportement = in_array($result['comportement'] ?? '', $validBehaviors)
                ? $result['comportement'] : 'Normal';
            $confianceIa = in_array($result['confiance_ia'] ?? '', $validConfidences)
                ? $result['confiance_ia'] : 'Moyen';
            $description = mb_substr($result['description'] ?? '', 0, 300);

            $this->logger->info('Grok Vision analysis successful', [
                'espece' => $espece,
                'nombre_individus' => $nombreIndividus,
                'confiance_ia' => $confianceIa,
            ]);

            return [
                'success' => true,
                'espece' => $espece,
                'nombre_individus' => $nombreIndividus,
                'comportement' => $comportement,
                'confiance_ia' => $confianceIa,
                'description' => $description,
                'error' => null,
            ];

        } catch (\Exception $e) {
            $this->logger->error('Grok Vision API call failed', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Erreur de connexion à l\'API Grok: ' . $e->getMessage());
        }
    }

    private function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'espece' => null,
            'nombre_individus' => null,
            'comportement' => null,
            'confiance_ia' => null,
            'description' => null,
            'error' => $message,
        ];
    }
}
