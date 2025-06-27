<?php

namespace App\Domain\Services;

use App\Domain\Repositories\LocationRepositoryInterface;
use App\Domain\ValueObjects\Location;

/**
 * Service métier pour la gestion des lieux
 */
class LocationService
{
    private const MIN_SEARCH_LENGTH = 2;
    private const MAX_SEARCH_RESULTS = 10;
    private const POPULAR_LOCATIONS_LIMIT = 8;
    
    public function __construct(
        private LocationRepositoryInterface $locationRepository
    ) {}
    
    /**
     * Recherche des lieux par nom
     */
    public function searchLocations(string $query): array
    {
        if (empty($query) || strlen($query) < self::MIN_SEARCH_LENGTH) {
            return $this->getPopularLocations();
        }
        
        $results = $this->locationRepository->searchByName($query, self::MAX_SEARCH_RESULTS);
        
        // Trier les résultats par pertinence
        return $this->sortLocationsByRelevance($results, $query);
    }
    
    /**
     * Récupère les lieux populaires
     */
    public function getPopularLocations(): array
    {
        $locations = $this->locationRepository->getPopularLocations(self::POPULAR_LOCATIONS_LIMIT);
        
        // Si pas assez de lieux populaires, compléter avec des lieux par défaut
        if (count($locations) < self::POPULAR_LOCATIONS_LIMIT) {
            $locations = array_merge($locations, $this->getDefaultLocations());
            $locations = array_slice($locations, 0, self::POPULAR_LOCATIONS_LIMIT);
        }
        
        return $locations;
    }
    
    /**
     * Récupère ou crée un lieu
     */
    public function getOrCreateLocation(string $name, ?string $address = null): Location
    {
        // Chercher d'abord si le lieu existe
        $existingLocation = $this->locationRepository->findByName($name);
        if ($existingLocation) {
            return $existingLocation;
        }
        
        // Créer le lieu s'il n'existe pas
        $locationData = [
            'name' => $name,
            'address' => $address ?: $name,
            'latitude' => null,
            'longitude' => null
        ];
        
        $locationId = $this->locationRepository->create($locationData);
        
        return new Location(
            $locationId,
            $name,
            $address ?: $name,
            null,
            null
        );
    }
    
    /**
     * Récupère les détails d'un lieu
     */
    public function getLocationDetails(int $locationId): ?Location
    {
        return $this->locationRepository->findById($locationId);
    }
    
    /**
     * Met à jour les coordonnées d'un lieu
     */
    public function updateLocationCoordinates(int $locationId, float $latitude, float $longitude): bool
    {
        $location = $this->locationRepository->findById($locationId);
        if (!$location) {
            return false;
        }
        
        $updatedLocation = new Location(
            $location->getId(),
            $location->getName(),
            $location->getAddress(),
            $latitude,
            $longitude
        );
        
        return $this->locationRepository->update($updatedLocation);
    }
    
    /**
     * Calcule la distance entre deux lieux
     */
    public function calculateDistance(int $fromLocationId, int $toLocationId): ?float
    {
        $fromLocation = $this->locationRepository->findById($fromLocationId);
        $toLocation = $this->locationRepository->findById($toLocationId);
        
        if (!$fromLocation || !$toLocation) {
            return null;
        }
        
        return $fromLocation->calculateDistanceTo($toLocation);
    }
    
    /**
     * Récupère les lieux les plus utilisés comme points de départ
     */
    public function getPopularDepartureLocations(int $limit = 10): array
    {
        return $this->locationRepository->getPopularDepartureLocations($limit);
    }
    
    /**
     * Récupère les lieux les plus utilisés comme destinations
     */
    public function getPopularDestinationLocations(int $limit = 10): array
    {
        return $this->locationRepository->getPopularDestinationLocations($limit);
    }
    
    /**
     * Trie les lieux par pertinence par rapport à la recherche
     */
    private function sortLocationsByRelevance(array $locations, string $query): array
    {
        $query = strtolower($query);
        
        usort($locations, function($a, $b) use ($query) {
            $nameA = strtolower($a['name']);
            $nameB = strtolower($b['name']);
            
            // Correspondance exacte d'abord
            if ($nameA === $query) return -1;
            if ($nameB === $query) return 1;
            
            // Puis les noms qui commencent par la recherche
            $startsWithA = strpos($nameA, $query) === 0;
            $startsWithB = strpos($nameB, $query) === 0;
            
            if ($startsWithA && !$startsWithB) return -1;
            if ($startsWithB && !$startsWithA) return 1;
            
            // Finalement tri alphabétique
            return strcmp($nameA, $nameB);
        });
        
        return $locations;
    }
    
    /**
     * Lieux par défaut si aucun lieu populaire n'est trouvé
     */
    private function getDefaultLocations(): array
    {
        return [
            ['id' => 1, 'name' => 'Paris'],
            ['id' => 2, 'name' => 'Lyon'],
            ['id' => 3, 'name' => 'Marseille'],
            ['id' => 4, 'name' => 'Bordeaux'],
            ['id' => 5, 'name' => 'Lille'],
            ['id' => 6, 'name' => 'Strasbourg'],
            ['id' => 7, 'name' => 'Nantes'],
            ['id' => 8, 'name' => 'Toulouse']
        ];
    }
} 