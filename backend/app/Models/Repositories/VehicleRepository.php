<?php

namespace App\Models\Repositories;

use App\Models\Entities\Vehicle;
use App\Repositories\Interfaces\IVehicleRepository;
use PDO;

/**
 * Repository pour la gestion des véhicules
 */
class VehicleRepository extends BaseRepository implements IVehicleRepository
{
    /**
     * Constructeur
     *
     * @param PDO $pdo Instance de PDO
     */
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo, 'Voiture', 'voiture_id');
    }
    
    /**
     * Récupère tous les véhicules
     * 
     * @param int $page Numéro de page (commence à 1)
     * @param int $limit Nombre d'éléments par page
     * @return array<Vehicle> Liste des véhicules
     */
    public function findAll(int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->tableName} ORDER BY {$this->primaryKey} DESC LIMIT :limit OFFSET :offset");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $vehicles = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $vehicles[] = Vehicle::fromArray($row);
        }
        
        return $vehicles;
    }
    
    /**
     * Récupère un véhicule par son ID
     * 
     * @param int $id ID du véhicule
     * @return Vehicle|null Véhicule trouvé ou null
     */
    public function findById(int $id)
    {
        $result = parent::findById($id);
        
        return $result ? Vehicle::fromArray($result) : null;
    }
    
    /**
     * Trouve un véhicule par son immatriculation
     *
     * @param string $immatriculation Immatriculation du véhicule
     * @return Vehicle|null Le véhicule trouvé ou null si non trouvé
     */
    public function findByImmatriculation(string $immatriculation): ?Vehicle
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->tableName} WHERE immatriculation = :immatriculation LIMIT 1");
        $stmt->bindValue(':immatriculation', $immatriculation, PDO::PARAM_STR);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? Vehicle::fromArray($result) : null;
    }
    
    /**
     * Trouve les véhicules d'un utilisateur spécifique
     *
     * @param int $userId Identifiant de l'utilisateur
     * @param int $page Numéro de page
     * @param int $limit Nombre d'éléments par page
     * @return array<Vehicle> Liste des véhicules de l'utilisateur
     */
    public function findByUserId(int $userId, int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->tableName} WHERE utilisateur_id = :userId ORDER BY {$this->primaryKey} DESC LIMIT :limit OFFSET :offset");
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $vehicles = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $vehicles[] = Vehicle::fromArray($row);
        }
        
        return $vehicles;
    }
    
    /**
     * Trouve les véhicules par type d'énergie
     *
     * @param int $energieId Identifiant du type d'énergie
     * @param int $page Numéro de page
     * @param int $limit Nombre d'éléments par page
     * @return array<Vehicle> Liste des véhicules utilisant ce type d'énergie
     */
    public function findByEnergieId(int $energieId, int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->tableName} WHERE energie_id = :energieId LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':energieId', $energieId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $vehicles = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $vehicles[] = Vehicle::fromArray($row);
        }
        
        return $vehicles;
    }
    
    /**
     * Compte le nombre de véhicules par utilisateur
     *
     * @param int $userId Identifiant de l'utilisateur
     * @return int Nombre de véhicules de l'utilisateur
     */
    public function countByUserId(int $userId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->tableName} WHERE utilisateur_id = :userId");
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Vérifie si une immatriculation existe déjà
     *
     * @param string $registration Immatriculation à vérifier
     * @param int|null $excludeId ID du véhicule à exclure (pour les mises à jour)
     * @return bool True si l'immatriculation existe
     */
    public function registrationExists(string $registration, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->tableName} WHERE immatriculation = :registration";
        $params = [':registration' => $registration];
        
        if ($excludeId !== null) {
            $sql .= " AND {$this->primaryKey} != :excludeId";
            $params[':excludeId'] = $excludeId;
        }
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    }
    
    /**
     * Recherche des véhicules selon différents critères
     *
     * @param int|null $modeleId ID du modèle
     * @param int|null $energieId ID du type d'énergie
     * @param string|null $immatriculation Immatriculation (recherche partielle)
     * @param string|null $couleur Couleur (recherche partielle)
     * @return array<Vehicle> Liste des véhicules correspondants
     */
    public function search(?int $modeleId = null, ?int $energieId = null, ?string $immatriculation = null, ?string $couleur = null): array
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE 1=1";
        $params = [];
        
        if ($modeleId !== null) {
            $sql .= " AND modele_id = :modeleId";
            $params[':modeleId'] = $modeleId;
        }
        
        if ($energieId !== null) {
            $sql .= " AND energie_id = :energieId";
            $params[':energieId'] = $energieId;
        }
        
        if ($immatriculation !== null) {
            $sql .= " AND immatriculation LIKE :immatriculation";
            $params[':immatriculation'] = "%$immatriculation%";
        }
        
        if ($couleur !== null) {
            $sql .= " AND couleur LIKE :couleur";
            $params[':couleur'] = "%$couleur%";
        }
        
        $sql .= " ORDER BY {$this->primaryKey} DESC";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        
        $stmt->execute();
        $vehicles = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $vehicles[] = Vehicle::fromArray($row);
        }
        
        return $vehicles;
    }
    
    /**
     * Crée un véhicule en base de données
     *
     * @param Vehicle $vehicle Véhicule à créer
     * @return int ID du véhicule créé
     */
    public function create($vehicle): int
    {
        $data = $vehicle->toArray();
        unset($data['voiture_id']); // Supprime l'ID pour l'insertion
        
        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$this->tableName} ($fields) VALUES ($placeholders)";
        
        $id = $this->executeInsert($sql, $data);
        if (!$id) {
            throw new \RuntimeException("Échec de la création du véhicule");
        }
        
        return $id;
    }
    
    /**
     * Met à jour un véhicule en base de données
     *
     * @param Vehicle $vehicle Véhicule à mettre à jour
     * @return bool Succès de l'opération
     */
    public function update($vehicle): bool
    {
        if ($vehicle->voiture_id === null) {
            return false;
        }
        
        $data = $vehicle->toArray();
        $id = $data['voiture_id'];
        unset($data['voiture_id']);
        
        $setParts = [];
        foreach (array_keys($data) as $field) {
            $setParts[] = "$field = :$field";
        }
        
        $setClause = implode(', ', $setParts);
        $sql = "UPDATE {$this->tableName} SET $setClause WHERE {$this->primaryKey} = :id";
        
        $data['id'] = $id;
        
        return $this->executeUpdate($sql, $data);
    }
    
    /**
     * Récupère les détails complets d'un véhicule (avec les informations des tables liées)
     *
     * @param int $id ID du véhicule
     * @return array|null Détails du véhicule ou null si non trouvé
     */
    public function getVehicleDetails(int $id): ?array
    {
        $sql = "
            SELECT 
                v.*,
                m.nom AS modele_nom,
                ma.nom AS marque_nom,
                e.nom AS energie_nom,
                u.nom AS utilisateur_nom,
                u.prenom AS utilisateur_prenom
            FROM {$this->tableName} v
            LEFT JOIN Modele m ON v.modele_id = m.modele_id
            LEFT JOIN Marque ma ON m.marque_id = ma.marque_id
            LEFT JOIN Energie e ON v.energie_id = e.energie_id
            LEFT JOIN Utilisateur u ON v.utilisateur_id = u.utilisateur_id
            WHERE v.{$this->primaryKey} = :id
        ";
        
        $result = $this->fetchOne($sql, ['id' => $id]);
        return $result ?: null;
    }
} 