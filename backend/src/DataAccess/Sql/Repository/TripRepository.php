<?php

namespace App\DataAccess\Sql\Repository;

use App\Core\Database;
use App\DataAccess\Exception\DataAccessException;
use App\DataAccess\Sql\Entity\Trip;
use PDO;
use PDOException;
use DateTime;

/**
 * Repository pour gérer les trajets dans la base de données MySQL
 * 
 * Implémente des méthodes optimisées pour la recherche de trajets avec filtres
 * écologiques et de préférences, en respectant les principes d'éco-conception.
 */
class TripRepository extends AbstractRepository implements RepositoryInterface
{
    /**
     * @var string Le nom de la table
     */
    protected string $tableName = 'trips';

    /**
     * Constructeur
     *
     * @param Database $database Instance de connexion à la base de données
     */
    public function __construct(Database $database)
    {
        parent::__construct($database);
    }

    /**
     * Initialise le repository
     * 
     * @return void
     */
    protected function initRepository(): void
    {
        // Initialisation spécifique au repository si nécessaire
    }

    /**
     * Construit une entité Trip à partir des données brutes
     *
     * @param array $data Les données brutes
     * @return Trip L'entité créée
     */
    protected function buildEntity(array $data)
    {
        $trip = new Trip();
        $trip->setId($data['id'])
             ->setDriverId($data['driver_id'])
             ->setVehicleId($data['vehicle_id'])
             ->setDepartureLocation($data['departure_location'])
             ->setArrivalLocation($data['arrival_location'])
             ->setDepartureDateTime(new DateTime($data['departure_datetime']))
             ->setEstimatedArrivalDateTime(new DateTime($data['estimated_arrival_datetime']))
             ->setAvailableSeats($data['available_seats'])
             ->setPrice($data['price'])
             ->setAllowedLuggageSize($data['allowed_luggage_size'])
             ->setStatus($data['status'])
             ->setDescription($data['description'] ?? null)
             ->setEcoRoute($data['eco_route'] ?? false)
             ->setTotalDistance($data['total_distance'] ?? null);
             
        if (isset($data['created_at'])) {
            $trip->setCreatedAt(new DateTime($data['created_at']));
        }
        
        if (isset($data['updated_at'])) {
            $trip->setUpdatedAt(new DateTime($data['updated_at']));
        }
        
        return $trip;
    }

