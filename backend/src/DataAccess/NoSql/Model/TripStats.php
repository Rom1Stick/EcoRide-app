<?php

namespace App\DataAccess\NoSql\Model;

use DateTime;
use JsonSerializable;

/**
 * Modèle pour les statistiques de trajets dans MongoDB
 */
class TripStats implements JsonSerializable
{
    /**
     * Identifiant MongoDB
     *
     * @var string|null
     */
    private ?string $id = null;

    /**
     * Identifiant du conducteur
     *
     * @var int
     */
    private int $driverId = 0;

    /**
     * Nombre total de trajets
     *
     * @var int
     */
    private int $totalTrips = 0;

    /**
     * Nombre total de places proposées
     *
     * @var int
     */
    private int $totalSeatsOffered = 0;

    /**
     * Nombre total de places réservées
     *
     * @var int
     */
    private int $totalSeatsBooked = 0;

    /**
     * Taux moyen de remplissage (0-1)
     *
     * @var float
     */
    private float $averageOccupancyRate = 0.0;

    /**
     * Montant total gagné
     *
     * @var float
     */
    private float $totalEarnings = 0.0;

    /**
     * Distance totale parcourue en km
     *
     * @var float
     */
    private float $totalDistance = 0.0;

    /**
     * Durée totale des trajets en minutes
     *
     * @var int
     */
    private int $totalDuration = 0;

    /**
     * Quantité totale de CO2 économisée
     *
     * @var float
     */
    private float $totalCO2Saved = 0.0;

    /**
     * Nombre de trajets par statut
     *
     * @var array
     */
    private array $tripsByStatus = [
        'scheduled' => 0,
        'active' => 0,
        'completed' => 0,
        'cancelled' => 0
    ];

    /**
     * Nombre de trajets par jour de la semaine
     *
     * @var array
     */
    private array $tripsByDayOfWeek = [
        'monday' => 0,
        'tuesday' => 0,
        'wednesday' => 0,
        'thursday' => 0,
        'friday' => 0,
        'saturday' => 0,
        'sunday' => 0
    ];

    /**
     * Villes d'origine les plus fréquentes
     * Format: ['city' => 'Paris', 'count' => 5]
     *
     * @var array
     */
    private array $topOrigins = [];

    /**
     * Villes de destination les plus fréquentes
     * Format: ['city' => 'Lyon', 'count' => 3]
     *
     * @var array
     */
    private array $topDestinations = [];

    /**
     * Historique mensuel des trajets
     * Format: ['2023-01' => ['count' => 5, 'earnings' => 120.50]]
     *
     * @var array
     */
    private array $monthlyTripHistory = [];

    /**
     * Date de dernière mise à jour
     *
     * @var DateTime
     */
    private DateTime $updatedAt;

    /**
     * Constructeur
     *
     * @param int $driverId ID du conducteur (optionnel)
     */
    public function __construct(int $driverId = 0)
    {
        $this->driverId = $driverId;
        $this->updatedAt = new DateTime();
    }

    /**
     * Obtenir l'ID
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Définir l'ID
     *
     * @param string $id
     * @return self
     */
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Obtenir l'ID du conducteur
     *
     * @return int
     */
    public function getDriverId(): int
    {
        return $this->driverId;
    }

    /**
     * Définir l'ID du conducteur
     *
     * @param int $driverId
     * @return self
     */
    public function setDriverId(int $driverId): self
    {
        $this->driverId = $driverId;
        return $this;
    }

    /**
     * Obtenir le nombre total de trajets
     *
     * @return int
     */
    public function getTotalTrips(): int
    {
        return $this->totalTrips;
    }

    /**
     * Définir le nombre total de trajets
     *
     * @param int $totalTrips
     * @return self
     */
    public function setTotalTrips(int $totalTrips): self
    {
        $this->totalTrips = $totalTrips;
        return $this;
    }

    /**
     * Incrémenter le nombre total de trajets
     *
     * @param int $count
     * @return self
     */
    public function incrementTotalTrips(int $count = 1): self
    {
        $this->totalTrips += $count;
        return $this;
    }

    /**
     * Obtenir le nombre total de places offertes
     *
     * @return int
     */
    public function getTotalSeatsOffered(): int
    {
        return $this->totalSeatsOffered;
    }

    /**
     * Définir le nombre total de places offertes
     *
     * @param int $totalSeatsOffered
     * @return self
     */
    public function setTotalSeatsOffered(int $totalSeatsOffered): self
    {
        $this->totalSeatsOffered = $totalSeatsOffered;
        return $this;
    }

    /**
     * Ajouter des places offertes au total
     *
     * @param int $seats
     * @return self
     */
    public function addSeatsOffered(int $seats): self
    {
        $this->totalSeatsOffered += $seats;
        return $this;
    }

