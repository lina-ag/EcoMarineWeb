<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiWasteController extends AbstractController
{
    #[Route('/admin/ai/analyze-waste', name: 'admin_ai_analyze_waste', methods: ['POST'])]
    public function analyzeWaste(Request $request, HttpClientInterface $client): JsonResponse
    {
        $image = $request->files->get('image');

        if (!$image) {
            return $this->json(['error' => 'Aucune image recue.'], 400);
        }

        $apiKey = $_ENV['OPENAI_API_KEY'] ?? null;
        $mime = $image->getMimeType();
        $base64 = base64_encode(file_get_contents($image->getPathname()));
        $dataUrl = 'data:' . $mime . ';base64,' . $base64;

        if (!$apiKey) {
            return $this->json([
                'success' => true,
                'fallback' => true,
                'analysis' => $this->fallbackAnalysis('cle API manquante'),
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
                                'text' => 'Analyse cette image de dechets sur une plage. Reponds en francais avec : types de dechets, pourcentages estimes, origine probable, impact ecologique, recommandations.',
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => $dataUrl,
                                ],
                            ],
                        ],
                    ]],
                    'max_tokens' => 700,
                ],
                'timeout' => 60,
            ]);

            $status = $response->getStatusCode();
            $data = $response->toArray(false);

            if ($status === 429) {
                return $this->json([
                    'success' => true,
                    'fallback' => true,
                    'analysis' => $this->fallbackAnalysis('quota OpenAI atteint'),
                ]);
            }

            if ($status >= 400) {
                return $this->json([
                    'success' => true,
                    'fallback' => true,
                    'analysis' => $this->fallbackAnalysis('erreur API OpenAI'),
                ]);
            }

            $text = $data['choices'][0]['message']['content'] ?? null;

            if (!$text) {
                return $this->json([
                    'success' => true,
                    'fallback' => true,
                    'analysis' => $this->fallbackAnalysis('reponse IA vide'),
                ]);
            }

            return $this->json([
                'success' => true,
                'fallback' => false,
                'analysis' => $text,
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => true,
                'fallback' => true,
                'analysis' => $this->fallbackAnalysis('erreur reseau ou serveur'),
            ]);
        }
    }

    private function fallbackAnalysis(string $reason): string
    {
        return "1) Dechets probables : plastique, emballages, bouteilles, canettes et petits dechets mixtes.\n"
            . "2) Pourcentages estimes : plastique 65%, metal/canettes 15%, papier/carton 10%, autres 10%.\n"
            . "3) Origine probable : tourisme 70%, restaurants ou cafes proches 20%, activite maritime/peche 10%.\n"
            . "4) Impact biodiversite : risque eleve pour les oiseaux marins, poissons et tortues a cause de l ingestion ou de l etranglement.\n"
            . "5) Recommandations : nettoyage prioritaire, ajout de poubelles visibles, sensibilisation des visiteurs, tri des dechets collectes et suivi regulier de la zone.";
    }
}
