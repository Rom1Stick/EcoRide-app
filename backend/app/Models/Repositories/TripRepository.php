<?php

namespace App\Models\Repositories;

use App\Models\Entities\Trip;
use App\Repositories\Interfaces\ITripRepository;
use PDO;

/**
 * Repository pour la gestion des covoiturages
 */
class TripRepository extends BaseRepository implements ITripRepository
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
     * Cette méthode est héritée de BaseRepository et retourne un tableau
     *
     * @param int $id ID du covoiturage
     * @return array|null Covoiturage trouvé ou null
     */
    public function findById(int $id): ?array
    {
        return parent::findById($id);
    }
    
    /**
     * Récupère un covoiturage par son ID et le convertit en objet Trip
     * 
     * @param int $id ID du covoiturage
     * @return Trip|null Covoiturage sous forme d'objet ou null
     */
    public function findTripById(int $id): ?Trip
    {
        $data = $this->findById($id);
        
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
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE statut_id = :status_id ORDER BY date_depart ASC, heure_depart ASC LIMIT :limit OFFSET :offset");
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
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE voiture_id = :vehicle_id ORDER BY date_depart ASC, heure_depart ASC LIMIT :limit OFFSET :offset");
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
     * Récupère tous les covoiturages proposés par un conducteur
     * 
     * @param int $driverId Identifiant du conducteur (propriétaire du véhicule)
     * @return array Liste des covoiturages
     */
    public function findByDriverId(int $driverId): array
    {
        $query = "
            SELECT c.* 
            FROM {$this->table} c
            INNER JOIN Voiture v ON c.voiture_id = v.voiture_id
            WHERE v.utilisateur_id = :driverId
            ORDER BY c.date_depart DESC
        ";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':driverId', $driverId, PDO::PARAM_INT);
        $stmt->execute();
        
        $trips = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $trips[] = Trip::fromArray($row);
        }
        
        return $trips;
    }
    
    /**
     * Récupère les covoiturages dans une plage de dates
     * 
     * @param string $startDate Date de début (format Y-m-d)
     * @param string $endDate Date de fin (format Y-m-d)
     * @return array Liste des covoiturages
     */
    public function findByDateRange(string $startDate, string $endDate): array
    {
        $query = "
            SELECT * 
            FROM {$this->table}
            WHERE DATE(date_depart) BETWEEN :startDate AND :endDate
            ORDER BY date_depart ASC
        ";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':startDate', $startDate, PDO::PARAM_STR);
        $stmt->bindValue(':endDate', $endDate, PDO::PARAM_STR);
        $stmt->execute();
        
        $trips = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $trips[] = Trip::fromArray($row);
        }
        
        return $trips;
    }
    
    /**
     * Récupère les covoiturages pour un trajet spécifique
     * 
     * @param int $departureId Identifiant du lieu de départ
     * @param int $arrivalId Identifiant du lieu d'arrivée
     * @return array Liste des covoiturages
     */
    public function findByRoute(int $departureId, int $arrivalId): array
    {
        $query = "
            SELECT * 
            FROM {$this->table}
            WHERE lieu_depart_id = :departureId 
              AND lieu_arrivee_id = :arrivalId
            ORDER BY date_depart ASC
        ";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':departureId', $departureId, PDO::PARAM_INT);
        $stmt->bindValue(':arrivalId', $arrivalId, PDO::PARAM_INT);
        $stmt->execute();
        
        $trips = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $trips[] = Trip::fromArray($row);
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
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];
        
        // Mapper les clés de ITripRepository vers les noms de colonnes de la base de données
        $mapping = [
            'departure_id' => 'lieu_depart_id',
            'arrival_id' => 'lieu_arrivee_id',
            'date' => 'date_depart',
            'min_seats' => 'nb_place',
            'status_id' => 'statut_id'
        ];
        
        // Convertir les clés des critères
        $convertedCriteria = [];
        foreach ($criteria as $key => $value) {
            $dbKey = $mapping[$key] ?? $key;
            $convertedCriteria[$dbKey] = $value;
        }
        
        if (isset($convertedCriteria['lieu_depart_id'])) {
            $sql .= " AND lieu_depart_id = :lieu_depart_id";
            $params['lieu_depart_id'] = $convertedCriteria['lieu_depart_id'];
        }
        
        if (isset($convertedCriteria['lieu_arrivee_id'])) {
            $sql .= " AND lieu_arrivee_id = :lieu_arrivee_id";
            $params['lieu_arrivee_id'] = $convertedCriteria['lieu_arrivee_id'];
        }
        
        if (isset($convertedCriteria['date_depart'])) {
            $sql .= " AND date_depart = :date_depart";
            $params['date_depart'] = $convertedCriteria['date_depart'];
        }
        
        if (isset($convertedCriteria['date_min'])) {
            $sql .= " AND date_depart >= :date_min";
            $params['date_min'] = $convertedCriteria['date_min'];
        }
        
        if (isset($convertedCriteria['date_max'])) {
            $sql .= " AND date_depart <= :date_max";
            $params['date_max'] = $convertedCriteria['date_max'];
        }
        
        if (isset($convertedCriteria['statut_id'])) {
            $sql .= " AND statut_id = :statut_id";
            $params['statut_id'] = $convertedCriteria['statut_id'];
        }
        
        if (isset($convertedCriteria['nb_place_min'])) {
            $sql .= " AND nb_place >= :nb_place_min";
            $params['nb_place_min'] = $convertedCriteria['nb_place_min'];
        }
        
        $sql .= " ORDER BY date_depart ASC, heure_depart ASC";
        
        if ($limit > 0) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $offset = ($page - 1) * $limit;
            $params['limit'] = $limit;
            $params['offset'] = $offset;
        }
        
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
        if (!($trip instanceof Trip)) {
            throw new \InvalidArgumentException('L\'entité doit être une instance de Trip');
        }
        
        $data = $trip->toArray();
        
        // Supprimer l'ID car il sera généré automatiquement
        if (isset($data['covoiturage_id'])) {
            unset($data['covoiturage_id']);
        }
        
        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$this->table} ($fields) VALUES ($placeholders)";
        
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($data as $key => $value) {
            $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(":$key", $value, $paramType);
        }
        
        $stmt->execute();
        return (int) $this->pdo->lastInsertId();
    }
    
    /**
     * Met à jour un covoiturage existant
     *
     * @param Trip $trip Covoiturage à mettre à jour
     * @return bool Succès de l'opération
     */
    public function update($trip): bool
    {
        if (!($trip instanceof Trip)) {
            throw new \InvalidArgumentException('L\'entité doit être une instance de Trip');
        }
        
        if ($trip->covoiturage_id === null) {
            throw new \InvalidArgumentException('L\'ID du covoiturage doit être défini pour la mise à jour');
        }
        
        $data = $trip->toArray();
        $id = $data['covoiturage_id'];
        unset($data['covoiturage_id']);
        
        $setParts = [];
        foreach (array_keys($data) as $field) {
            $setParts[] = "$field = :$field";
        }
        
        $setClause = implode(', ', $setParts);
        $sql = "UPDATE {$this->table} SET $setClause WHERE {$this->primaryKey} = :id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        foreach ($data as $key => $value) {
            $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(":$key", $value, $paramType);
        }
        
        return $stmt->execute();
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
        $carbonFootprint = $distance * $emissionFactor / ($trip->nb_place + 1); // +1 pour le conducteur
        
        $trip->empreinte_carbone = $carbonFootprint;
        return $this->update($trip);
    }
} 