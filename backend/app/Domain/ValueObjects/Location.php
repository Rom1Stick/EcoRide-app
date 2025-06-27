<?php

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object représentant un lieu géographique
 */
final class Location
{
    private int $id;
    private string $name;
    private ?float $latitude;
    private ?float $longitude;

    public function __construct(int $id, string $name, ?float $latitude = null, ?float $longitude = null)
    {
        $this->validateId($id);
        $this->validateName($name);
        $this->validateCoordinates($latitude, $longitude);
        
        $this->id = $id;
        $this->name = trim($name);
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    /**
     * Vérifie si le lieu a des coordonnées GPS
     */
    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Calcule la distance avec un autre lieu (en kilomètres)
     * Utilise la formule de Haversine
     */
    public function distanceTo(Location $other): ?float
    {
        if (!$this->hasCoordinates() || !$other->hasCoordinates()) {
            return null;
        }

        $earthRadius = 6371; // Rayon de la Terre en kilomètres

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($other->latitude);
        $lonTo = deg2rad($other->longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos($latFrom) * cos($latTo) *
            sin($lonDelta / 2) * sin($lonDelta / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * Vérifie l'égalité avec un autre lieu
     */
    public function equals(Location $other): bool
    {
        return $this->id === $other->id;
    }

    /**
     * Vérifie si le nom du lieu correspond à une recherche
     */
    public function matchesSearch(string $search): bool
    {
        return stripos($this->name, trim($search)) !== false;
    }

    /**
     * Retourne une représentation sous forme de tableau
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude
        ];
    }

    /**
     * Retourne une représentation sous forme de chaîne
     */
    public function __toString(): string
    {
        return $this->name;
    }

    private function validateId(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('L\'ID du lieu doit être positif');
        }
    }

    private function validateName(string $name): void
    {
        if (empty(trim($name))) {
            throw new InvalidArgumentException('Le nom du lieu ne peut pas être vide');
        }
        
        if (strlen(trim($name)) > 255) {
            throw new InvalidArgumentException('Le nom du lieu ne peut pas dépasser 255 caractères');
        }
    }

    private function validateCoordinates(?float $latitude, ?float $longitude): void
    {
        if (($latitude === null) !== ($longitude === null)) {
            throw new InvalidArgumentException('La latitude et la longitude doivent être définies ensemble ou pas du tout');
        }
        
        if ($latitude !== null && ($latitude < -90 || $latitude > 90)) {
            throw new InvalidArgumentException('La latitude doit être comprise entre -90 et 90');
        }
        
        if ($longitude !== null && ($longitude < -180 || $longitude > 180)) {
            throw new InvalidArgumentException('La longitude doit être comprise entre -180 et 180');
        }
    }
} 