<?php

namespace App\Domain\Repositories;

/**
 * Interface pour la gestion des réservations
 */
interface BookingRepositoryInterface
{
    /**
     * Trouve une réservation par son ID
     */
    public function findById(int $bookingId): ?array;
    
    /**
     * Trouve toutes les réservations d'un utilisateur
     */
    public function findByUserId(int $userId): array;
    
    /**
     * Trouve toutes les réservations d'un trajet
     */
    public function findByRideId(int $rideId): array;
    
    /**
     * Crée une nouvelle réservation
     */
    public function create(array $bookingData): int;
    
    /**
     * Met à jour le statut d'une réservation
     */
    public function updateStatus(int $bookingId, string $status): bool;
    
    /**
     * Supprime une réservation
     */
    public function delete(int $bookingId): bool;
    
    /**
     * Trouve les réservations par statut
     */
    public function findByStatus(string $status): array;
    
    /**
     * Compte le nombre de réservations actives pour un trajet
     */
    public function countActiveBookingsForRide(int $rideId): int;
    
    /**
     * Trouve les réservations d'un utilisateur avec un statut spécifique
     */
    public function findByUserIdAndStatus(int $userId, string $status): array;
    
    /**
     * Vérifie si un utilisateur a déjà réservé un trajet
     */
    public function hasUserBookedRide(int $userId, int $rideId): bool;
    
    /**
     * Récupère l'historique des réservations d'un utilisateur
     */
    public function getUserBookingHistory(int $userId, int $limit = 20): array;
} 