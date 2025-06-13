<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\Ride;
use App\Domain\ValueObjects\Location;
use App\Domain\Entities\User;

/**
 * Interface du repository pour les trajets
 */
interface RideRepositoryInterface
{
    /**
     * Trouve un trajet par son ID
     */
    public function findById(int $id): ?Ride;

    /**
     * Trouve tous les trajets d'un conducteur
     */
    public function findByDriver(User $driver): array;

    /**
     * Recherche des trajets selon des critères
     */
    public function searchRides(
        ?Location $departure = null,
        ?Location $arrival = null,
        ?\DateTime $date = null,
        ?string $sortBy = 'departureTime',
        int $page = 1,
        int $limit = 10
    ): array;

    /**
     * Sauvegarde un trajet
     */
    public function save(Ride $ride): void;

    /**
     * Supprime un trajet
     */
    public function delete(Ride $ride): void;

    /**
     * Compte le nombre total de trajets correspondant aux critères
     */
    public function countSearchResults(
        ?Location $departure = null,
        ?Location $arrival = null,
        ?\DateTime $date = null
    ): int;

    /**
     * Trouve les trajets disponibles pour réservation
     */
    public function findAvailableRides(int $limit = 10): array;

    /**
     * Trouve les trajets populaires (les plus réservés)
     */
    public function findPopularRides(int $limit = 10): array;
} 