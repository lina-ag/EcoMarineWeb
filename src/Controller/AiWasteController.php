<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiWasteController extends AbstractController
{
    #[Route('/admin/ai/analyze-waste', name: 'admin_ai_analyze_waste', methods: ['POST'])]
    public function analyzeWaste(Request $request, HttpClientInterface $client): JsonResponse
    {
        $image = $request->files->get('image');

        if (!$image) {
            return $this->json(['error' => 'Aucune image reÃ§ue.'], 400);
        }

        $apiKey = $_ENV['OPENAI_API_KEY'] ?? null;
        $mime = $image->getMimeType();
        $base64 = base64_encode(file_get_contents($image->getPathname()));
        $dataUrl = 'data:' . $mime . ';base64,' . $base64;

        if (!$apiKey) {
            return $this->json([
                'success' => true,
                'fallback' => true,
                'analysis' => $this->fallbackAnalysis('clÃ© API manquante')
            ]);
        }

        try {
            $response = $client->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-4o-mini',
                    'messages' => [[
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Analyse cette image de dÃ©chets sur une plage. RÃ©ponds en franÃ§ais avec : types de dÃ©chets, pourcentages estimÃ©s, origine probable, impact Ã©cologique, recommandations.'
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => $dataUrl
                                ]
                            ]
                        ]
                    ]],
                    'max_tokens' => 700
                ],
                'timeout' => 60,
            ]);

            $status = $response->getStatusCode();
            $data = $response->toArray(false);

            if ($status === 429) {
                return $this->json([
                    'success' => true,
                    'fallback' => true,
                    'analysis' => $this->fallbackAnalysis('quota OpenAI atteint')
                ]);
            }

            if ($status >= 400) {
                return $this->json([
                    'success' => true,
                    'fallback' => true,
                    'analysis' => $this->fallbackAnalysis('erreur API OpenAI')
                ]);
            }

            $text = $data['choices'][0]['message']['content'] ?? null;

            if (!$text) {
                return $this->json([
                    'success' => true,
                    'fallback' => true,
                    'analysis' => $this->fallbackAnalysis('rÃ©ponse IA vide')
                ]);
            }

            return $this->json([
                'success' => true,
                'fallback' => false,
                'analysis' => $text
            ]);

        } catch (\Throwable $e) {
            return $this->json([
                'success' => true,
                'fallback' => true,
                'analysis' => $this->fallbackAnalysis('erreur rÃ©seau ou serveur')
            ]);
        }
    }

    private function fallbackAnalysis(string $reason): string
    {
        return "âš ï¸ Mode fallback activÃ© : " . $reason . ".\n\n"
            . "Analyse locale estimÃ©e :\n"
            . "1) DÃ©chets probables : plastique, emballages, bouteilles, canettes et petits dÃ©chets mixtes.\n"
            . "2) Pourcentages estimÃ©s : plastique 65%, mÃ©tal/canettes 15%, papier/carton 10%, autres 10%.\n"
            . "3) Origine probable : tourisme 70%, restaurants ou cafÃ©s proches 20%, activitÃ© maritime/pÃªche 10%.\n"
            . "4) Impact biodiversitÃ© : risque Ã©levÃ© pour les oiseaux marins, poissons et tortues Ã  cause de lâ€™ingestion ou de lâ€™Ã©tranglement.\n"
            . "5) Recommandations : nettoyage prioritaire, ajout de poubelles visibles, sensibilisation des visiteurs, tri des dÃ©chets collectÃ©s et suivi rÃ©gulier de la zone.";
    }
}