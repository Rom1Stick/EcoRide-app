<?php

namespace App\DataAccess\NoSql\Model;

/**
 * Modèle pour stocker et analyser les statistiques de réservation
 */
class BookingStats implements \JsonSerializable
{
    /**
     * ID MongoDB
     * 
     * @var string|null
     */
    private ?string $id = null;
    
    /**
     * ID de l'utilisateur concerné
     * 
     * @var int
     */
    private int $userId;
    
    /**
     * Nombre total de réservations effectuées
     * 
     * @var int
     */
    private int $totalBookings = 0;
    
    /**
     * Nombre total de places réservées
     * 
     * @var int
     */
    private int $totalSeatsBooked = 0;
    
    /**
     * Montant total dépensé en réservations
     * 
     * @var float
     */
    private float $totalAmountSpent = 0.0;
    
    /**
     * Économies totales de CO2 (en kg)
     * 
     * @var float
     */
    private float $totalCO2Saved = 0.0;
    
    /**
     * Distance totale parcourue en covoiturage (km)
     * 
     * @var float
     */
    private float $totalDistance = 0.0;
    
    /**
     * Nombre de réservations par statut
     * 
     * @var array
     */
    private array $bookingsByStatus = [
        'pending' => 0,
        'confirmed' => 0,
        'completed' => 0,
        'cancelled' => 0,
        'refunded' => 0
    ];
    
    /**
     * Répartition des réservations par jour de la semaine
     * 
     * @var array
     */
    private array $bookingsByDayOfWeek = [
        'monday' => 0,
        'tuesday' => 0,
        'wednesday' => 0,
        'thursday' => 0,
        'friday' => 0,
        'saturday' => 0,
        'sunday' => 0
    ];
    
    /**
     * Destinations les plus fréquentes
     * 
     * @var array
     */
    private array $topDestinations = [];
    
    /**
     * Historique mensuel des réservations
     * 
     * @var array
     */
    private array $monthlyBookingHistory = [];
    
    /**
     * Date de dernière mise à jour
     * 
     * @var \DateTime
     */
    private \DateTime $updatedAt;
    
    /**
     * Constructeur
     * 
     * @param int $userId ID de l'utilisateur
     */
    public function __construct(int $userId = 0)
    {
        $this->userId = $userId;
        $this->updatedAt = new \DateTime();
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
     * @param string $id Identifiant MongoDB
     * @return self
     */
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }
    
