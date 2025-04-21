<?php

namespace App\Repositories\Interfaces;

use App\Models\Documents\ReviewDocument;
use MongoDB\BSON\ObjectId;

/**
 * Interface pour le repository des avis
 */
interface IReviewRepository
{
    /**
     * Trouve un avis par son identifiant MongoDB
     *
     * @param string|ObjectId $id Identifiant de l'avis
     * @return ReviewDocument|null L'avis trouvé ou null si non trouvé
     */
    public function findById($id): ?ReviewDocument;
    
    /**
     * Récupère tous les avis avec pagination
     *
     * @param int $page Numéro de page (commence à 1)
     * @param int $limit Nombre d'éléments par page
     * @return array Liste des avis
     */
    public function findAll(int $page = 1, int $limit = 20): array;
    
    /**
     * Trouve les avis d'un utilisateur spécifique
     *
     * @param int $userId Identifiant de l'utilisateur
     * @param int $page Numéro de page
     * @param int $limit Nombre d'éléments par page
     * @return array Liste des avis de l'utilisateur
     */
    public function findByUserId(int $userId, int $page = 1, int $limit = 20): array;
    
    /**
     * Trouve les avis pour un trajet spécifique
     *
     * @param int $tripId Identifiant du trajet
     * @param int $page Numéro de page
     * @param int $limit Nombre d'éléments par page
     * @return array Liste des avis pour le trajet
     */
    public function findByTripId(int $tripId, int $page = 1, int $limit = 20): array;
    
    /**
     * Trouve l'avis laissé par un utilisateur pour un trajet spécifique
     *
     * @param int $userId Identifiant de l'utilisateur
     * @param int $tripId Identifiant du trajet
     * @return ReviewDocument|null L'avis trouvé ou null si non trouvé
     */
    public function findByUserAndTrip(int $userId, int $tripId): ?ReviewDocument;
    
    /**
     * Calcule la note moyenne pour un trajet
     *
     * @param int $tripId Identifiant du trajet
     * @return float Note moyenne ou 0 si aucun avis
     */
    public function getAverageRatingForTrip(int $tripId): float;
    
    /**
     * Crée un nouvel avis
     *
     * @param ReviewDocument $review L'avis à créer
     * @return string|null Identifiant du nouvel avis ou null en cas d'échec
     */
    public function create(ReviewDocument $review): ?string;
    
    /**
     * Met à jour un avis existant
     *
     * @param ReviewDocument $review L'avis à mettre à jour
     * @return bool Succès de la mise à jour
     */
    public function update(ReviewDocument $review): bool;
    
    /**
     * Supprime un avis par son identifiant
     *
     * @param string|ObjectId $id Identifiant de l'avis à supprimer
     * @return bool Succès de la suppression
     */
    public function delete($id): bool;
    
    /**
     * Change le statut d'un avis
     *
     * @param string|ObjectId $id Identifiant de l'avis
     * @param string $status Nouveau statut
     * @return bool Succès du changement de statut
     */
    public function updateStatus($id, string $status): bool;
    
    /**
     * Compte le nombre total d'avis
     *
     * @return int Nombre total d'avis
     */
    public function count(): int;
    
    /**
     * Compte le nombre d'avis par statut
     *
     * @param string $status Statut des avis à compter
     * @return int Nombre d'avis pour le statut spécifié
     */
    public function countByStatus(string $status): int;
} 