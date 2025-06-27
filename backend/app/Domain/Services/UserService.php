<?php

namespace App\Domain\Services;

use App\Domain\Entities\User;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\ValueObjects\Email;
use App\Domain\Exceptions\UnauthorizedException;
use App\Core\Security;

/**
 * Service métier pour la gestion des utilisateurs
 */
class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}
    
    /**
     * Récupère les informations d'un utilisateur par son token JWT
     */
    public function getUserFromToken(string $token): ?User
    {
        $payload = $this->decodeJwtPayload($token);
        if (!$payload || !isset($payload['sub'])) {
            return null;
        }
        
        $userId = (int)$payload['sub'];
        return $this->userRepository->findById($userId);
    }
    
    /**
     * Récupère les informations complètes d'un utilisateur (avec rôles)
     */
    public function getUserProfile(int $userId): array
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new UnauthorizedException('Utilisateur non trouvé');
        }
        
        $roles = $this->userRepository->getUserRoles($userId);
        
        return [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail()->getValue(),
            'username' => $user->getUsername(),
            'photoPath' => $user->getPhotoPath() ?: '/assets/images/Logo_EcoRide.svg',
            'roles' => $roles,
            'isAdmin' => $this->isAdmin($roles),
            'rating' => $user->getRating(),
            'totalRides' => $user->getTotalRides(),
            'joinedAt' => $user->getCreatedAt()->format('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Met à jour le profil d'un utilisateur
     */
    public function updateProfile(int $userId, array $data): User
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new UnauthorizedException('Utilisateur non trouvé');
        }
        
        // Validation des données
        if (isset($data['email'])) {
            $email = new Email($data['email']);
            $user = $user->withEmail($email);
        }
        
        if (isset($data['username'])) {
            $user = $user->withUsername($data['username']);
        }
        
        if (isset($data['photo_path'])) {
            $user = $user->withPhotoPath($data['photo_path']);
        }
        
        // Sauvegarder les modifications
        $this->userRepository->update($user);
        
        return $user;
    }
    
    /**
     * Ajoute un rôle à un utilisateur
     */
    public function addRole(int $userId, string $roleName): bool
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new UnauthorizedException('Utilisateur non trouvé');
        }
        
        // Vérifier si le rôle demandé est autorisé pour l'auto-attribution
        $allowedRoles = ['passager'];
        if (!in_array(strtolower($roleName), $allowedRoles)) {
            throw new UnauthorizedException('Seul le rôle "passager" peut être ajouté automatiquement');
        }
        
        // Vérifier si l'utilisateur a déjà ce rôle
        $existingRoles = $this->userRepository->getUserRoles($userId);
        foreach ($existingRoles as $role) {
            if (strtolower($role['name']) === strtolower($roleName)) {
                return true; // Déjà présent
            }
        }
        
        // Ajouter le rôle
        return $this->userRepository->addRole($userId, $roleName);
    }
    
    /**
     * Soumet une demande de changement de rôle
     */
    public function requestRoleChange(int $userId, int $roleId): bool
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new UnauthorizedException('Utilisateur non trouvé');
        }
        
        // Vérifier s'il y a déjà une demande en attente
        if ($this->userRepository->hasPendingRoleRequest($userId)) {
            throw new UnauthorizedException('Une demande est déjà en cours');
        }
        
        return $this->userRepository->createRoleRequest($userId, $roleId);
    }
    
    /**
     * Récupère les statistiques d'un utilisateur
     */
    public function getUserStats(int $userId): array
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new UnauthorizedException('Utilisateur non trouvé');
        }
        
        return [
            'totalRides' => $user->getTotalRides(),
            'rating' => $user->getRating(),
            'completedRides' => $this->userRepository->getCompletedRidesCount($userId),
            'cancelledRides' => $this->userRepository->getCancelledRidesCount($userId),
            'memberSince' => $user->getCreatedAt()->format('Y-m-d')
        ];
    }
    
    /**
     * Vérifie si l'utilisateur est administrateur
     */
    private function isAdmin(array $roles): bool
    {
        foreach ($roles as $role) {
            if (stripos($role['name'], 'admin') !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Décode le payload d'un token JWT (sans validation de signature)
     */
    private function decodeJwtPayload(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        
        $payloadB64 = $parts[1];
        $payloadJson = base64_decode(str_replace(['-', '_'], ['+', '/'], $payloadB64));
        
        return json_decode($payloadJson, true);
    }
} 