    /**
     * Obtenir le nombre total de places réservées
     *
     * @return int
     */
    public function getTotalSeatsBooked(): int
    {
        return $this->totalSeatsBooked;
    }

    /**
     * Définir le nombre total de places réservées
     *
     * @param int $totalSeatsBooked
     * @return self
     */
    public function setTotalSeatsBooked(int $totalSeatsBooked): self
    {
        $this->totalSeatsBooked = $totalSeatsBooked;
        return $this;
    }

    /**
     * Ajouter des places réservées au total
     *
     * @param int $seats
     * @return self
     */
    public function addSeatsBooked(int $seats): self
    {
        $this->totalSeatsBooked += $seats;
        // Recalculer le taux d'occupation moyen
        if ($this->totalSeatsOffered > 0) {
            $this->averageOccupancyRate = $this->totalSeatsBooked / $this->totalSeatsOffered;
        }
        return $this;
    }

    /**
     * Obtenir le taux moyen d'occupation
     *
     * @return float
     */
    public function getAverageOccupancyRate(): float
    {
        return $this->averageOccupancyRate;
    }

    /**
     * Définir le taux moyen d'occupation
     *
     * @param float $averageOccupancyRate
     * @return self
     */
    public function setAverageOccupancyRate(float $averageOccupancyRate): self
    {
        $this->averageOccupancyRate = $averageOccupancyRate;
        return $this;
    }

    /**
     * Obtenir le montant total des gains
     *
     * @return float
     */
    public function getTotalEarnings(): float
    {
        return $this->totalEarnings;
    }

    /**
     * Définir le montant total des gains
     *
     * @param float $totalEarnings
     * @return self
     */
    public function setTotalEarnings(float $totalEarnings): self
    {
        $this->totalEarnings = $totalEarnings;
        return $this;
    }

    /**
     * Ajouter un montant aux gains totaux
     *
     * @param float $amount
     * @return self
     */
    public function addToEarnings(float $amount): self
    {
        $this->totalEarnings += $amount;
        return $this;
    }

    /**
     * Obtenir la distance totale parcourue
     *
     * @return float
     */
    public function getTotalDistance(): float
    {
        return $this->totalDistance;
    }

    /**
     * Définir la distance totale parcourue
     *
     * @param float $totalDistance
     * @return self
     */
    public function setTotalDistance(float $totalDistance): self
    {
        $this->totalDistance = $totalDistance;
        return $this;
    }

    /**
     * Ajouter une distance au total
     *
     * @param float $distance
     * @return self
     */
    public function addToDistance(float $distance): self
    {
        $this->totalDistance += $distance;
        return $this;
    }

    /**
     * Obtenir la durée totale des trajets
     *
     * @return int
     */
    public function getTotalDuration(): int
    {
        return $this->totalDuration;
    }

    /**
     * Définir la durée totale des trajets
     *
     * @param int $totalDuration
     * @return self
     */
    public function setTotalDuration(int $totalDuration): self
    {
        $this->totalDuration = $totalDuration;
        return $this;
    }

    /**
     * Ajouter une durée au total
     *
     * @param int $duration
     * @return self
     */
    public function addToDuration(int $duration): self
    {
        $this->totalDuration += $duration;
        return $this;
    }

    /**
     * Obtenir le total de CO2 économisé
     *
     * @return float
     */
    public function getTotalCO2Saved(): float
    {
        return $this->totalCO2Saved;
    }

    /**
     * Définir le total de CO2 économisé
     *
     * @param float $totalCO2Saved
     * @return self
     */
    public function setTotalCO2Saved(float $totalCO2Saved): self
    {
        $this->totalCO2Saved = $totalCO2Saved;
        return $this;
    }

    /**
     * Ajouter un montant de CO2 économisé
     *
     * @param float $co2Amount
     * @return self
     */
    public function addToCO2Saved(float $co2Amount): self
    {
        $this->totalCO2Saved += $co2Amount;
        return $this;
    }

    /**
     * Obtenir le comptage de trajets par statut
     *
     * @return array
     */
    public function getTripsByStatus(): array
    {
        return $this->tripsByStatus;
    }

    /**
     * Définir le comptage de trajets par statut
     *
     * @param array $tripsByStatus
     * @return self
     */
    public function setTripsByStatus(array $tripsByStatus): self
    {
        $this->tripsByStatus = $tripsByStatus;
        return $this;
    }

    /**
     * Incrémenter le compteur pour un statut
     *
     * @param string $status
     * @param int $count
     * @return self
     */
    public function incrementStatusCount(string $status, int $count = 1): self
    {
        if (!isset($this->tripsByStatus[$status])) {
            $this->tripsByStatus[$status] = 0;
        }
        $this->tripsByStatus[$status] += $count;
        return $this;
    }

