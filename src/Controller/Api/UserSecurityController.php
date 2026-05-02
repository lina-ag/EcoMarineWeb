<?php
namespace App\Controller\Api;

use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/users', name: 'api_security_')]
class UserSecurityController extends AbstractController
{
    // 1. Bloquer un utilisateur
    #[Route('/{id}/block', name: 'block_user', methods: ['PUT'])]
    public function blockUser(
        int $id,
        UtilisateurRepository $repo,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $repo->find($id);

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        if ($user->isBlocked()) {
            return $this->json([
                'success' => false,
                'message' => 'Utilisateur déjà bloqué'
            ], 400);
        }

        $user->setIsBlocked(true);
        $user->setBlockedAt(new \DateTime());
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => "L'utilisateur {$user->getPrenom()} {$user->getNom()} a été bloqué",
            'data' => [
                'id'         => $user->getIdUtilisateur(),
                'nom'        => $user->getNom(),
                'prenom'     => $user->getPrenom(),
                'email'      => $user->getEmail(),
                'is_blocked' => true,
                'blocked_at' => $user->getBlockedAt()->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    // 2. Débloquer un utilisateur
    #[Route('/{id}/unblock', name: 'unblock_user', methods: ['PUT'])]
    public function unblockUser(
        int $id,
        UtilisateurRepository $repo,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $repo->find($id);

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        if (!$user->isBlocked()) {
            return $this->json([
                'success' => false,
                'message' => 'Utilisateur déjà débloqué'
            ], 400);
        }

        $user->setIsBlocked(false);
        $user->setBlockedAt(null);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => "L'utilisateur {$user->getPrenom()} {$user->getNom()} a été débloqué",
            'data' => [
                'id'         => $user->getIdUtilisateur(),
                'nom'        => $user->getNom(),
                'prenom'     => $user->getPrenom(),
                'email'      => $user->getEmail(),
                'is_blocked' => false,
            ]
        ]);
    }

    // 3. Liste des utilisateurs bloqués
    #[Route('/blocked', name: 'blocked_users', methods: ['GET'])]
    public function getBlockedUsers(UtilisateurRepository $repo): JsonResponse
    {
        $users = $repo->findBy(['isBlocked' => true]);

        $data = array_map(fn($u) => [
            'id'         => $u->getIdUtilisateur(),
            'nom'        => $u->getNom(),
            'prenom'     => $u->getPrenom(),
            'email'      => $u->getEmail(),
            'blocked_at' => $u->getBlockedAt()?->format('Y-m-d H:i:s'),
        ], $users);

        return $this->json([
            'success' => true,
            'total'   => count($data),
            'data'    => $data
        ]);
    }

    // 4. Statut d'un utilisateur
    #[Route('/{id}/status', name: 'user_status', methods: ['GET'])]
    public function getUserStatus(
        int $id,
        UtilisateurRepository $repo
    ): JsonResponse {
        $user = $repo->find($id);

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        return $this->json([
            'success' => true,
            'data'    => [
                'id'         => $user->getIdUtilisateur(),
                'nom'        => $user->getNom(),
                'prenom'     => $user->getPrenom(),
                'email'      => $user->getEmail(),
                'is_blocked' => $user->isBlocked(),
                'blocked_at' => $user->getBlockedAt()?->format('Y-m-d H:i:s'),
                'role'       => $user->getRoleName(),
            ]
        ]);
    }
}