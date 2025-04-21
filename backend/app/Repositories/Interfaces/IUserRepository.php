<?php

namespace App\Repositories\Interfaces;

use App\Models\Entities\User;

/**
 * Interface pour le repository des utilisateurs
 * 
 * @extends IRepository<User>
 */
interface IUserRepository extends IRepository
{
    /**
     * Trouve un utilisateur par son email
     *
     * @param string $email Email de l'utilisateur
     * @return User|null L'utilisateur trouvé ou null si non trouvé
     */
    public function findByEmail(string $email): ?User;
    
    /**
     * Trouve un utilisateur par son pseudo
     *
     * @param string $nickname Pseudo de l'utilisateur
     * @return User|null L'utilisateur trouvé ou null si non trouvé
     */
    public function findByNickname(string $nickname): ?User;
    
    /**
     * Recherche des utilisateurs par nom ou prénom
     *
     * @param string $query Termes de recherche
     * @param int $page Numéro de page
     * @param int $limit Nombre d'éléments par page
     * @return array<User> Liste des utilisateurs correspondants
     */
    public function searchByName(string $query, int $page = 1, int $limit = 20): array;
    
    /**
     * Récupère les rôles d'un utilisateur
     *
     * @param int $userId Identifiant de l'utilisateur
     * @return array Tableau des rôles
     */
    public function getUserRoles(int $userId): array;
    
    /**
     * Ajoute un rôle à un utilisateur
     *
     * @param int $userId Identifiant de l'utilisateur
     * @param int $roleId Identifiant du rôle
     * @return bool Succès de l'opération
     */
    public function addUserRole(int $userId, int $roleId): bool;
    
    /**
     * Supprime un rôle d'un utilisateur
     *
     * @param int $userId Identifiant de l'utilisateur
     * @param int $roleId Identifiant du rôle
     * @return bool Succès de l'opération
     */
    public function removeUserRole(int $userId, int $roleId): bool;
    
    /**
     * Vérifie si un utilisateur a un rôle spécifique
     *
     * @param int $userId Identifiant de l'utilisateur
     * @param int $roleId Identifiant du rôle
     * @return bool Vrai si l'utilisateur a le rôle
     */
    public function userHasRole(int $userId, int $roleId): bool;
    
    /**
     * Met à jour la date de dernière connexion d'un utilisateur
     *
     * @param int $userId Identifiant de l'utilisateur
     * @return bool Succès de l'opération
     */
    public function updateLastConnection(int $userId): bool;
} 