<?php

namespace App\Models\Entities;

use DateTime;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use JsonSerializable;

/**
 * Entité représentant un trajet
 */
class Ride implements JsonSerializable
{
    // Statuts possibles pour un trajet
    public const STATUS_PLANNED = 'planned';
    public const STATUS_ONGOING = 'ongoing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    
    /**
     * ID du trajet
     *
     * @var string|null
     */
    public ?string $id;
    
    /**
     * ID de l'utilisateur
     *
     * @var int
     */
    public int $userId;
    
    /**
     * ID du véhicule
     *
     * @var int
     */
    public int $vehicleId;
    
    /**
     * Emplacement de départ
     *
     * @var string
     */
    public string $startLocation;
    
    /**
     * Emplacement d'arrivée
     *
     * @var string
     */
    public string $endLocation;
    
    /**
     * Heure de départ prévue
     *
     * @var DateTime
     */
    public DateTime $startTime;
    
    /**
     * Heure de départ réelle
     *
     * @var DateTime|null
     */
    public ?DateTime $actualStartTime;
    
    /**
     * Heure d'arrivée
     *
     * @var DateTime|null
     */
    public ?DateTime $endTime;
    
    /**
     * Distance parcourue en kilomètres
     *
     * @var float
     */
    public float $distance;
    
    /**
     * Durée du trajet en secondes
     *
     * @var int
     */
    public int $duration;
    
    /**
     * Coût du trajet
     *
     * @var float
     */
    public float $cost;
    
    /**
     * Statut du trajet
     *
     * @var string
     */
    public string $status;
    
    /**
     * Points de passage
     *
     * @var array
     */
    public array $waypoints;
    
    /**
     * Métadonnées additionnelles
     *
     * @var array
     */
    public array $metadata;
    
    /**
     * Date de création
     *
     * @var DateTime
     */
    public DateTime $createdAt;
    
    /**
     * Date de dernière mise à jour
     *
     * @var DateTime
     */
    public DateTime $updatedAt;
    
    /**
     * Constructeur
     *
     * @param int $userId ID de l'utilisateur
     * @param int $vehicleId ID du véhicule
     * @param string $startLocation Emplacement de départ
     * @param string $endLocation Emplacement d'arrivée
     * @param DateTime $startTime Heure de départ prévue
     * @param float $distance Distance en kilomètres
     * @param int $duration Durée en secondes
     * @param array $waypoints Points de passage (optionnel)
     * @param array $metadata Métadonnées (optionnel)
     * @param string|null $id ID du trajet (optionnel)
     */
    public function __construct(
        int $userId,
        int $vehicleId,
        string $startLocation,
        string $endLocation,
        DateTime $startTime,
        float $distance = 0,
        int $duration = 0,
        array $waypoints = [],
        array $metadata = [],
        ?string $id = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->vehicleId = $vehicleId;
        $this->startLocation = $startLocation;
        $this->endLocation = $endLocation;
        $this->startTime = $startTime;
        $this->actualStartTime = null;
        $this->endTime = null;
        $this->distance = $distance;
        $this->duration = $duration;
        $this->cost = 0;
        $this->status = self::STATUS_PLANNED;
        $this->waypoints = $waypoints;
        $this->metadata = $metadata;
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }
    
    /**
     * Démarre le trajet
     *
     * @param DateTime|null $startTime Heure de départ réelle (optionnel)
     * @return void
     */
    public function start(?DateTime $startTime = null): void
    {
        $this->actualStartTime = $startTime ?? new DateTime();
        $this->status = self::STATUS_ONGOING;
        $this->updatedAt = new DateTime();
    }
    
    /**
     * Termine le trajet
     *
     * @param DateTime|null $endTime Heure d'arrivée (optionnel)
     * @param float|null $distance Distance parcourue (optionnel)
     * @param int|null $duration Durée réelle (optionnel)
     * @return void
     */
    public function complete(?DateTime $endTime = null, ?float $distance = null, ?int $duration = null): void
    {
        $this->endTime = $endTime ?? new DateTime();
        
        if ($distance !== null) {
            $this->distance = $distance;
        }
        
        // Calcule la durée si non fournie
        if ($duration === null && $this->actualStartTime !== null) {
            $this->duration = $this->endTime->getTimestamp() - $this->actualStartTime->getTimestamp();
        } elseif ($duration !== null) {
            $this->duration = $duration;
        }
        
        $this->status = self::STATUS_COMPLETED;
        $this->updatedAt = new DateTime();
    }
    
    /**
     * Annule le trajet
     *
     * @return void
     */
    public function cancel(): void
    {
        $this->status = self::STATUS_CANCELLED;
        $this->updatedAt = new DateTime();
    }
    
