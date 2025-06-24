<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\User;

/**
 * Interface pour la gestion des utilisateurs
 */
interface UserRepositoryInterface
{
    /**
     * Trouve un utilisateur par son ID
     */
    public function findById(int $userId): ?User;
    
    /**
     * Trouve un utilisateur par son email
     */
    public function findByEmail(string $email): ?User;
    
    /**
     * Trouve un utilisateur par son nom d'utilisateur
     */
    public function findByUsername(string $username): ?User;
    
    /**
     * Crée un nouvel utilisateur
     */
    public function create(array $userData): int;
    
    /**
     * Met à jour un utilisateur
     */
    public function update(User $user): bool;
    
    /**
     * Supprime un utilisateur
     */
    public function delete(int $userId): bool;
    
    /**
     * Récupère les rôles d'un utilisateur
     */
    public function getUserRoles(int $userId): array;
    
    /**
     * Ajoute un rôle à un utilisateur
     */
    public function addRole(int $userId, string $roleName): bool;
    
    /**
     * Supprime un rôle d'un utilisateur
     */
    public function removeRole(int $userId, string $roleName): bool;
    
    /**
     * Vérifie si un utilisateur a un rôle spécifique
     */
    public function hasRole(int $userId, string $roleName): bool;
    
    /**
     * Crée une demande de changement de rôle
     */
    public function createRoleRequest(int $userId, int $roleId): bool;
    
    /**
     * Vérifie si un utilisateur a une demande de rôle en attente
     */
    public function hasPendingRoleRequest(int $userId): bool;
    
    /**
     * Récupère le nombre de trajets complétés par un utilisateur
     */
    public function getCompletedRidesCount(int $userId): int;
    
    /**
     * Récupère le nombre de trajets annulés par un utilisateur
     */
    public function getCancelledRidesCount(int $userId): int;
    
    /**
     * Met à jour la note moyenne d'un utilisateur
     */
    public function updateRating(int $userId, float $rating): bool;
    
    /**
     * Met à jour le nombre total de trajets d'un utilisateur
     */
    public function updateTotalRides(int $userId, int $totalRides): bool;
    
    /**
     * Trouve tous les utilisateurs avec pagination
     */
    public function findAll(int $page = 1, int $limit = 20): array;
    
    /**
     * Recherche des utilisateurs par nom ou email
     */
    public function search(string $query, int $limit = 20): array;
} 