    /**
     * Obtenir l'ID utilisateur
     * 
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }
    
    /**
     * Définir l'ID utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @return self
     */
    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }
    
    /**
     * Obtenir le nombre total de réservations
     * 
     * @return int
     */
    public function getTotalBookings(): int
    {
        return $this->totalBookings;
    }
    
    /**
     * Définir le nombre total de réservations
     * 
     * @param int $totalBookings Nombre total
     * @return self
     */
    public function setTotalBookings(int $totalBookings): self
    {
        $this->totalBookings = $totalBookings;
        return $this;
    }
    
    /**
     * Incrémenter le nombre total de réservations
     * 
     * @param int $count Nombre à ajouter
     * @return self
     */
    public function incrementTotalBookings(int $count = 1): self
    {
        $this->totalBookings += $count;
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
     * @param int $totalSeatsBooked Nombre total
     * @return self
     */
    public function setTotalSeatsBooked(int $totalSeatsBooked): self
    {
        $this->totalSeatsBooked = $totalSeatsBooked;
        return $this;
    }
    
    /**
     * Incrémenter le nombre total de places réservées
     * 
     * @param int $count Nombre à ajouter
     * @return self
     */
    public function incrementTotalSeatsBooked(int $count): self
    {
        $this->totalSeatsBooked += $count;
        return $this;
    }
    
    /**
     * Obtenir le montant total dépensé
     * 
     * @return float
     */
    public function getTotalAmountSpent(): float
    {
        return $this->totalAmountSpent;
    }
    
    /**
     * Définir le montant total dépensé
     * 
     * @param float $totalAmountSpent Montant total
     * @return self
     */
    public function setTotalAmountSpent(float $totalAmountSpent): self
    {
        $this->totalAmountSpent = $totalAmountSpent;
        return $this;
    }
    
    /**
     * Ajouter un montant au total dépensé
     * 
     * @param float $amount Montant à ajouter
     * @return self
     */
    public function addToTotalAmountSpent(float $amount): self
    {
        $this->totalAmountSpent += $amount;
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
     * @param float $totalCO2Saved Total en kg
     * @return self
     */
    public function setTotalCO2Saved(float $totalCO2Saved): self
    {
        $this->totalCO2Saved = $totalCO2Saved;
        return $this;
    }
    
    /**
     * Ajouter au total de CO2 économisé
     * 
     * @param float $co2Amount Quantité à ajouter en kg
     * @return self
     */
    public function addToTotalCO2Saved(float $co2Amount): self
    {
        $this->totalCO2Saved += $co2Amount;
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
     * @param float $totalDistance Distance en km
     * @return self
     */
    public function setTotalDistance(float $totalDistance): self
    {
        $this->totalDistance = $totalDistance;
        return $this;
    }
    
    /**
     * Ajouter à la distance totale parcourue
     * 
     * @param float $distance Distance à ajouter en km
     * @return self
     */
    public function addToTotalDistance(float $distance): self
    {
        $this->totalDistance += $distance;
        return $this;
    }
    
    /**
     * Obtenir les réservations par statut
     * 
     * @return array
     */
    public function getBookingsByStatus(): array
    {
        return $this->bookingsByStatus;
    }
    
    /**
     * Définir les réservations par statut
     * 
     * @param array $bookingsByStatus Tableau de réservations par statut
     * @return self
     */
    public function setBookingsByStatus(array $bookingsByStatus): self
    {
        $this->bookingsByStatus = $bookingsByStatus;
        return $this;
    }
    
    /**
     * Incrémenter le compteur d'un statut
     * 
     * @param string $status Statut à incrémenter
     * @param int $count Nombre à ajouter
     * @return self
     */
    public function incrementStatusCount(string $status, int $count = 1): self
    {
        if (isset($this->bookingsByStatus[$status])) {
            $this->bookingsByStatus[$status] += $count;
        } else {
            $this->bookingsByStatus[$status] = $count;
        }
        
        return $this;
    }
    
    /**
     * Décrémenter le compteur d'un statut et incrémenter un autre
     * 
     * @param string $fromStatus Statut à décrémenter
     * @param string $toStatus Statut à incrémenter
     * @param int $count Nombre à modifier
     * @return self
     */
    public function moveStatusCount(string $fromStatus, string $toStatus, int $count = 1): self
    {
        if (isset($this->bookingsByStatus[$fromStatus]) && $this->bookingsByStatus[$fromStatus] >= $count) {
            $this->bookingsByStatus[$fromStatus] -= $count;
            
            if (isset($this->bookingsByStatus[$toStatus])) {
                $this->bookingsByStatus[$toStatus] += $count;
            } else {
                $this->bookingsByStatus[$toStatus] = $count;
            }
        }
        
        return $this;
    }
    
    /**
     * Obtenir la répartition par jour de la semaine
     * 
     * @return array
     */
    public function getBookingsByDayOfWeek(): array
    {
        return $this->bookingsByDayOfWeek;
    }
    
    /**
     * Définir la répartition par jour de la semaine
     * 
     * @param array $bookingsByDayOfWeek Tableau de répartition
     * @return self
     */
    public function setBookingsByDayOfWeek(array $bookingsByDayOfWeek): self
    {
        $this->bookingsByDayOfWeek = $bookingsByDayOfWeek;
        return $this;
    }
    
    /**
     * Incrémenter le compteur pour un jour de la semaine
     * 
     * @param string $dayOfWeek Jour de la semaine
     * @param int $count Nombre à ajouter
     * @return self
     */
    public function incrementDayOfWeekCount(string $dayOfWeek, int $count = 1): self
    {
        $dayOfWeek = strtolower($dayOfWeek);
        
        if (isset($this->bookingsByDayOfWeek[$dayOfWeek])) {
            $this->bookingsByDayOfWeek[$dayOfWeek] += $count;
        }
        
        return $this;
    }
    
    /**
     * Obtenir les destinations les plus fréquentes
     * 
     * @return array
     */
    public function getTopDestinations(): array
    {
        return $this->topDestinations;
    }
    
    /**
     * Définir les destinations les plus fréquentes
     * 
     * @param array $topDestinations Tableau des destinations
     * @return self
     */
    public function setTopDestinations(array $topDestinations): self
    {
        $this->topDestinations = $topDestinations;
        return $this;
    }
    
    /**
     * Ajouter ou mettre à jour une destination
     * 
     * @param string $destination Nom de la destination
     * @param int $count Nombre à ajouter
     * @return self
     */
    public function addDestination(string $destination, int $count = 1): self
    {
        $found = false;
        
        foreach ($this->topDestinations as &$item) {
            if ($item['destination'] === $destination) {
                $item['count'] += $count;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $this->topDestinations[] = [
                'destination' => $destination,
                'count' => $count
            ];
        }
        
        // Trier et limiter à 10 destinations
        usort($this->topDestinations, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });
        
        $this->topDestinations = array_slice($this->topDestinations, 0, 10);
        
        return $this;
    }
    
    /**
     * Obtenir l'historique mensuel des réservations
     * 
     * @return array
     */
    public function getMonthlyBookingHistory(): array
    {
        return $this->monthlyBookingHistory;
    }
    
    /**
     * Définir l'historique mensuel des réservations
     * 
     * @param array $monthlyBookingHistory Tableau d'historique
     * @return self
     */
    public function setMonthlyBookingHistory(array $monthlyBookingHistory): self
    {
        $this->monthlyBookingHistory = $monthlyBookingHistory;
        return $this;
    }
    
    /**
     * Ajouter une réservation à l'historique mensuel
     * 
     * @param string $yearMonth Format 'YYYY-MM'
     * @param int $count Nombre à ajouter
     * @param float $amount Montant associé
     * @return self
     */
    public function addToMonthlyHistory(string $yearMonth, int $count = 1, float $amount = 0): self
    {
        $found = false;
        
        foreach ($this->monthlyBookingHistory as &$item) {
            if ($item['month'] === $yearMonth) {
                $item['count'] += $count;
                $item['amount'] += $amount;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $this->monthlyBookingHistory[] = [
                'month' => $yearMonth,
                'count' => $count,
                'amount' => $amount
            ];
        }
        
        // Trier par date (du plus récent au plus ancien)
        usort($this->monthlyBookingHistory, function ($a, $b) {
            return strcmp($b['month'], $a['month']);
        });
        
        // Limiter à 24 mois d'historique
        $this->monthlyBookingHistory = array_slice($this->monthlyBookingHistory, 0, 24);
        
        return $this;
    }
    
    /**
     * Obtenir la date de dernière mise à jour
     * 
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }
    
    /**
     * Définir la date de dernière mise à jour
     * 
     * @param \DateTime $updatedAt Date de mise à jour
     * @return self
     */
    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
    
    /**
     * Mettre à jour le timestamp
     * 
     * @return self
     */
    public function updateTimestamp(): self
    {
        $this->updatedAt = new \DateTime();
        return $this;
    }
    
    /**
     * Conversion vers un tableau pour JSON
     * 
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            '_id' => $this->id,
            'user_id' => $this->userId,
            'total_bookings' => $this->totalBookings,
            'total_seats_booked' => $this->totalSeatsBooked,
            'total_amount_spent' => $this->totalAmountSpent,
            'total_co2_saved' => $this->totalCO2Saved,
            'total_distance' => $this->totalDistance,
            'bookings_by_status' => $this->bookingsByStatus,
            'bookings_by_day_of_week' => $this->bookingsByDayOfWeek,
            'top_destinations' => $this->topDestinations,
            'monthly_booking_history' => $this->monthlyBookingHistory,
            'updated_at' => $this->updatedAt->format('c')
        ];
    }
    
    /**
     * Créer une instance à partir d'un document MongoDB
     * 
     * @param array $data Données du document
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $bookingStats = new self();
        
        if (isset($data['_id'])) {
            $bookingStats->setId((string)$data['_id']);
        }
        
        if (isset($data['user_id'])) {
            $bookingStats->setUserId((int)$data['user_id']);
        }
        
        if (isset($data['total_bookings'])) {
            $bookingStats->setTotalBookings((int)$data['total_bookings']);
        }
        
        if (isset($data['total_seats_booked'])) {
            $bookingStats->setTotalSeatsBooked((int)$data['total_seats_booked']);
        }
        
        if (isset($data['total_amount_spent'])) {
            $bookingStats->setTotalAmountSpent((float)$data['total_amount_spent']);
        }
        
        if (isset($data['total_co2_saved'])) {
            $bookingStats->setTotalCO2Saved((float)$data['total_co2_saved']);
        }
        
        if (isset($data['total_distance'])) {
            $bookingStats->setTotalDistance((float)$data['total_distance']);
        }
        
        if (isset($data['bookings_by_status'])) {
            $bookingStats->setBookingsByStatus($data['bookings_by_status']);
        }
        
        if (isset($data['bookings_by_day_of_week'])) {
            $bookingStats->setBookingsByDayOfWeek($data['bookings_by_day_of_week']);
        }
        
        if (isset($data['top_destinations'])) {
            $bookingStats->setTopDestinations($data['top_destinations']);
        }
        
        if (isset($data['monthly_booking_history'])) {
            $bookingStats->setMonthlyBookingHistory($data['monthly_booking_history']);
        }
        
        if (isset($data['updated_at'])) {
            $bookingStats->setUpdatedAt(new \DateTime($data['updated_at']));
        }
        
        return $bookingStats;
    }
} 