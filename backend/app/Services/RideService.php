<?php

namespace App\Services;

use App\Models\Entities\Ride;
use App\Models\Repositories\RideRepository;
use DateTime;
use Exception;

/**
 * Service de gestion des trajets
 */
class RideService
{
    private RideRepository $rideRepository;
    
    /**
     * Constructeur
     *
     * @param RideRepository $rideRepository Repository des trajets
     */
    public function __construct(RideRepository $rideRepository)
    {
        $this->rideRepository = $rideRepository;
    }
    
    /**
     * Crée un nouveau trajet
     *
     * @param int $userId ID de l'utilisateur
     * @param int $vehicleId ID du véhicule
     * @param string $startLocation Position de départ
     * @param string $endLocation Position d'arrivée
     * @param string|DateTime $startTime Heure de départ (chaîne ou objet DateTime)
     * @param array $waypoints Points de passage (optionnel)
     * @param array $metadata Métadonnées (optionnel)
     * @return Ride Trajet créé
     */
    public function createRide(
        int $userId,
        int $vehicleId,
        string $startLocation,
        string $endLocation,
        $startTime,
        array $waypoints = [],
        array $metadata = []
    ): Ride {
        // Convertit startTime en DateTime si nécessaire
        if (!$startTime instanceof DateTime) {
            $startTime = new DateTime($startTime);
        }
        
        // Crée un nouveau trajet
        $ride = new Ride(
            $userId,
            $vehicleId,
            $startLocation,
            $endLocation,
            $startTime,
            0, // Distance sera calculée plus tard
            0, // Durée sera calculée plus tard
            $waypoints,
            $metadata
        );
        
        // Sauvegarde le trajet
        return $this->rideRepository->save($ride);
    }
    
    /**
     * Récupère un trajet par son ID
     *
     * @param string $rideId ID du trajet
     * @return Ride|null Trajet trouvé ou null si non trouvé
     * @throws Exception Si le trajet n'existe pas
     */
    public function getRideById(string $rideId): Ride
    {
        $ride = $this->rideRepository->findById($rideId);
        
        if (!$ride) {
            throw new Exception("Trajet non trouvé avec l'ID: $rideId");
        }
        
        return $ride;
    }
    
    /**
     * Démarre un trajet
     *
     * @param string $rideId ID du trajet
     * @param DateTime|null $startTime Heure de début (optionnel)
     * @return Ride Trajet mis à jour
     * @throws Exception Si le trajet n'existe pas ou est déjà en cours
     */
    public function startRide(string $rideId, ?DateTime $startTime = null): Ride
    {
        $ride = $this->getRideById($rideId);
        
        if ($ride->isOngoing()) {
            throw new Exception("Le trajet est déjà en cours");
        }
        
        if ($ride->isCompleted()) {
            throw new Exception("Le trajet est déjà terminé");
        }
        
        if ($ride->isCancelled()) {
            throw new Exception("Le trajet est annulé et ne peut pas être démarré");
        }
        
        // Démarre le trajet
        $ride->start($startTime);
        
        // Sauvegarde les modifications
        return $this->rideRepository->save($ride);
    }
    
    /**
     * Termine un trajet
     *
     * @param string $rideId ID du trajet
     * @param float $distance Distance parcourue en kilomètres
     * @param DateTime|null $endTime Heure de fin (optionnel)
     * @param int|null $duration Durée en secondes (optionnel)
     * @param float|null $customRatePerKm Tarif personnalisé par kilomètre (optionnel)
     * @return Ride Trajet mis à jour
     * @throws Exception Si le trajet n'existe pas ou n'est pas en cours
     */
    public function completeRide(
        string $rideId,
        float $distance,
        ?DateTime $endTime = null,
        ?int $duration = null,
        ?float $customRatePerKm = null
    ): Ride {
        $ride = $this->getRideById($rideId);
        
        if (!$ride->isOngoing()) {
            throw new Exception("Le trajet n'est pas en cours et ne peut pas être terminé");
        }
        
        // Termine le trajet
        $ride->complete($endTime, $distance, $duration);
        
        // Calcule le coût
        $ratePerKm = $customRatePerKm ?? 0.5; // Tarif par défaut: 0.5€/km
        $ride->calculateCost($ratePerKm);
        
        // Sauvegarde les modifications
        return $this->rideRepository->save($ride);
    }
    
    /**
     * Annule un trajet
     *
     * @param string $rideId ID du trajet
     * @return Ride Trajet mis à jour
     * @throws Exception Si le trajet n'existe pas ou est déjà terminé
     */
    public function cancelRide(string $rideId): Ride
    {
        $ride = $this->getRideById($rideId);
        
        if ($ride->isCompleted()) {
            throw new Exception("Le trajet est déjà terminé et ne peut pas être annulé");
        }
        
        if ($ride->isCancelled()) {
            throw new Exception("Le trajet est déjà annulé");
        }
        
        // Annule le trajet
        $ride->cancel();
        
        // Sauvegarde les modifications
        return $this->rideRepository->save($ride);
    }
    
