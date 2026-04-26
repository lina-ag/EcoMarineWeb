<?php
namespace App\Controller\Api;

use App\Entity\BlockedCountry;
use App\Repository\BlockedCountryRepository;
use App\Service\GeoIpService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin', name: 'api_admin_')]
class BlockCountryController extends AbstractController
{
    #[Route('/users/block-country', name: 'block_country', methods: ['POST'])]
    public function blockCountry(
        Request $request,
        EntityManagerInterface $em,
        BlockedCountryRepository $repo
    ): JsonResponse {
        $data        = json_decode($request->getContent(), true);
        $countryCode = strtoupper($data['country_code'] ?? '');
        $countryName = $data['country_name'] ?? '';
        $reason      = $data['reason'] ?? null;

        if (!$countryCode || strlen($countryCode) !== 2) {
            return $this->json(['success' => false, 'message' => 'Code pays invalide'], 400);
        }

        if ($repo->isCountryBlocked($countryCode)) {
            return $this->json(['success' => false, 'message' => "Le pays {$countryCode} est déjà bloqué"], 409);
        }

        $blocked = new BlockedCountry($countryCode, $countryName, $reason);
        $em->persist($blocked);
        $em->flush();

        return $this->json([
            'success'      => true,
            'message'      => "Pays {$countryCode} bloqué avec succès",
            'country_code' => $countryCode,
            'country_name' => $countryName,
            'blocked_at'   => $blocked->getBlockedAt()->format('Y-m-d H:i:s'),
        ], 201);
    }

    #[Route('/users/blocked-countries', name: 'blocked_countries', methods: ['GET'])]
    public function listBlockedCountries(BlockedCountryRepository $repo): JsonResponse
    {
        $countries = $repo->findAll();
        $data = array_map(fn($c) => [
            'id'           => $c->getId(),
            'country_code' => $c->getCountryCode(),
            'country_name' => $c->getCountryName(),
            'reason'       => $c->getReason(),
            'blocked_at'   => $c->getBlockedAt()->format('Y-m-d H:i:s'),
        ], $countries);

        return $this->json(['success' => true, 'total' => count($data), 'countries' => $data]);
    }

    #[Route('/users/block-country/{code}', name: 'unblock_country', methods: ['DELETE'])]
    public function unblockCountry(
        string $code,
        BlockedCountryRepository $repo,
        EntityManagerInterface $em
    ): JsonResponse {
        $country = $repo->findOneBy(['countryCode' => strtoupper($code)]);
        if (!$country) {
            return $this->json(['success' => false, 'message' => "Pays {$code} non trouvé"], 404);
        }
        $em->remove($country);
        $em->flush();
        return $this->json(['success' => true, 'message' => "Pays {$code} débloqué avec succès"]);
    }

    #[Route('/users/check-ip/{ip}', name: 'check_ip', methods: ['GET'])]
    public function checkIp(
        string $ip,
        GeoIpService $geoIp,
        BlockedCountryRepository $repo
    ): JsonResponse {
        $location  = $geoIp->getCountryFromIp($ip);
        $isBlocked = $repo->isCountryBlocked($location['country_code']);
        return $this->json([
            'success'      => true,
            'ip'           => $ip,
            'country_code' => $location['country_code'],
            'country_name' => $location['country_name'],
            'is_blocked'   => $isBlocked,
        ]);
    }
}