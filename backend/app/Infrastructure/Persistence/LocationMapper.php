<?php

namespace App\Infrastructure\Persistence;

use App\Domain\ValueObjects\Location;

/**
 * Mapper pour convertir les données SQL en Value Objects Location
 */
class LocationMapper
{
    /**
     * Convertit des données SQL en Value Object Location
     */
    public function mapToLocation(int $id, string $name, ?float $latitude = null, ?float $longitude = null): Location
    {
        return new Location($id, $name, $latitude, $longitude);
    }

    /**
     * Convertit un tableau de données SQL en Location
     */
    public function mapFromArray(array $data): Location
    {
        return new Location(
            (int) $data['lieu_id'],
            $data['nom'],
            isset($data['latitude']) ? (float) $data['latitude'] : null,
            isset($data['longitude']) ? (float) $data['longitude'] : null
        );
    }

    /**
     * Convertit une Location en tableau pour l'insertion/mise à jour SQL
     */
    public function mapToArray(Location $location): array
    {
        return [
            'lieu_id' => $location->getId(),
            'nom' => $location->getName(),
            'latitude' => $location->getLatitude(),
            'longitude' => $location->getLongitude()
        ];
    }

    /**
     * Convertit plusieurs résultats SQL en array de Locations
     */
    public function mapToLocations(array $results): array
    {
        $locations = [];
        foreach ($results as $result) {
            $locations[] = $this->mapFromArray($result);
        }
        return $locations;
    }
} 