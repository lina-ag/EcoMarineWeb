<?php

namespace App\Controller;

use App\Service\FaceRecognitionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/diagnostic')]
class DiagnosticController extends AbstractController
{
    /**
     * Vérifier la santé du système
     */
    #[Route('/health', name: 'diagnostic_health')]
    public function health(FaceRecognitionService $faceService): JsonResponse
    {
        $pythonServiceOk = $faceService->testPythonService();

        return $this->json([
            'status' => $pythonServiceOk ? 'ok' : 'error',
            'python_service' => $pythonServiceOk ? 'available' : 'unavailable',
            'message' => $pythonServiceOk 
                ? 'Système de reconnaissance faciale opérationnel'
                : 'Service Python de reconnaissance faciale indisponible'
        ]);
    }

    /**
     * Page de diagnostic (pour admins)
     */
    #[Route('/face-recognition', name: 'diagnostic_face_recognition')]
    public function faceRecognitionDiagnostic(FaceRecognitionService $faceService): Response
    {
        $pythonServiceOk = $faceService->testPythonService();

        return $this->render('diagnostic/face_recognition.html.twig', [
            'python_service_ok' => $pythonServiceOk,
        ]);
    }
}