    /**
     * Trouve un trajet par son ID
     *
     * @param int $id L'ID du trajet
     * @return Trip|null Le trajet trouvé ou null
     * @throws DataAccessException En cas d'erreur de base de données
     */
    public function findById(int $id): ?Trip
    {
        try {
            $query = "SELECT * FROM {$this->tableName} WHERE id = :id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $data = $stmt->fetch();
            if (!$data) {
                return null;
            }
            
            return $this->buildEntity($data);
        } catch (PDOException $e) {
            throw new DataAccessException("Erreur lors de la recherche du trajet: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Crée un nouveau trajet dans la base de données
     *
     * @param Trip $trip Le trajet à créer
     * @return int L'ID du trajet créé
     * @throws DataAccessException En cas d'erreur de base de données
     */
    public function create($trip): int
    {
        if (!$trip instanceof Trip) {
            throw new DataAccessException("L'objet doit être une instance de Trip");
        }

        try {
            $query = "INSERT INTO {$this->tableName} (
                driver_id,
                vehicle_id,
                departure_location,
                arrival_location,
                departure_datetime,
                estimated_arrival_datetime,
                available_seats,
                price,
                allowed_luggage_size,
                status,
                description,
                eco_route,
                total_distance,
                created_at,
                updated_at
            ) VALUES (
                :driver_id,
                :vehicle_id,
                :departure_location,
                :arrival_location,
                :departure_datetime,
                :estimated_arrival_datetime,
                :available_seats,
                :price,
                :allowed_luggage_size,
                :status,
                :description,
                :eco_route,
                :total_distance,
                NOW(),
                NOW()
            )";
            
            $stmt = $this->pdo->prepare($query);
            
            $driverId = $trip->getDriverId();
            $vehicleId = $trip->getVehicleId();
            $departureLocation = $trip->getDepartureLocation();
            $arrivalLocation = $trip->getArrivalLocation();
            $departureDateTime = $trip->getDepartureDateTime()->format('Y-m-d H:i:s');
            $estimatedArrivalDateTime = $trip->getEstimatedArrivalDateTime()->format('Y-m-d H:i:s');
            $availableSeats = $trip->getAvailableSeats();
            $price = $trip->getPrice();
            $allowedLuggageSize = $trip->getAllowedLuggageSize();
            $status = $trip->getStatus();
            $description = $trip->getDescription();
            $ecoRoute = $trip->isEcoRoute() ? 1 : 0;
            $totalDistance = $trip->getTotalDistance();
            
            $stmt->bindParam(':driver_id', $driverId, PDO::PARAM_INT);
            $stmt->bindParam(':vehicle_id', $vehicleId, PDO::PARAM_INT);
            $stmt->bindParam(':departure_location', $departureLocation, PDO::PARAM_STR);
            $stmt->bindParam(':arrival_location', $arrivalLocation, PDO::PARAM_STR);
            $stmt->bindParam(':departure_datetime', $departureDateTime, PDO::PARAM_STR);
            $stmt->bindParam(':estimated_arrival_datetime', $estimatedArrivalDateTime, PDO::PARAM_STR);
            $stmt->bindParam(':available_seats', $availableSeats, PDO::PARAM_INT);
            $stmt->bindParam(':price', $price, PDO::PARAM_STR);
            $stmt->bindParam(':allowed_luggage_size', $allowedLuggageSize, PDO::PARAM_STR);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':eco_route', $ecoRoute, PDO::PARAM_BOOL);
            $stmt->bindParam(':total_distance', $totalDistance, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new DataAccessException("Erreur lors de la création du trajet: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Met à jour un trajet existant
     *
     * @param Trip $trip Le trajet à mettre à jour
     * @return bool Succès de la mise à jour
     * @throws DataAccessException En cas d'erreur de base de données
     */
    public function update($trip): bool
    {
        if (!$trip instanceof Trip) {
            throw new DataAccessException("L'objet doit être une instance de Trip");
        }

        try {
            $query = "UPDATE {$this->tableName} SET 
                driver_id = :driver_id,
                vehicle_id = :vehicle_id,
                departure_location = :departure_location,
                arrival_location = :arrival_location,
                departure_datetime = :departure_datetime,
                estimated_arrival_datetime = :estimated_arrival_datetime,
                available_seats = :available_seats,
                price = :price,
                allowed_luggage_size = :allowed_luggage_size,
                status = :status,
                description = :description,
                eco_route = :eco_route,
                total_distance = :total_distance,
                updated_at = NOW()
                WHERE id = :id";
            
            $stmt = $this->pdo->prepare($query);
            
            $id = $trip->getId();
            $driverId = $trip->getDriverId();
            $vehicleId = $trip->getVehicleId();
            $departureLocation = $trip->getDepartureLocation();
            $arrivalLocation = $trip->getArrivalLocation();
            $departureDateTime = $trip->getDepartureDateTime()->format('Y-m-d H:i:s');
            $estimatedArrivalDateTime = $trip->getEstimatedArrivalDateTime()->format('Y-m-d H:i:s');
            $availableSeats = $trip->getAvailableSeats();
            $price = $trip->getPrice();
            $allowedLuggageSize = $trip->getAllowedLuggageSize();
            $status = $trip->getStatus();
            $description = $trip->getDescription();
            $ecoRoute = $trip->isEcoRoute() ? 1 : 0;
            $totalDistance = $trip->getTotalDistance();
            
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':driver_id', $driverId, PDO::PARAM_INT);
            $stmt->bindParam(':vehicle_id', $vehicleId, PDO::PARAM_INT);
            $stmt->bindParam(':departure_location', $departureLocation, PDO::PARAM_STR);
            $stmt->bindParam(':arrival_location', $arrivalLocation, PDO::PARAM_STR);
            $stmt->bindParam(':departure_datetime', $departureDateTime, PDO::PARAM_STR);
            $stmt->bindParam(':estimated_arrival_datetime', $estimatedArrivalDateTime, PDO::PARAM_STR);
            $stmt->bindParam(':available_seats', $availableSeats, PDO::PARAM_INT);
            $stmt->bindParam(':price', $price, PDO::PARAM_STR);
            $stmt->bindParam(':allowed_luggage_size', $allowedLuggageSize, PDO::PARAM_STR);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':eco_route', $ecoRoute, PDO::PARAM_BOOL);
            $stmt->bindParam(':total_distance', $totalDistance, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new DataAccessException("Erreur lors de la mise à jour du trajet: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Supprime un trajet par son ID
     *
     * @param int $id L'ID du trajet à supprimer
     * @return bool Succès de la suppression
     * @throws DataAccessException En cas d'erreur de base de données
     */
    public function delete(int $id): bool
    {
        try {
            $query = "DELETE FROM {$this->tableName} WHERE id = :id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new DataAccessException("Erreur lors de la suppression du trajet: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Recherche des trajets en fonction de critères spécifiques
     * 
     * @param string $departureLocation Lieu de départ
     * @param string $arrivalLocation Lieu d'arrivée
     * @param DateTime $departureDate Date de départ
     * @param int $minSeats Nombre minimum de places
     * @param bool $ecoRouteOnly Ne retourner que les trajets éco-responsables
     * @param int $page Numéro de page
     * @param int $limit Nombre d'éléments par page
     * @return array Liste des trajets
     * @throws DataAccessException En cas d'erreur de base de données
     */
    public function searchTrips(
        string $departureLocation,
        string $arrivalLocation,
        DateTime $departureDate,
        int $minSeats = 1,
        bool $ecoRouteOnly = false,
        int $page = 1,
        int $limit = 20
    ): array {
        $offset = ($page - 1) * $limit;
        
        // Formatage de la date pour la recherche
        $dateString = $departureDate->format('Y-m-d');
        
        try {
            // Construction de la requête avec optimisation pour l'éco-conception
            $query = "SELECT t.* FROM {$this->tableName} t 
                      LEFT JOIN vehicles v ON t.vehicle_id = v.id 
                      WHERE t.departure_location LIKE :departure_location 
                      AND t.arrival_location LIKE :arrival_location 
                      AND DATE(t.departure_datetime) = :departure_date
                      AND t.available_seats >= :min_seats
                      AND t.status = 'active'";
                      
            // Filtre optionnel pour les trajets éco-responsables
            if ($ecoRouteOnly) {
                $query .= " AND (t.eco_route = 1 OR v.eco_score >= 80)";
            }
            
            // Tri optimisé pour favoriser des choix écologiques
            $query .= " ORDER BY 
                        CASE WHEN t.eco_route = 1 THEN 0 ELSE 1 END, 
                        v.eco_score DESC,
                        t.departure_datetime ASC 
                        LIMIT :limit OFFSET :offset";
            
            $stmt = $this->pdo->prepare($query);
            
            // Utilisation de LIKE avec wildcards pour les localisations
            $departureLocationParam = "%$departureLocation%";
            $arrivalLocationParam = "%$arrivalLocation%";
            
            $stmt->bindParam(':departure_location', $departureLocationParam, PDO::PARAM_STR);
            $stmt->bindParam(':arrival_location', $arrivalLocationParam, PDO::PARAM_STR);
            $stmt->bindParam(':departure_date', $dateString, PDO::PARAM_STR);
            $stmt->bindParam(':min_seats', $minSeats, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            
            $trips = [];
            while ($data = $stmt->fetch()) {
                $trips[] = $this->buildEntity($data);
            }
            
            return $trips;
        } catch (PDOException $e) {
            throw new DataAccessException("Erreur lors de la recherche de trajets: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Recherche des trajets par conducteur
     * 
     * @param int $driverId ID du conducteur
     * @param int $page Numéro de page
     * @param int $limit Nombre d'éléments par page
     * @return array Liste des trajets
     * @throws DataAccessException En cas d'erreur de base de données
     */
    public function findByDriver(int $driverId, int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        
        try {
            $query = "SELECT * FROM {$this->tableName} 
                      WHERE driver_id = :driver_id 
                      ORDER BY departure_datetime DESC 
                      LIMIT :limit OFFSET :offset";
                      
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':driver_id', $driverId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $trips = [];
            while ($data = $stmt->fetch()) {
                $trips[] = $this->buildEntity($data);
            }
            
            return $trips;
        } catch (PDOException $e) {
            throw new DataAccessException("Erreur lors de la recherche des trajets par conducteur: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Compte le nombre de places disponibles restantes pour un trajet
     * en tenant compte des réservations existantes
     * 
     * @param int $tripId ID du trajet
     * @return int Nombre de places disponibles
     * @throws DataAccessException En cas d'erreur de base de données
     */
    public function countRemainingSeats(int $tripId): int
    {
        try {
            // Requête optimisée avec JOIN pour une seule requête
            $query = "SELECT t.available_seats - COALESCE(SUM(b.seats), 0) as remaining_seats
                      FROM {$this->tableName} t
                      LEFT JOIN bookings b ON t.id = b.trip_id AND b.status IN ('confirmed', 'pending')
                      WHERE t.id = :trip_id
                      GROUP BY t.id, t.available_seats";
                      
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':trip_id', $tripId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch();
            if (!$result) {
                throw new DataAccessException("Trajet non trouvé avec l'ID: $tripId");
            }
            
            return max(0, (int)$result['remaining_seats']);
        } catch (PDOException $e) {
            throw new DataAccessException("Erreur lors du comptage des places restantes: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Met à jour le statut d'un trajet
     * 
     * @param int $tripId ID du trajet
     * @param string $status Nouveau statut
     * @return bool Succès de la mise à jour
     * @throws DataAccessException En cas d'erreur de base de données
     */
    public function updateStatus(int $tripId, string $status): bool
    {
        try {
            $query = "UPDATE {$this->tableName} SET status = :status, updated_at = NOW() WHERE id = :id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':id', $tripId, PDO::PARAM_INT);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new DataAccessException("Erreur lors de la mise à jour du statut du trajet: " . $e->getMessage(), 0, $e);
        }
    }
} 