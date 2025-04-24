<?php

namespace App\DataAccess\Sql\Repository;

use App\DataAccess\Sql\Entity\Booking;
use App\DataAccess\Sql\Entity\Trip;
use App\DataAccess\Exception\DataAccessException;
use App\DataAccess\Sql\AbstractRepository;

/**
 * Repository pour gérer les réservations dans la base de données
 */
class BookingRepository extends AbstractRepository
{
    /**
     * Nom de la table
     *
     * @var string
     */
    protected string $table = 'bookings';

    /**
     * Crée une nouvelle réservation
     *
     * @param Booking $booking Objet réservation
     * @return Booking Réservation avec ID
     * @throws DataAccessException
     */
    public function create(Booking $booking): Booking
    {
        $data = [
            'passenger_id' => $booking->getPassengerId(),
            'trip_id' => $booking->getTripId(),
            'seat_count' => $booking->getSeatCount(),
            'total_price' => $booking->getTotalPrice(),
            'status' => $booking->getStatus(),
            'payment_method' => $booking->getPaymentMethod(),
            'payment_transaction_id' => $booking->getPaymentTransactionId(),
            'payment_confirmed_at' => $booking->getPaymentConfirmedAt() ? 
                $booking->getPaymentConfirmedAt()->format('Y-m-d H:i:s') : null,
            'has_luggage' => $booking->hasLuggage() ? 1 : 0,
            'passenger_notes' => $booking->getPassengerNotes(),
            'pickup_location' => $booking->getPickupLocation(),
            'dropoff_location' => $booking->getDropoffLocation(),
            'custom_pickup_time' => $booking->getCustomPickupTime() ? 
                $booking->getCustomPickupTime()->format('Y-m-d H:i:s') : null,
            'co2_saved' => $booking->getCo2Saved(),
            'created_at' => $booking->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $booking->getUpdatedAt()->format('Y-m-d H:i:s')
        ];

        $bookingId = $this->insert($data);
        $booking->setId($bookingId);

        return $booking;
    }

    /**
     * Met à jour une réservation existante
     *
     * @param Booking $booking Réservation à mettre à jour
     * @return bool Succès
     * @throws DataAccessException
     */
    public function update(Booking $booking): bool
    {
        if ($booking->getId() === null) {
            throw new DataAccessException("Impossible de mettre à jour une réservation sans ID");
        }

        $booking->updateTimestamp();

        $data = [
            'passenger_id' => $booking->getPassengerId(),
            'trip_id' => $booking->getTripId(),
            'seat_count' => $booking->getSeatCount(),
            'total_price' => $booking->getTotalPrice(),
            'status' => $booking->getStatus(),
            'payment_method' => $booking->getPaymentMethod(),
            'payment_transaction_id' => $booking->getPaymentTransactionId(),
            'payment_confirmed_at' => $booking->getPaymentConfirmedAt() ? 
                $booking->getPaymentConfirmedAt()->format('Y-m-d H:i:s') : null,
            'has_luggage' => $booking->hasLuggage() ? 1 : 0,
            'passenger_notes' => $booking->getPassengerNotes(),
            'pickup_location' => $booking->getPickupLocation(),
            'dropoff_location' => $booking->getDropoffLocation(),
            'custom_pickup_time' => $booking->getCustomPickupTime() ? 
                $booking->getCustomPickupTime()->format('Y-m-d H:i:s') : null,
            'co2_saved' => $booking->getCo2Saved(),
            'updated_at' => $booking->getUpdatedAt()->format('Y-m-d H:i:s')
        ];

        return $this->updateById($booking->getId(), $data);
    }

    /**
     * Trouve une réservation par son ID
     *
     * @param int $id ID de la réservation
     * @return Booking|null Réservation ou null
     * @throws DataAccessException
     */
    public function findById(int $id): ?Booking
    {
        $bookingData = $this->fetchById($id);
        
        if (!$bookingData) {
            return null;
        }
        
        return $this->hydrateBooking($bookingData);
    }

