<?php

namespace App\Domain\Repositories;

use App\Domain\ValueObjects\Location;

/**
 * Interface pour la gestion des lieux
 */
interface LocationRepositoryInterface
{
    /**
     * Trouve un lieu par son ID
     */
    public function findById(int $locationId): ?Location;
    
    /**
     * Trouve un lieu par son nom
     */
    public function findByName(string $name): ?Location;
    
    /**
     * Recherche des lieux par nom avec limite
     */
    public function searchByName(string $query, int $limit = 10): array;
    
    /**
     * Crée un nouveau lieu
     */
    public function create(array $locationData): int;
    
    /**
     * Met à jour un lieu
     */
    public function update(Location $location): bool;
    
    /**
     * Supprime un lieu
     */
    public function delete(int $locationId): bool;
    
    /**
     * Récupère les lieux populaires (les plus utilisés)
     */
    public function getPopularLocations(int $limit = 8): array;
    
    /**
     * Récupère les lieux populaires comme points de départ
     */
    public function getPopularDepartureLocations(int $limit = 10): array;
    
    /**
     * Récupère les lieux populaires comme destinations
     */
    public function getPopularDestinationLocations(int $limit = 10): array;
    
    /**
     * Trouve tous les lieux avec pagination
     */
    public function findAll(int $page = 1, int $limit = 20): array;
    
    /**
     * Trouve les lieux dans un rayon géographique
     */
    public function findNearby(float $latitude, float $longitude, float $radiusKm = 50): array;
    
    /**
     * Met à jour les coordonnées d'un lieu
     */
    public function updateCoordinates(int $locationId, float $latitude, float $longitude): bool;
    
    /**
     * Trouve ou crée un lieu par nom
     */
    public function findOrCreate(string $name, ?string $address = null): Location;
    
    /**
     * Compte le nombre total de lieux
     */
    public function count(): int;
} 