    /**
     * Met à jour l'itinéraire d'un trajet
     *
     * @param string $rideId ID du trajet
     * @param string $startLocation Nouvelle position de départ
     * @param string $endLocation Nouvelle position d'arrivée
     * @param array $waypoints Nouveaux points de passage
     * @return Ride Trajet mis à jour
     * @throws Exception Si le trajet n'existe pas ou est déjà terminé/annulé
     */
    public function updateRideRoute(
        string $rideId,
        string $startLocation,
        string $endLocation,
        array $waypoints = []
    ): Ride {
        $ride = $this->getRideById($rideId);
        
        if ($ride->isCompleted()) {
            throw new Exception("Le trajet est déjà terminé et ne peut pas être modifié");
        }
        
        if ($ride->isCancelled()) {
            throw new Exception("Le trajet est annulé et ne peut pas être modifié");
        }
        
        // Met à jour l'itinéraire
        $ride->updateRoute($startLocation, $endLocation, $waypoints);
        
        // Sauvegarde les modifications
        return $this->rideRepository->save($ride);
    }
    
    /**
     * Récupère les trajets d'un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param array $options Options de filtrage et pagination
     * @return array Trajets trouvés
     */
    public function getUserRides(int $userId, array $options = []): array
    {
        return $this->rideRepository->findByUserId($userId, $options);
    }
    
    /**
     * Récupère les trajets d'un véhicule
     *
     * @param int $vehicleId ID du véhicule
     * @param array $options Options de filtrage et pagination
     * @return array Trajets trouvés
     */
    public function getVehicleRides(int $vehicleId, array $options = []): array
    {
        return $this->rideRepository->findByVehicleId($vehicleId, $options);
    }
    
    /**
     * Recherche de trajets selon des critères
     *
     * @param array $criteria Critères de recherche
     * @param array $options Options de tri et pagination
     * @return array Trajets correspondants
     */
    public function searchRides(array $criteria, array $options = []): array
    {
        return $this->rideRepository->search($criteria, $options);
    }
    
    /**
     * Supprime un trajet
     *
     * @param string $rideId ID du trajet
     * @return bool Succès de la suppression
     */
    public function deleteRide(string $rideId): bool
    {
        return $this->rideRepository->delete($rideId);
    }
    
    /**
     * Calcule les statistiques d'un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param array $options Options de filtrage
     * @return array Statistiques
     */
    public function getUserStats(int $userId, array $options = []): array
    {
        return $this->rideRepository->getUserStats($userId, $options);
    }
    
    /**
     * Récupère la distance totale parcourue par un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param array $options Options de filtrage
     * @return float Distance totale en kilomètres
     */
    public function getTotalUserDistance(int $userId, array $options = []): float
    {
        return $this->rideRepository->calculateTotalDistanceByUser($userId, $options);
    }
    
    /**
     * Calcule l'empreinte carbone pour un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param array $options Options de filtrage
     * @return float Empreinte carbone en kg de CO2
     */
    public function calculateCarbonFootprint(int $userId, array $options = []): float
    {
        // Récupère les statistiques de l'utilisateur
        $stats = $this->getUserStats($userId, $options);
        
        // Facteur d'émission moyen pour un véhicule électrique (en kg CO2/km)
        // Note: cette valeur peut varier selon les pays et le mix énergétique
        $emissionFactor = 0.024;
        
        // Calcul de l'empreinte carbone en kg de CO2
        $carbonFootprint = $stats['total_distance'] * $emissionFactor;
        
        return $carbonFootprint;
    }
    
    /**
     * Calcule les économies de CO2 par rapport à un véhicule thermique
     * 
     * @param int $userId ID de l'utilisateur
     * @param array $options Options de filtrage
     * @return float Économies en kg de CO2
     */
    public function calculateCO2Savings(int $userId, array $options = []): float
    {
        // Récupère la distance totale
        $totalDistance = $this->getTotalUserDistance($userId, $options);
        
        // Facteur d'émission moyen pour un véhicule électrique (en kg CO2/km)
        $electricEmissionFactor = 0.024;
        
        // Facteur d'émission moyen pour un véhicule thermique (en kg CO2/km)
        $gasEmissionFactor = 0.17;
        
        // Calcul des émissions pour chaque type de véhicule
        $electricEmissions = $totalDistance * $electricEmissionFactor;
        $gasEmissions = $totalDistance * $gasEmissionFactor;
        
        // Calcul des économies
        $savings = $gasEmissions - $electricEmissions;
        
        return $savings;
    }
} 