    /**
     * Trouve toutes les réservations d'un passager
     *
     * @param int $passengerId ID du passager
     * @param int $limit Limite de résultats
     * @param int $offset Décalage pour la pagination
     * @return array Réservations
     * @throws DataAccessException
     */
    public function findByPassengerId(int $passengerId, int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE passenger_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $results = $this->query($sql, [$passengerId, $limit, $offset]);
        
        $bookings = [];
        foreach ($results as $bookingData) {
            $bookings[] = $this->hydrateBooking($bookingData);
        }
        
        return $bookings;
    }

    /**
     * Trouve les réservations actives d'un passager (non annulées et non terminées)
     *
     * @param int $passengerId ID du passager
     * @return array Réservations actives
     * @throws DataAccessException
     */
    public function findActiveBookingsByPassengerId(int $passengerId): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE passenger_id = ? 
                AND status IN ('pending', 'confirmed') 
                ORDER BY created_at DESC";
                
        $results = $this->query($sql, [$passengerId]);
        
        $bookings = [];
        foreach ($results as $bookingData) {
            $bookings[] = $this->hydrateBooking($bookingData);
        }
        
        return $bookings;
    }

    /**
     * Trouve toutes les réservations pour un trajet
     *
     * @param int $tripId ID du trajet
     * @return array Réservations
     * @throws DataAccessException
     */
    public function findByTripId(int $tripId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE trip_id = ? ORDER BY created_at ASC";
        $results = $this->query($sql, [$tripId]);
        
        $bookings = [];
        foreach ($results as $bookingData) {
            $bookings[] = $this->hydrateBooking($bookingData);
        }
        
        return $bookings;
    }

    /**
     * Compte le nombre total de places réservées pour un trajet
     *
     * @param int $tripId ID du trajet
     * @return int Nombre de places réservées
     * @throws DataAccessException
     */
    public function countBookedSeatsByTripId(int $tripId): int
    {
        $sql = "SELECT SUM(seat_count) as total_seats 
                FROM {$this->table} 
                WHERE trip_id = ? 
                AND status IN ('pending', 'confirmed')";
                
        $result = $this->queryOne($sql, [$tripId]);
        
        return $result && isset($result['total_seats']) ? (int)$result['total_seats'] : 0;
    }

    /**
     * Vérifie si un utilisateur a déjà réservé sur un trajet spécifique
     *
     * @param int $passengerId ID du passager
     * @param int $tripId ID du trajet
     * @return bool True si l'utilisateur a une réservation active
     * @throws DataAccessException
     */
    public function hasUserActiveBookingForTrip(int $passengerId, int $tripId): bool
    {
        $sql = "SELECT COUNT(*) as booking_count 
                FROM {$this->table} 
                WHERE passenger_id = ? 
                AND trip_id = ? 
                AND status IN ('pending', 'confirmed')";
                
        $result = $this->queryOne($sql, [$passengerId, $tripId]);
        
        return $result && $result['booking_count'] > 0;
    }

    /**
     * Trouve les réservations confirmées pour un chauffeur donné
     *
     * @param int $driverId ID du chauffeur
     * @param int $limit Limite de résultats
     * @param int $offset Décalage pour pagination
     * @return array Réservations
     * @throws DataAccessException
     */
    public function findConfirmedBookingsForDriver(int $driverId, int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT b.* 
                FROM {$this->table} b
                JOIN trips t ON b.trip_id = t.id
                WHERE t.driver_id = ?
                AND b.status = 'confirmed'
                ORDER BY t.departure_time ASC
                LIMIT ? OFFSET ?";
                
        $results = $this->query($sql, [$driverId, $limit, $offset]);
        
        $bookings = [];
        foreach ($results as $bookingData) {
            $bookings[] = $this->hydrateBooking($bookingData);
        }
        
        return $bookings;
    }

    /**
     * Marque toutes les réservations comme annulées pour un trajet annulé
     *
     * @param int $tripId ID du trajet
     * @return bool Succès
     * @throws DataAccessException
     */
    public function cancelAllBookingsForTrip(int $tripId): bool
    {
        $data = [
            'status' => 'cancelled',
            'updated_at' => (new \DateTime())->format('Y-m-d H:i:s')
        ];
        
        $conditions = [
            'trip_id' => $tripId,
            'status IN' => ['pending', 'confirmed']
        ];
        
        return $this->updateWhere($conditions, $data);
    }

    /**
     * Calcule le montant total des réservations pour un trajet
     *
     * @param int $tripId ID du trajet
     * @return float Montant total
     * @throws DataAccessException
     */
    public function calculateTotalAmountForTrip(int $tripId): float
    {
        $sql = "SELECT SUM(total_price) as total_amount 
                FROM {$this->table} 
                WHERE trip_id = ? 
                AND status = 'confirmed'";
                
        $result = $this->queryOne($sql, [$tripId]);
        
        return $result && isset($result['total_amount']) ? (float)$result['total_amount'] : 0;
    }

    /**
     * Vérifie la disponibilité des places pour un trajet donné
     *
     * @param int $tripId ID du trajet
     * @param int $requestedSeats Nombre de places demandées
     * @return bool True si les places sont disponibles
     * @throws DataAccessException
     */
    public function areSeatAvailableForTrip(int $tripId, int $requestedSeats): bool
    {
        // Récupérer le nombre de places disponibles sur le trajet
        $tripRepo = new TripRepository($this->connection);
        $trip = $tripRepo->findById($tripId);
        
        if (!$trip) {
            throw new DataAccessException("Trajet non trouvé");
        }
        
        // Calculer le nombre de places déjà réservées
        $bookedSeats = $this->countBookedSeatsByTripId($tripId);
        
        // Vérifier si les places demandées sont disponibles
        return ($bookedSeats + $requestedSeats) <= $trip->getAvailableSeats();
    }

    /**
     * Calcule la quantité totale de CO2 économisée par un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @return float Quantité de CO2 économisée en kg
     * @throws DataAccessException
     */
    public function calculateUserTotalCO2Savings(int $userId): float
    {
        $sql = "SELECT SUM(co2_saved) as total_co2_saved 
                FROM {$this->table} 
                WHERE passenger_id = ? 
                AND status = 'completed'";
                
        $result = $this->queryOne($sql, [$userId]);
        
        return $result && isset($result['total_co2_saved']) ? (float)$result['total_co2_saved'] : 0;
    }

    /**
     * Hydrate un objet Booking à partir des données de la base
     *
     * @param array $data Données de la base
     * @return Booking Objet Booking
     */
    private function hydrateBooking(array $data): Booking
    {
        $booking = new Booking();
        
        $booking->setId((int)$data['id']);
        $booking->setPassengerId((int)$data['passenger_id']);
        $booking->setTripId((int)$data['trip_id']);
        $booking->setSeatCount((int)$data['seat_count']);
        $booking->setTotalPrice((float)$data['total_price']);
        $booking->setStatus($data['status']);
        $booking->setPaymentMethod($data['payment_method']);
        $booking->setPaymentTransactionId($data['payment_transaction_id']);
        
        if (!empty($data['payment_confirmed_at'])) {
            $booking->setPaymentConfirmedAt(new \DateTime($data['payment_confirmed_at']));
        }
        
        $booking->setHasLuggage((bool)$data['has_luggage']);
        $booking->setPassengerNotes($data['passenger_notes']);
        $booking->setPickupLocation($data['pickup_location']);
        $booking->setDropoffLocation($data['dropoff_location']);
        
        if (!empty($data['custom_pickup_time'])) {
            $booking->setCustomPickupTime(new \DateTime($data['custom_pickup_time']));
        }
        
        $booking->setCo2Saved($data['co2_saved'] !== null ? (float)$data['co2_saved'] : null);
        $booking->setCreatedAt(new \DateTime($data['created_at']));
        $booking->setUpdatedAt(new \DateTime($data['updated_at']));
        
        return $booking;
    }
} 