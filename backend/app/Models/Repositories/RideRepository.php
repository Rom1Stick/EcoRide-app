<?php

namespace App\Models\Repositories;

use App\Models\Entities\Ride;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

/**
 * Classe repository pour les trajets
 */
class RideRepository
{
    /**
     * Nom de la collection MongoDB
     *
     * @var string
     */
    protected string $collection = 'rides';
    
    /**
     * Trouve un trajet par son ID
     *
     * @param string $id ID du trajet
     * @return Ride|null Trajet ou null si non trouvé
     */
    public function findById(string $id): ?Ride
    {
        try {
            $data = DB::collection($this->collection)->where('_id', new ObjectId($id))->first();
            
            if (!$data) {
                return null;
            }
            
            return $this->mapToEntity($data);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du trajet: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Trouve les trajets d'un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param array $options Options de filtrage
     * @return array Liste des trajets
     */
    public function findByUserId(int $userId, array $options = []): array
    {
        try {
            $query = DB::collection($this->collection)->where('userId', $userId);
            
            // Appliquer les filtres
            $this->applyFilters($query, $options);
            
            // Appliquer la pagination
            $limit = $options['limit'] ?? 50;
            $skip = $options['skip'] ?? 0;
            
            // Appliquer le tri
            if (isset($options['sort'])) {
                foreach ($options['sort'] as $field => $order) {
                    $query->orderBy($field, $order === 1 ? 'asc' : 'desc');
                }
            } else {
                // Tri par défaut: date de début décroissante
                $query->orderBy('startTime', 'desc');
            }
            
            $rides = $query->skip($skip)->take($limit)->get();
            
            return array_map([$this, 'mapToEntity'], $rides->toArray());
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des trajets: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Sauvegarde un trajet
     *
     * @param Ride $ride Trajet à sauvegarder
     * @return Ride|null Trajet sauvegardé ou null en cas d'erreur
     */
    public function save(Ride $ride): ?Ride
    {
        try {
            $data = $this->mapToDocument($ride);
            
            if ($ride->id) {
                // Mise à jour
                $id = new ObjectId($ride->id);
                unset($data['_id']); // Éviter de modifier l'ID
                
                DB::collection($this->collection)->where('_id', $id)->update($data);
                
                // Récupérer le trajet mis à jour
                return $this->findById($ride->id);
            } else {
                // Création
                $id = DB::collection($this->collection)->insertGetId($data);
                $ride->id = (string) $id;
                
                return $ride;
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la sauvegarde du trajet: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Supprime un trajet
     *
     * @param string $id ID du trajet à supprimer
     * @return bool Succès de la suppression
     */
    public function delete(string $id): bool
    {
        try {
            $result = DB::collection($this->collection)
                ->where('_id', new ObjectId($id))
                ->delete();
            
            return $result > 0;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression du trajet: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère les statistiques d'un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param array $options Options de filtrage
     * @return array Statistiques de l'utilisateur
     */
    public function getUserStats(int $userId, array $options = []): array
    {
        try {
            $query = DB::collection($this->collection)
                ->where('userId', $userId)
                ->where('status', Ride::STATUS_COMPLETED);
            
            // Appliquer les filtres temporels
            if (isset($options['start_time_from'])) {
                $dateFrom = new UTCDateTime(new DateTime($options['start_time_from']));
                $query->where('startTime', '>=', $dateFrom);
            }
            
            if (isset($options['start_time_to'])) {
                $dateTo = new UTCDateTime(new DateTime($options['start_time_to']));
                $query->where('startTime', '<=', $dateTo);
            }
            
            // Agréger les données
            $stats = $query->get(['distance', 'duration', 'cost']);
            
            // Calculer les statistiques
            $totalDistance = 0;
            $totalDuration = 0;
            $totalCost = 0;
            $rideCount = $stats->count();
            
            foreach ($stats as $ride) {
                $totalDistance += $ride['distance'] ?? 0;
                $totalDuration += $ride['duration'] ?? 0;
                $totalCost += $ride['cost'] ?? 0;
            }
            
            return [
                'total_rides' => $rideCount,
                'total_distance' => $totalDistance,
                'total_duration' => $totalDuration,
                'total_cost' => $totalCost,
                'avg_distance' => $rideCount > 0 ? $totalDistance / $rideCount : 0,
                'avg_duration' => $rideCount > 0 ? $totalDuration / $rideCount : 0,
                'avg_cost' => $rideCount > 0 ? $totalCost / $rideCount : 0
            ];
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des statistiques: ' . $e->getMessage());
            return [
                'total_rides' => 0,
                'total_distance' => 0,
                'total_duration' => 0,
                'total_cost' => 0,
                'avg_distance' => 0,
                'avg_duration' => 0,
                'avg_cost' => 0
            ];
        }
    }
    
    /**
     * Compte le nombre de trajets par statut
     *
     * @param int $userId ID de l'utilisateur
     * @param array $options Options de filtrage
     * @return array Nombre de trajets par statut
     */
    public function countRidesByStatus(int $userId, array $options = []): array
    {
        try {
            $query = DB::collection($this->collection)
                ->where('userId', $userId);
            
            // Appliquer les filtres temporels
            if (isset($options['start_time_from'])) {
                $dateFrom = new UTCDateTime(new DateTime($options['start_time_from']));
                $query->where('startTime', '>=', $dateFrom);
            }
            
            if (isset($options['start_time_to'])) {
                $dateTo = new UTCDateTime(new DateTime($options['start_time_to']));
                $query->where('startTime', '<=', $dateTo);
            }
            
            // Agréger par statut
            $result = $query->raw(function ($collection) {
                return $collection->aggregate([
                    [
                        '$group' => [
                            '_id' => '$status',
                            'count' => ['$sum' => 1]
                        ]
                    ]
                ]);
            });
            
            // Formater le résultat
            $ridesByStatus = [
                Ride::STATUS_PLANNED => 0,
                Ride::STATUS_ONGOING => 0,
                Ride::STATUS_COMPLETED => 0,
                Ride::STATUS_CANCELLED => 0
            ];
            
            foreach ($result as $item) {
                $ridesByStatus[$item->_id] = $item->count;
            }
            
            return $ridesByStatus;
        } catch (\Exception $e) {
            Log::error('Erreur lors du comptage des trajets par statut: ' . $e->getMessage());
            return [
                Ride::STATUS_PLANNED => 0,
                Ride::STATUS_ONGOING => 0,
                Ride::STATUS_COMPLETED => 0,
                Ride::STATUS_CANCELLED => 0
            ];
        }
    }
    
    /**
     * Récupère les trajets récents d'un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param int $limit Nombre de trajets à récupérer
     * @return array Liste des trajets récents
     */
    public function getRecentRides(int $userId, int $limit = 5): array
    {
        try {
            $now = new UTCDateTime(new DateTime());
            
            $rides = DB::collection($this->collection)
                ->where('userId', $userId)
                ->where('endTime', '<=', $now)
                ->where('status', Ride::STATUS_COMPLETED)
                ->orderBy('endTime', 'desc')
                ->take($limit)
                ->get();
            
            return array_map([$this, 'mapToEntity'], $rides->toArray());
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des trajets récents: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère les trajets à venir d'un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @return array Liste des trajets à venir
     */
    public function getUpcomingRides(int $userId): array
    {
        try {
            $now = new UTCDateTime(new DateTime());
            
            $rides = DB::collection($this->collection)
                ->where('userId', $userId)
                ->where('startTime', '>=', $now)
                ->where('status', Ride::STATUS_PLANNED)
                ->orderBy('startTime', 'asc')
                ->get();
            
            return array_map([$this, 'mapToEntity'], $rides->toArray());
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des trajets à venir: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mappe un document MongoDB vers une entité Ride
     *
     * @param array $data Document MongoDB
     * @return Ride Entité Ride
     */
    private function mapToEntity(array $data): Ride
    {
        // Convertir l'ID MongoDB en string
        $id = isset($data['_id']) ? (string) $data['_id'] : null;
        
        // Convertir les dates MongoDB en DateTime
        $startTime = isset($data['startTime']) ? $this->convertMongoDate($data['startTime']) : null;
        $endTime = isset($data['endTime']) ? $this->convertMongoDate($data['endTime']) : null;
        
        // Créer l'entité
        $ride = new Ride(
            $data['userId'],
            $data['vehicleId'],
            $data['startLocation'],
            $data['endLocation'],
            $startTime,
            $data['distance'] ?? 0,
            $data['duration'] ?? 0,
            $data['waypoints'] ?? [],
            $data['metadata'] ?? []
        );
        
        // Définir les propriétés supplémentaires
        $ride->id = $id;
        $ride->status = $data['status'] ?? Ride::STATUS_PLANNED;
        
        if ($endTime) {
            $ride->endTime = $endTime;
        }
        
        if (isset($data['cost'])) {
            $ride->cost = $data['cost'];
        }
        
        return $ride;
    }
    
    /**
     * Mappe une entité Ride vers un document MongoDB
     *
     * @param Ride $ride Entité Ride
     * @return array Document MongoDB
     */
    private function mapToDocument(Ride $ride): array
    {
        $data = [
            'userId' => $ride->userId,
            'vehicleId' => $ride->vehicleId,
            'startLocation' => $ride->startLocation,
            'endLocation' => $ride->endLocation,
            'startTime' => new UTCDateTime($ride->startTime),
            'distance' => $ride->distance,
            'duration' => $ride->duration,
            'status' => $ride->status,
            'waypoints' => $ride->waypoints,
            'metadata' => $ride->metadata
        ];
        
        // Ajouter l'ID si présent
        if ($ride->id) {
            $data['_id'] = new ObjectId($ride->id);
        }
        
        // Ajouter la date de fin si présente
        if ($ride->endTime) {
            $data['endTime'] = new UTCDateTime($ride->endTime);
        }
        
        // Ajouter le coût si présent
        if ($ride->cost) {
            $data['cost'] = $ride->cost;
        }
        
        return $data;
    }
    
    /**
     * Convertit une date MongoDB en DateTime
     *
     * @param mixed $mongoDate Date MongoDB
     * @return DateTime Date PHP
     */
    private function convertMongoDate($mongoDate): DateTime
    {
        if ($mongoDate instanceof UTCDateTime) {
            return $mongoDate->toDateTime();
        }
        
        return new DateTime();
    }
    
    /**
     * Applique les filtres à une requête
     *
     * @param \Illuminate\Database\Query\Builder $query Requête MongoDB
     * @param array $options Options de filtrage
     * @return void
     */
    private function applyFilters(\Illuminate\Database\Query\Builder $query, array $options): void
    {
        // Filtrage par statut
        if (isset($options['status'])) {
            $query->where('status', $options['status']);
        }
        
        // Filtrage par période (date de début)
        if (isset($options['start_time_from'])) {
            $dateFrom = new UTCDateTime(new DateTime($options['start_time_from']));
            $query->where('startTime', '>=', $dateFrom);
        }
        
        if (isset($options['start_time_to'])) {
            $dateTo = new UTCDateTime(new DateTime($options['start_time_to']));
            $query->where('startTime', '<=', $dateTo);
        }
    }
} 