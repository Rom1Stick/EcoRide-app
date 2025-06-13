<?php

namespace App\Infrastructure\Repositories;

use App\Domain\ValueObjects\Location;
use App\Infrastructure\Persistence\LocationMapper;
use App\Core\Database\DatabaseInterface;
use App\Core\Logger;
use PDO;
use Exception;

/**
 * Repository MySQL pour la gestion des lieux
 */
class MySQLLocationRepository
{
    private DatabaseInterface $database;
    private Logger $logger;
    private LocationMapper $locationMapper;

    public function __construct(
        DatabaseInterface $database,
        Logger $logger,
        LocationMapper $locationMapper
    ) {
        $this->database = $database;
        $this->logger = $logger;
        $this->locationMapper = $locationMapper;
    }

    /**
     * Trouve un lieu par son ID
     */
    public function findById(int $id): ?Location
    {
        try {
            $sql = "SELECT lieu_id, nom, latitude, longitude FROM Lieu WHERE lieu_id = ?";
            $stmt = $this->database->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return null;
            }

            return $this->locationMapper->mapFromArray($result);

        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la recherche du lieu par ID', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Trouve un lieu par son nom
     */
    public function findByName(string $name): ?Location
    {
        try {
            $sql = "SELECT lieu_id, nom, latitude, longitude FROM Lieu WHERE nom = ? LIMIT 1";
            $stmt = $this->database->prepare($sql);
            $stmt->execute([$name]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return null;
            }

            return $this->locationMapper->mapFromArray($result);

        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la recherche du lieu par nom', [
                'name' => $name,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Recherche des lieux par nom (recherche partielle)
     */
    public function searchByName(string $searchTerm, int $limit = 10): array
    {
        try {
            $sql = "
                SELECT lieu_id, nom, latitude, longitude 
                FROM Lieu 
                WHERE nom LIKE ? 
                ORDER BY nom ASC 
                LIMIT ?
            ";
            $stmt = $this->database->prepare($sql);
            $stmt->execute(['%' . $searchTerm . '%', $limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->locationMapper->mapToLocations($results);

        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la recherche de lieux', [
                'searchTerm' => $searchTerm,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Crée ou trouve un lieu par nom
     */
    public function findOrCreate(string $name): Location
    {
        try {
            // D'abord on essaie de trouver le lieu existant
            $existingLocation = $this->findByName($name);
            if ($existingLocation) {
                return $existingLocation;
            }

            // Si pas trouvé, on le crée
            $sql = "INSERT INTO Lieu (nom) VALUES (?)";
            $stmt = $this->database->prepare($sql);
            $stmt->execute([$name]);
            
            $newId = (int) $this->database->lastInsertId();
            
            $this->logger->info('Nouveau lieu créé', [
                'id' => $newId,
                'name' => $name
            ]);

            return new Location($newId, $name);

        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la création du lieu', [
                'name' => $name,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Met à jour les coordonnées d'un lieu
     */
    public function updateCoordinates(int $locationId, float $latitude, float $longitude): void
    {
        try {
            $sql = "UPDATE Lieu SET latitude = ?, longitude = ? WHERE lieu_id = ?";
            $stmt = $this->database->prepare($sql);
            $stmt->execute([$latitude, $longitude, $locationId]);

            $this->logger->info('Coordonnées du lieu mises à jour', [
                'location_id' => $locationId,
                'latitude' => $latitude,
                'longitude' => $longitude
            ]);

        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la mise à jour des coordonnées', [
                'location_id' => $locationId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Récupère tous les lieux (avec pagination)
     */
    public function findAll(int $page = 1, int $limit = 50): array
    {
        try {
            $offset = ($page - 1) * $limit;
            
            $sql = "
                SELECT lieu_id, nom, latitude, longitude 
                FROM Lieu 
                ORDER BY nom ASC 
                LIMIT ? OFFSET ?
            ";
            $stmt = $this->database->prepare($sql);
            $stmt->execute([$limit, $offset]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->locationMapper->mapToLocations($results);

        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la récupération de tous les lieux', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Compte le nombre total de lieux
     */
    public function count(): int
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM Lieu";
            $stmt = $this->database->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int) $result['total'];

        } catch (Exception $e) {
            $this->logger->error('Erreur lors du comptage des lieux', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Trouve les lieux les plus populaires (les plus utilisés dans les trajets)
     */
    public function findMostPopular(int $limit = 10): array
    {
        try {
            $sql = "
                SELECT 
                    l.lieu_id, 
                    l.nom, 
                    l.latitude, 
                    l.longitude,
                    COUNT(*) as usage_count
                FROM Lieu l
                JOIN (
                    SELECT lieu_depart_id as lieu_id FROM Covoiturage
                    UNION ALL
                    SELECT lieu_arrivee_id as lieu_id FROM Covoiturage
                ) c ON l.lieu_id = c.lieu_id
                GROUP BY l.lieu_id, l.nom, l.latitude, l.longitude
                ORDER BY usage_count DESC
                LIMIT ?
            ";
            $stmt = $this->database->prepare($sql);
            $stmt->execute([$limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->locationMapper->mapToLocations($results);

        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la recherche des lieux populaires', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
} 