    /**
     * Déplacer le comptage d'un statut à un autre
     *
     * @param string $fromStatus
     * @param string $toStatus
     * @param int $count
     * @return self
     */
    public function moveStatusCount(string $fromStatus, string $toStatus, int $count = 1): self
    {
        if (!isset($this->tripsByStatus[$fromStatus])) {
            $this->tripsByStatus[$fromStatus] = 0;
        }
        if (!isset($this->tripsByStatus[$toStatus])) {
            $this->tripsByStatus[$toStatus] = 0;
        }
        
        // S'assurer que nous ne soustrayons pas plus que disponible
        $count = min($count, $this->tripsByStatus[$fromStatus]);
        
        $this->tripsByStatus[$fromStatus] -= $count;
        $this->tripsByStatus[$toStatus] += $count;
        
        return $this;
    }

    /**
     * Obtenir le comptage de trajets par jour de la semaine
     *
     * @return array
     */
    public function getTripsByDayOfWeek(): array
    {
        return $this->tripsByDayOfWeek;
    }

    /**
     * Définir le comptage de trajets par jour de la semaine
     *
     * @param array $tripsByDayOfWeek
     * @return self
     */
    public function setTripsByDayOfWeek(array $tripsByDayOfWeek): self
    {
        $this->tripsByDayOfWeek = $tripsByDayOfWeek;
        return $this;
    }

    /**
     * Incrémenter le compteur pour un jour de la semaine
     *
     * @param string $dayOfWeek
     * @param int $count
     * @return self
     */
    public function incrementDayOfWeekCount(string $dayOfWeek, int $count = 1): self
    {
        $dayOfWeek = strtolower($dayOfWeek);
        if (isset($this->tripsByDayOfWeek[$dayOfWeek])) {
            $this->tripsByDayOfWeek[$dayOfWeek] += $count;
        }
        return $this;
    }

    /**
     * Obtenir les principales villes d'origine
     *
     * @return array
     */
    public function getTopOrigins(): array
    {
        return $this->topOrigins;
    }

    /**
     * Définir les principales villes d'origine
     *
     * @param array $topOrigins
     * @return self
     */
    public function setTopOrigins(array $topOrigins): self
    {
        $this->topOrigins = $topOrigins;
        return $this;
    }

    /**
     * Ajouter une ville d'origine au comptage
     *
     * @param string $origin
     * @param int $count
     * @return self
     */
    public function addOrigin(string $origin, int $count = 1): self
    {
        $found = false;
        
        // Mettre à jour si la ville existe déjà
        foreach ($this->topOrigins as $key => $entry) {
            if ($entry['city'] === $origin) {
                $this->topOrigins[$key]['count'] += $count;
                $found = true;
                break;
            }
        }
        
        // Ajouter la ville si elle n'existe pas
        if (!$found) {
            $this->topOrigins[] = [
                'city' => $origin,
                'count' => $count
            ];
        }
        
        // Trier par nombre décroissant
        usort($this->topOrigins, function($a, $b) {
            return $b['count'] <=> $a['count'];
        });
        
        return $this;
    }

    /**
     * Obtenir les principales destinations
     *
     * @return array
     */
    public function getTopDestinations(): array
    {
        return $this->topDestinations;
    }

    /**
     * Définir les principales destinations
     *
     * @param array $topDestinations
     * @return self
     */
    public function setTopDestinations(array $topDestinations): self
    {
        $this->topDestinations = $topDestinations;
        return $this;
    }

    /**
     * Ajouter une destination au comptage
     *
     * @param string $destination
     * @param int $count
     * @return self
     */
    public function addDestination(string $destination, int $count = 1): self
    {
        $found = false;
        
        // Mettre à jour si la ville existe déjà
        foreach ($this->topDestinations as $key => $entry) {
            if ($entry['city'] === $destination) {
                $this->topDestinations[$key]['count'] += $count;
                $found = true;
                break;
            }
        }
        
        // Ajouter la ville si elle n'existe pas
        if (!$found) {
            $this->topDestinations[] = [
                'city' => $destination,
                'count' => $count
            ];
        }
        
        // Trier par nombre décroissant
        usort($this->topDestinations, function($a, $b) {
            return $b['count'] <=> $a['count'];
        });
        
        return $this;
    }

    /**
     * Obtenir l'historique mensuel des trajets
     *
     * @return array
     */
    public function getMonthlyTripHistory(): array
    {
        return $this->monthlyTripHistory;
    }

