<?php

namespace App\Controller;

use App\Entity\DetectionDrone;
use App\Form\DetectionDroneType;
use App\Repository\DetectionDroneRepository;
use App\Service\RoboflowService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/detection/drone')]
final class DetectionDroneController extends AbstractController
{
    #[Route(name: 'app_detection_drone_index', methods: ['GET'])]
    public function index(DetectionDroneRepository $detectionDroneRepository): Response
    {
        return $this->render('detection_drone/index.html.twig', [
            'detection_drones' => $detectionDroneRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_detection_drone_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $detectionDrone = new DetectionDrone();
        $form = $this->createForm(DetectionDroneType::class, $detectionDrone);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image file upload
            $imageFile = $form->get('image_path')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/detections';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $imageFile->move($uploadDir, $newFilename);
                    $detectionDrone->setImagePath('/uploads/detections/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image: ' . $e->getMessage());
                }
            }

            // Set timestamp if not provided
            if (!$detectionDrone->getTimestamp()) {
                $detectionDrone->setTimestamp(date('Y-m-d H:i:s'));
            }
            
            // Auto-generate id_mission since it's required by the DB
            if (!$detectionDrone->getIdMission()) {
                $detectionDrone->setIdMission(random_int(1000, 9999));
            }

            $entityManager->persist($detectionDrone);
            $entityManager->flush();

            return $this->redirectToRoute('app_detection_drone_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('detection_drone/new.html.twig', [
            'detection_drone' => $detectionDrone,
            'form' => $form,
        ]);
    }

    #[Route('/analyze-image', name: 'app_detection_drone_analyze', methods: ['POST'])]
    public function analyzeImage(Request $request, RoboflowService $roboflowService): JsonResponse
    {
        $imageFile = $request->files->get('image');

        if (!$imageFile) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Aucune image fournie. Veuillez uploader une image.',
            ], 400);
        }

        // Validate MIME type
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $mimeType = $imageFile->getMimeType();
        if (!in_array($mimeType, $allowedMimes)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Format d\'image non supporté. Utilisez: JPEG, PNG, GIF ou WebP.',
            ], 400);
        }

        // Validate file size (max 10MB)
        if ($imageFile->getSize() > 10 * 1024 * 1024) {
            return new JsonResponse([
                'success' => false,
                'error' => 'L\'image dépasse la taille maximale de 10 MB.',
            ], 400);
        }

        try {
            // Analyser l'image avec le modèle custom EcoMarine IA
            $analysisResult = $roboflowService->analyzeImage($imageFile);
            
            // Flatten result so JS can access data.espece, data.comportement etc. directly
            $result = array_merge(['success' => false], $analysisResult);
        } catch (\Exception $e) {
            $result = [
                'success' => false,
                'error' => 'Erreur lors de l\'analyse par l\'IA: ' . $e->getMessage(),
            ];
        }

        return new JsonResponse($result, $result['success'] ? 200 : 500);
    }

    #[Route('/{id_detection}', name: 'app_detection_drone_show', methods: ['GET'])]
    public function show(DetectionDrone $detectionDrone): Response
    {
        return $this->render('detection_drone/show.html.twig', [
            'detection_drone' => $detectionDrone,
        ]);
    }

    #[Route('/{id_detection}/edit', name: 'app_detection_drone_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, DetectionDrone $detectionDrone, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DetectionDroneType::class, $detectionDrone);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_detection_drone_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('detection_drone/edit.html.twig', [
            'detection_drone' => $detectionDrone,
            'form' => $form,
        ]);
    }

    #[Route('/{id_detection}', name: 'app_detection_drone_delete', methods: ['POST'])]
    public function delete(Request $request, DetectionDrone $detectionDrone, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$detectionDrone->getId_detection(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($detectionDrone);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_detection_drone_index', [], Response::HTTP_SEE_OTHER);
    }
}
