<?php
namespace App\Controller\Admin;

use App\Service\RapportMLService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/rapport', name: 'admin_rapport_')]
class RapportController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(RapportMLService $rapportService): Response
    {
        $rapport = $rapportService->genererRapport();
        return $this->render('admin/rapport.html.twig', ['rapport' => $rapport]);
    }
}