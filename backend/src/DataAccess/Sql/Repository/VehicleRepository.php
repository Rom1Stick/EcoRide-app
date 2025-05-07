<?php

namespace App\DataAccess\Sql\Repository;

use App\Core\Database;
use App\DataAccess\Exception\DataAccessException;
use App\DataAccess\Sql\Entity\Vehicle;
use PDO;
use PDOException;

/**
 * Repository pour gérer les véhicules dans la base de données MySQL
 */
class VehicleRepository extends AbstractRepository implements RepositoryInterface
{
    /**
     * @var string Le nom de la table
     */
    protected string $tableName = 'vehicles';

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
     * Construit une entité Vehicle à partir des données brutes
     *
     * @param array $data Les données brutes
     * @return Vehicle L'entité créée
     */
    protected function buildEntity(array $data)
    {
        $vehicle = new Vehicle();
        $vehicle->setId($data['id'])
                ->setUserId($data['user_id'])
                ->setBrand($data['brand'])
                ->setModel($data['model'])
                ->setYear($data['year'])
                ->setLicensePlate($data['license_plate'])
                ->setColor($data['color'])
                ->setEnergyType($data['energy_type'])
                ->setSeats($data['seats'])
                ->setComfortLevel($data['comfort_level'])
                ->setEcoScore($data['eco_score']);
                
        if (isset($data['created_at'])) {
            $vehicle->setCreatedAt(new \DateTime($data['created_at']));
        }
        
        if (isset($data['updated_at'])) {
            $vehicle->setUpdatedAt(new \DateTime($data['updated_at']));
        }
        
        return $vehicle;
    }