    /**
     * Définir l'historique mensuel des trajets
     *
     * @param array $monthlyTripHistory
     * @return self
     */
    public function setMonthlyTripHistory(array $monthlyTripHistory): self
    {
        $this->monthlyTripHistory = $monthlyTripHistory;
        return $this;
    }

    /**
     * Ajouter une entrée à l'historique mensuel
     *
     * @param string $yearMonth Format 'YYYY-MM'
     * @param int $count Nombre de trajets
     * @param float $earnings Montant gagné
     * @return self
     */
    public function addToMonthlyHistory(string $yearMonth, int $count = 1, float $earnings = 0): self
    {
        // Ajouter ou mettre à jour le mois
        if (!isset($this->monthlyTripHistory[$yearMonth])) {
            $this->monthlyTripHistory[$yearMonth] = [
                'count' => $count,
                'earnings' => $earnings
            ];
        } else {
            $this->monthlyTripHistory[$yearMonth]['count'] += $count;
            $this->monthlyTripHistory[$yearMonth]['earnings'] += $earnings;
        }
        
        // Trier par clé (date) en ordre décroissant
        krsort($this->monthlyTripHistory);
        
        return $this;
    }

    /**
     * Obtenir la date de dernière mise à jour
     *
     * @return DateTime
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Définir la date de dernière mise à jour
     *
     * @param DateTime $updatedAt
     * @return self
     */
    public function setUpdatedAt(DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Mettre à jour l'horodatage
     *
     * @return self
     */
    public function updateTimestamp(): self
    {
        $this->updatedAt = new DateTime();
        return $this;
    }

    /**
     * Sérialiser l'objet pour JSON
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            '_id' => $this->id,
            'driver_id' => $this->driverId,
            'total_trips' => $this->totalTrips,
            'total_seats_offered' => $this->totalSeatsOffered,
            'total_seats_booked' => $this->totalSeatsBooked,
            'average_occupancy_rate' => $this->averageOccupancyRate,
            'total_earnings' => $this->totalEarnings,
            'total_distance' => $this->totalDistance,
            'total_duration' => $this->totalDuration,
            'total_co2_saved' => $this->totalCO2Saved,
            'trips_by_status' => $this->tripsByStatus,
            'trips_by_day_of_week' => $this->tripsByDayOfWeek,
            'top_origins' => $this->topOrigins,
            'top_destinations' => $this->topDestinations,
            'monthly_trip_history' => $this->monthlyTripHistory,
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Créer une instance à partir d'un tableau
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $stats = new self();
        
        if (isset($data['_id'])) {
            $stats->setId((string)$data['_id']);
        }
        
        if (isset($data['driver_id'])) {
            $stats->setDriverId((int)$data['driver_id']);
        }
        
        if (isset($data['total_trips'])) {
            $stats->setTotalTrips((int)$data['total_trips']);
        }
        
        if (isset($data['total_seats_offered'])) {
            $stats->setTotalSeatsOffered((int)$data['total_seats_offered']);
        }
        
        if (isset($data['total_seats_booked'])) {
            $stats->setTotalSeatsBooked((int)$data['total_seats_booked']);
        }
        
        if (isset($data['average_occupancy_rate'])) {
            $stats->setAverageOccupancyRate((float)$data['average_occupancy_rate']);
        }
        
        if (isset($data['total_earnings'])) {
            $stats->setTotalEarnings((float)$data['total_earnings']);
        }
        
        if (isset($data['total_distance'])) {
            $stats->setTotalDistance((float)$data['total_distance']);
        }
        
        if (isset($data['total_duration'])) {
            $stats->setTotalDuration((int)$data['total_duration']);
        }
        
        if (isset($data['total_co2_saved'])) {
            $stats->setTotalCO2Saved((float)$data['total_co2_saved']);
        }
        
        if (isset($data['trips_by_status']) && is_array($data['trips_by_status'])) {
            $stats->setTripsByStatus($data['trips_by_status']);
        }
        
        if (isset($data['trips_by_day_of_week']) && is_array($data['trips_by_day_of_week'])) {
            $stats->setTripsByDayOfWeek($data['trips_by_day_of_week']);
        }
        
        if (isset($data['top_origins']) && is_array($data['top_origins'])) {
            $stats->setTopOrigins($data['top_origins']);
        }
        
        if (isset($data['top_destinations']) && is_array($data['top_destinations'])) {
            $stats->setTopDestinations($data['top_destinations']);
        }
        
        if (isset($data['monthly_trip_history']) && is_array($data['monthly_trip_history'])) {
            $stats->setMonthlyTripHistory($data['monthly_trip_history']);
        }
        
        if (isset($data['updated_at'])) {
            $stats->setUpdatedAt(new DateTime($data['updated_at']));
        }
        
        return $stats;
    }
} 