    /**
     * Met à jour l'itinéraire
     *
     * @param string $startLocation Nouveau lieu de départ
     * @param string $endLocation Nouveau lieu d'arrivée
     * @param array $waypoints Nouveaux points de passage
     * @return void
     */
    public function updateRoute(string $startLocation, string $endLocation, array $waypoints = []): void
    {
        $this->startLocation = $startLocation;
        $this->endLocation = $endLocation;
        $this->waypoints = $waypoints;
        $this->updatedAt = new DateTime();
    }
    
    /**
     * Calcule le coût du trajet
     *
     * @param float $ratePerKm Tarif par kilomètre
     * @return float Coût calculé
     */
    public function calculateCost(float $ratePerKm = 0.5): float
    {
        $this->cost = $this->distance * $ratePerKm;
        $this->updatedAt = new DateTime();
        
        return $this->cost;
    }
    
    /**
     * Vérifie si le trajet est en cours
     *
     * @return bool
     */
    public function isOngoing(): bool
    {
        return $this->status === self::STATUS_ONGOING;
    }
    
    /**
     * Vérifie si le trajet est terminé
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
    
    /**
     * Vérifie si le trajet est annulé
     *
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }
    
    /**
     * Vérifie si le trajet est planifié
     *
     * @return bool
     */
    public function isPlanned(): bool
    {
        return $this->status === self::STATUS_PLANNED;
    }
    
    /**
     * Ajoute des métadonnées
     *
     * @param string $key Clé
     * @param mixed $value Valeur
     * @return void
     */
    public function addMetadata(string $key, $value): void
    {
        $this->metadata[$key] = $value;
        $this->updatedAt = new DateTime();
    }
    
    /**
     * Convertit les objets DateTime en UTCDateTime pour MongoDB
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = [
            'user_id' => $this->userId,
            'vehicle_id' => $this->vehicleId,
            'start_location' => $this->startLocation,
            'end_location' => $this->endLocation,
            'start_time' => new UTCDateTime($this->startTime),
            'actual_start_time' => $this->actualStartTime ? new UTCDateTime($this->actualStartTime) : null,
            'end_time' => $this->endTime ? new UTCDateTime($this->endTime) : null,
            'distance' => $this->distance,
            'duration' => $this->duration,
            'cost' => $this->cost,
            'status' => $this->status,
            'waypoints' => $this->waypoints,
            'metadata' => $this->metadata,
            'created_at' => new UTCDateTime($this->createdAt),
            'updated_at' => new UTCDateTime($this->updatedAt)
        ];
        
        if ($this->id) {
            $data['_id'] = new ObjectId($this->id);
        }
        
        return $data;
    }
    
    /**
     * Crée une instance Ride depuis un tableau de données
     *
     * @param array $data Données
     * @return Ride
     */
    public static function fromArray(array $data): self
    {
        $id = isset($data['_id']) ? (string) $data['_id'] : null;
        
        $startTime = $data['start_time'] instanceof UTCDateTime
            ? $data['start_time']->toDateTime()
            : new DateTime($data['start_time']);
        
        $ride = new self(
            $data['user_id'],
            $data['vehicle_id'],
            $data['start_location'],
            $data['end_location'],
            $startTime,
            $data['distance'] ?? 0,
            $data['duration'] ?? 0,
            $data['waypoints'] ?? [],
            $data['metadata'] ?? [],
            $id
        );
        
        if (isset($data['actual_start_time'])) {
            $ride->actualStartTime = $data['actual_start_time'] instanceof UTCDateTime
                ? $data['actual_start_time']->toDateTime()
                : new DateTime($data['actual_start_time']);
        }
        
        if (isset($data['end_time'])) {
            $ride->endTime = $data['end_time'] instanceof UTCDateTime
                ? $data['end_time']->toDateTime()
                : new DateTime($data['end_time']);
        }
        
        if (isset($data['cost'])) {
            $ride->cost = $data['cost'];
        }
        
        if (isset($data['status'])) {
            $ride->status = $data['status'];
        }
        
        if (isset($data['created_at'])) {
            $ride->createdAt = $data['created_at'] instanceof UTCDateTime
                ? $data['created_at']->toDateTime()
                : new DateTime($data['created_at']);
        }
        
        if (isset($data['updated_at'])) {
            $ride->updatedAt = $data['updated_at'] instanceof UTCDateTime
                ? $data['updated_at']->toDateTime()
                : new DateTime($data['updated_at']);
        }
        
        return $ride;
    }
    
    /**
     * Implémentation de JsonSerializable
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'vehicle_id' => $this->vehicleId,
            'start_location' => $this->startLocation,
            'end_location' => $this->endLocation,
            'start_time' => $this->startTime->format('Y-m-d H:i:s'),
            'actual_start_time' => $this->actualStartTime ? $this->actualStartTime->format('Y-m-d H:i:s') : null,
            'end_time' => $this->endTime ? $this->endTime->format('Y-m-d H:i:s') : null,
            'distance' => $this->distance,
            'duration' => $this->duration,
            'cost' => $this->cost,
            'status' => $this->status,
            'waypoints' => $this->waypoints,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s')
        ];
    }
} 