    /**
     * Trouve un véhicule par son ID
     *
     * @param int $id L'ID du véhicule
     * @return Vehicle|null Le véhicule trouvé ou null
     * @throws DataAccessException En cas d'erreur de base de données
     */
    public function findById(int $id): ?Vehicle
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
            throw new DataAccessException("Erreur lors de la recherche du véhicule: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Crée un nouveau véhicule dans la base de données
     *
     * @param Vehicle $vehicle Le véhicule à créer
     * @return int L'ID du véhicule créé
     * @throws DataAccessException En cas d'erreur de base de données
     */
    public function create($vehicle): int
    {
        if (!$vehicle instanceof Vehicle) {
            throw new DataAccessException("L'objet doit être une instance de Vehicle");
        }

        try {
            $query = "INSERT INTO {$this->tableName} (
                user_id, 
                brand, 
                model, 
                year, 
                license_plate, 
                color, 
                energy_type, 
                seats,
                comfort_level,
                eco_score,
                created_at,
                updated_at
            ) VALUES (
                :user_id, 
                :brand, 
                :model, 
                :year, 
                :license_plate, 
                :color, 
                :energy_type, 
                :seats,
                :comfort_level,
                :eco_score,
                NOW(),
                NOW()
            )";
            
            $stmt = $this->pdo->prepare($query);
            
            $userId = $vehicle->getUserId();
            $brand = $vehicle->getBrand();
            $model = $vehicle->getModel();
            $year = $vehicle->getYear();
            $licensePlate = $vehicle->getLicensePlate();
            $color = $vehicle->getColor();
            $energyType = $vehicle->getEnergyType();
            $seats = $vehicle->getSeats();
            $comfortLevel = $vehicle->getComfortLevel();
            $ecoScore = $vehicle->getEcoScore();
            
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':brand', $brand, PDO::PARAM_STR);
            $stmt->bindParam(':model', $model, PDO::PARAM_STR);
            $stmt->bindParam(':year', $year, PDO::PARAM_INT);
            $stmt->bindParam(':license_plate', $licensePlate, PDO::PARAM_STR);
            $stmt->bindParam(':color', $color, PDO::PARAM_STR);
            $stmt->bindParam(':energy_type', $energyType, PDO::PARAM_STR);
            $stmt->bindParam(':seats', $seats, PDO::PARAM_INT);
            $stmt->bindParam(':comfort_level', $comfortLevel, PDO::PARAM_STR);
            $stmt->bindParam(':eco_score', $ecoScore, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new DataAccessException("Erreur lors de la création du véhicule: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Met à jour un véhicule existant
     *
     * @param Vehicle $vehicle Le véhicule à mettre à jour
     * @return bool Succès de la mise à jour
     * @throws DataAccessException En cas d'erreur de base de données
     */
    public function update($vehicle): bool
    {
        if (!$vehicle instanceof Vehicle) {
            throw new DataAccessException("L'objet doit être une instance de Vehicle");
        }

        try {
            $query = "UPDATE {$this->tableName} SET 
                brand = :brand, 
                model = :model, 
                year = :year, 
                license_plate = :license_plate, 
                color = :color, 
                energy_type = :energy_type, 
                seats = :seats,
                comfort_level = :comfort_level,
                eco_score = :eco_score,
                updated_at = NOW()
                WHERE id = :id";
            
            $stmt = $this->pdo->prepare($query);
            
            $id = $vehicle->getId();
            $brand = $vehicle->getBrand();
            $model = $vehicle->getModel();
            $year = $vehicle->getYear();
            $licensePlate = $vehicle->getLicensePlate();
            $color = $vehicle->getColor();
            $energyType = $vehicle->getEnergyType();
            $seats = $vehicle->getSeats();
            $comfortLevel = $vehicle->getComfortLevel();
            $ecoScore = $vehicle->getEcoScore();
            
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':brand', $brand, PDO::PARAM_STR);
            $stmt->bindParam(':model', $model, PDO::PARAM_STR);
            $stmt->bindParam(':year', $year, PDO::PARAM_INT);
            $stmt->bindParam(':license_plate', $licensePlate, PDO::PARAM_STR);
            $stmt->bindParam(':color', $color, PDO::PARAM_STR);
            $stmt->bindParam(':energy_type', $energyType, PDO::PARAM_STR);
            $stmt->bindParam(':seats', $seats, PDO::PARAM_INT);
            $stmt->bindParam(':comfort_level', $comfortLevel, PDO::PARAM_STR);
            $stmt->bindParam(':eco_score', $ecoScore, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new DataAccessException("Erreur lors de la mise à jour du véhicule: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Supprime un véhicule par son ID
     *
     * @param int $id L'ID du véhicule à supprimer
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
            throw new DataAccessException("Erreur lors de la suppression du véhicule: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Trouve tous les véhicules d'un utilisateur
     *
     * @param int $userId L'ID de l'utilisateur
     * @return array Liste des véhicules
     * @throws DataAccessException En cas d'erreur de base de données
     */
    public function findByUserId(int $userId): array
    {
        try {
            $query = "SELECT * FROM {$this->tableName} WHERE user_id = :user_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $vehicles = [];
            while ($data = $stmt->fetch()) {
                $vehicles[] = $this->buildEntity($data);
            }
            
            return $vehicles;
        } catch (PDOException $e) {
            throw new DataAccessException("Erreur lors de la recherche des véhicules par utilisateur: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Trouve les véhicules par type d'énergie
     *
     * @param string $energyType Le type d'énergie (électrique, hybride, essence, etc.)
     * @param int $page Numéro de page
     * @param int $limit Nombre d'éléments par page
     * @return array Liste des véhicules
     * @throws DataAccessException En cas d'erreur de base de données
     */
    public function findByEnergyType(string $energyType, int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        
        try {
            $query = "SELECT * FROM {$this->tableName} WHERE energy_type = :energy_type LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':energy_type', $energyType, PDO::PARAM_STR);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $vehicles = [];
            while ($data = $stmt->fetch()) {
                $vehicles[] = $this->buildEntity($data);
            }
            
            return $vehicles;
        } catch (PDOException $e) {
            throw new DataAccessException("Erreur lors de la recherche des véhicules par type d'énergie: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Trouve les véhicules par score éco supérieur à la valeur spécifiée
     *
     * @param int $minScore Score éco minimum
     * @param int $page Numéro de page
     * @param int $limit Nombre d'éléments par page
     * @return array Liste des véhicules
     * @throws DataAccessException En cas d'erreur de base de données
     */
    public function findByMinEcoScore(int $minScore, int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        
        try {
            $query = "SELECT * FROM {$this->tableName} WHERE eco_score >= :min_score LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':min_score', $minScore, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $vehicles = [];
            while ($data = $stmt->fetch()) {
                $vehicles[] = $this->buildEntity($data);
            }
            
            return $vehicles;
        } catch (PDOException $e) {
            throw new DataAccessException("Erreur lors de la recherche des véhicules par score éco: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Compte le nombre de véhicules par type d'énergie
     *
     * @param string $energyType Le type d'énergie
     * @return int Nombre de véhicules
     * @throws DataAccessException En cas d'erreur de base de données
     */
    public function countByEnergyType(string $energyType): int
    {
        try {
            $query = "SELECT COUNT(*) FROM {$this->tableName} WHERE energy_type = :energy_type";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':energy_type', $energyType, PDO::PARAM_STR);
            $stmt->execute();
            
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new DataAccessException("Erreur lors du comptage des véhicules par type d'énergie: " . $e->getMessage(), 0, $e);
        }
    }
} 