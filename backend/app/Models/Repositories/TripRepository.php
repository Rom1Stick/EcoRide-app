<?php

namespace App\Models\Repositories;

use App\Models\Entities\Trip;
use PDO;

/**
 * Repository pour la gestion des covoiturages
 */
class TripRepository extends BaseRepository
{
    /**
     * TripRepository constructor.
     *
     * @param PDO $pdo Instance de PDO
     */
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo, 'covoiturage', 'covoiturage_id');
    }
    
    /**
     * Récupère tous les covoiturages
     *
     * @param int $page Numéro de page (commence à 1)
     * @param int $limit Nombre d'éléments par page
     * @return Trip[] Tableau de covoiturages
     */
    public function findAll(int $page = 1, int $limit = 20): array
    {
        $data = parent::findAll($page, $limit);
        
        $trips = [];
        foreach ($data as $tripData) {
            $trips[] = Trip::fromArray($tripData);
        }
        
        return $trips;
    }
    
    /**
     * Récupère un covoiturage par son ID
     *
     * @param int $id ID du covoiturage
     * @return Trip|null Covoiturage trouvé ou null
     */
    public function findById(int $id)
    {
        $data = parent::findById($id);
        
        if (!$data) {
            return null;
        }
        
        return Trip::fromArray($data);
    }
    
    /**
     * Récupère les covoiturages par statut
     *
     * @param int $statusId ID du statut
     * @param int $page Numéro de page
     * @param int $limit Nombre d'éléments par page
     * @return Trip[] Tableau de covoiturages
     */
    public function findByStatus(int $statusId, int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->tableName} WHERE statut_id = :status_id ORDER BY date_depart ASC, heure_depart ASC LIMIT :limit OFFSET :offset");
        $stmt->bindParam(':status_id', $statusId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $tripsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $trips = [];
        foreach ($tripsData as $tripData) {
            $trips[] = Trip::fromArray($tripData);
        }
        
        return $trips;
    }
    
    /**
     * Récupère les covoiturages par véhicule
     *
     * @param int $vehicleId ID du véhicule
     * @param int $page Numéro de page
     * @param int $limit Nombre d'éléments par page
     * @return Trip[] Tableau de covoiturages
     */
    public function findByVehicle(int $vehicleId, int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->tableName} WHERE voiture_id = :vehicle_id ORDER BY date_depart ASC, heure_depart ASC LIMIT :limit OFFSET :offset");
        $stmt->bindParam(':vehicle_id', $vehicleId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $tripsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $trips = [];
        foreach ($tripsData as $tripData) {
            $trips[] = Trip::fromArray($tripData);
        }
        
        return $trips;
    }
    
    /**
     * Recherche des covoiturages par critères (recherche avancée)
     *
     * @param array $criteria Critères de recherche (lieu_depart_id, lieu_arrivee_id, date_depart, etc.)
     * @param int $page Numéro de page
     * @param int $limit Nombre d'éléments par page
     * @return Trip[] Tableau de covoiturages
     */
    public function search(array $criteria, int $page = 1, int $limit = 20): array
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE 1=1";
        $params = [];
        
        if (isset($criteria['lieu_depart_id'])) {
            $sql .= " AND lieu_depart_id = :lieu_depart_id";
            $params['lieu_depart_id'] = $criteria['lieu_depart_id'];
        }
        
        if (isset($criteria['lieu_arrivee_id'])) {
            $sql .= " AND lieu_arrivee_id = :lieu_arrivee_id";
            $params['lieu_arrivee_id'] = $criteria['lieu_arrivee_id'];
        }
        
        if (isset($criteria['date_depart'])) {
            $sql .= " AND date_depart = :date_depart";
            $params['date_depart'] = $criteria['date_depart'];
        }
        
        if (isset($criteria['date_min'])) {
            $sql .= " AND date_depart >= :date_min";
            $params['date_min'] = $criteria['date_min'];
        }
        
        if (isset($criteria['date_max'])) {
            $sql .= " AND date_depart <= :date_max";
            $params['date_max'] = $criteria['date_max'];
        }
        
        if (isset($criteria['statut_id'])) {
            $sql .= " AND statut_id = :statut_id";
            $params['statut_id'] = $criteria['statut_id'];
        }
        
        if (isset($criteria['nb_place_min'])) {
            $sql .= " AND nb_place >= :nb_place_min";
            $params['nb_place_min'] = $criteria['nb_place_min'];
        }
        
        $sql .= " ORDER BY date_depart ASC, heure_depart ASC";
        $sql .= " LIMIT :limit OFFSET :offset";
        
        $offset = ($page - 1) * $limit;
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        
        $stmt = $this->pdo->prepare($sql);
        
        // Lier les paramètres avec le bon type
        foreach($params as $key => $value) {
            if ($key === 'limit' || $key === 'offset') {
                $stmt->bindValue(":$key", $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(":$key", $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
        }
        
        $stmt->execute();
        $tripsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $trips = [];
        foreach ($tripsData as $tripData) {
            $trips[] = Trip::fromArray($tripData);
        }
        
        return $trips;
    }
    
    /**
     * Crée un nouveau covoiturage
     *
     * @param Trip $trip Covoiturage à créer
     * @return int L'identifiant du nouveau covoiturage
     */
    public function create($trip): int
    {
        $data = $trip->toArray();
        
        // Supprimer l'ID car il sera généré automatiquement
        if (isset($data['covoiturage_id'])) {
            unset($data['covoiturage_id']);
        }
        
        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$this->tableName} ($fields) VALUES ($placeholders)";
        
        $id = $this->executeInsert($sql, $data);
        if (!$id) {
            throw new \RuntimeException("Échec de la création du covoiturage");
        }
        
        $trip->setId($id);
        return $id;
    }
    
    /**
     * Met à jour un covoiturage existant
     *
     * @param Trip $trip Covoiturage à mettre à jour
     * @return bool Succès de l'opération
     * @throws \Exception Si l'ID n'est pas défini
     */
    public function update($trip): bool
    {
        if ($trip->getId() === null) {
            throw new \RuntimeException("L'ID du covoiturage doit être défini pour la mise à jour");
        }
        
        $data = $trip->toArray();
        $id = $data['covoiturage_id'];
        unset($data['covoiturage_id']);
        
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
     * Supprime un covoiturage par son ID
     *
     * @param int $id ID du covoiturage
     * @return bool Succès de l'opération
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->tableName} WHERE {$this->primaryKey} = :id");
        return $stmt->execute(['id' => $id]);
    }
    
    /**
     * Calcule et met à jour l'empreinte carbone d'un covoiturage
     *
     * @param Trip $trip Covoiturage à mettre à jour
     * @param float $distance Distance en kilomètres
     * @param float $emissionFactor Facteur d'émission en kg CO2/km
     * @return bool Succès de l'opération
     */
    public function updateCarbonFootprint(Trip $trip, float $distance, float $emissionFactor): bool
    {
        // Calcul de l'empreinte carbone: distance * facteur d'émission / nombre de passagers
        $carbonFootprint = $distance * $emissionFactor / ($trip->getAvailableSeats() + 1); // +1 pour le conducteur
        
        $trip->setCarbonFootprint($carbonFootprint);
        return $this->update($trip);
    }
    
    /**
     * Récupère les covoiturages avec des informations détaillées (jointures)
     *
     * @param array $criteria Critères de recherche
     * @param int $page Numéro de page
     * @param int $limit Nombre d'éléments par page
     * @return array Tableau des covoiturages avec détails
     */
    public function findWithDetails(array $criteria = [], int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT c.*, 
                ld.nom as lieu_depart_nom, 
                la.nom as lieu_arrivee_nom,
                s.libelle as statut_libelle,
                v.immatriculation,
                m.marque, m.modele,
                u.nom as conducteur_nom, u.prenom as conducteur_prenom
                FROM {$this->tableName} c
                JOIN lieu ld ON c.lieu_depart_id = ld.lieu_id
                JOIN lieu la ON c.lieu_arrivee_id = la.lieu_id
                JOIN statut s ON c.statut_id = s.statut_id
                JOIN voiture v ON c.voiture_id = v.voiture_id
                JOIN modele m ON v.modele_id = m.modele_id
                JOIN utilisateur u ON v.utilisateur_id = u.utilisateur_id
                WHERE 1=1";
        
        $params = [];
        
        // Ajout des critères de recherche
        if (!empty($criteria)) {
            foreach ($criteria as $key => $value) {
                $sql .= " AND c.$key = :$key";
                $params[$key] = $value;
            }
        }
        
        $sql .= " ORDER BY c.date_depart ASC, c.heure_depart ASC";
        $sql .= " LIMIT :limit OFFSET :offset";
        
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        
        $stmt = $this->pdo->prepare($sql);
        
        // Lier les paramètres avec le bon type
        foreach($params as $key => $value) {
            if ($key === 'limit' || $key === 'offset') {
                $stmt->bindValue(":$key", $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(":$key", $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 