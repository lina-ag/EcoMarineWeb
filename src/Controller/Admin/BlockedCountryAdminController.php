<?php
namespace App\Controller\Admin;

use App\Entity\BlockedCountry;
use App\Repository\BlockedCountryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/countries', name: 'admin_')]
class BlockedCountryAdminController extends AbstractController
{
    #[Route('', name: 'blocked_countries_list', methods: ['GET'])]
    public function index(BlockedCountryRepository $repo): Response
    {
        return $this->render('admin/blocked_countries.html.twig', [
            'countries' => $repo->findAll(),
        ]);
    }

    #[Route('/block', name: 'block_country', methods: ['POST'])]
    public function block(Request $request, EntityManagerInterface $em, BlockedCountryRepository $repo): Response
    {
        $countryCode = strtoupper($request->request->get('country_code', ''));
        $countryName = $request->request->get('country_name', '');
        $reason      = $request->request->get('reason');

        if (!$countryCode || strlen($countryCode) !== 2) {
            $this->addFlash('error', 'Code pays invalide (2 lettres requis).');
            return $this->redirectToRoute('admin_blocked_countries_list');
        }

        if ($repo->isCountryBlocked($countryCode)) {
            $this->addFlash('error', "Le pays {$countryCode} est déjà bloqué.");
            return $this->redirectToRoute('admin_blocked_countries_list');
        }

        $em->persist(new BlockedCountry($countryCode, $countryName, $reason ?: null));
        $em->flush();

        $this->addFlash('success', "Pays {$countryCode} bloqué avec succès !");
        return $this->redirectToRoute('admin_blocked_countries_list');
    }

    #[Route('/unblock/{code}', name: 'unblock_country', methods: ['POST'])]
    public function unblock(string $code, BlockedCountryRepository $repo, EntityManagerInterface $em): Response
    {
        $country = $repo->findOneBy(['countryCode' => strtoupper($code)]);

        if (!$country) {
            $this->addFlash('error', "Pays {$code} non trouvé.");
            return $this->redirectToRoute('admin_blocked_countries_list');
        }

        $em->remove($country);
        $em->flush();

        $this->addFlash('success', "Pays {$code} débloqué avec succès !");
        return $this->redirectToRoute('admin_blocked_countries_list');
    }
}