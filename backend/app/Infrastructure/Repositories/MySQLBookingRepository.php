<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\BookingRepositoryInterface;
use App\Core\Database\DatabaseInterface;
use PDO;

/**
 * Implémentation MySQL du repository pour les réservations
 */
class MySQLBookingRepository implements BookingRepositoryInterface
{
    public function __construct(
        private DatabaseInterface $database
    ) {}

    public function findById(int $bookingId): ?array
    {
        $pdo = $this->database->getMysqlConnection();
        
        $stmt = $pdo->prepare(
            'SELECT p.participation_id as booking_id,
                    p.utilisateur_id as user_id,
                    p.covoiturage_id as ride_id,
                    p.date_reservation as reserved_at,
                    sp.libelle as status,
                    c.prix_personne as price,
                    c.ville_depart as departure,
                    c.ville_destination as destination,
                    c.date_depart,
                    c.heure_depart as departure_time
             FROM Participation p
             JOIN StatutParticipation sp ON p.statut_id = sp.statut_id
             JOIN Covoiturage c ON p.covoiturage_id = c.covoiturage_id
             WHERE p.participation_id = ?'
        );
        
        $stmt->execute([$bookingId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    public function findByUserId(int $userId): array
    {
        $pdo = $this->database->getMysqlConnection();
        
        $stmt = $pdo->prepare(
            'SELECT p.participation_id as booking_id,
                    p.covoiturage_id as ride_id,
                    p.date_reservation as reserved_at,
                    sp.libelle as status,
                    c.ville_depart as departure,
                    c.ville_destination as destination,
                    c.date_depart,
                    c.heure_depart as departure_time,
                    c.prix_personne as price
             FROM Participation p
             JOIN StatutParticipation sp ON p.statut_id = sp.statut_id
             JOIN Covoiturage c ON p.covoiturage_id = c.covoiturage_id
             WHERE p.utilisateur_id = ?
             ORDER BY p.date_reservation DESC'
        );
        
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByRideId(int $rideId): array
    {
        $pdo = $this->database->getMysqlConnection();
        
        $stmt = $pdo->prepare(
            'SELECT p.participation_id as booking_id,
                    p.utilisateur_id as user_id,
                    p.date_reservation as reserved_at,
                    sp.libelle as status,
                    u.pseudo as username,
                    CONCAT(u.nom, " ", u.prenom) as name
             FROM Participation p
             JOIN StatutParticipation sp ON p.statut_id = sp.statut_id
             JOIN Utilisateur u ON p.utilisateur_id = u.utilisateur_id
             WHERE p.covoiturage_id = ?
             ORDER BY p.date_reservation ASC'
        );
        
        $stmt->execute([$rideId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $bookingData): int
    {
        $pdo = $this->database->getMysqlConnection();
        
        // Récupérer l'ID du statut
        $statusStmt = $pdo->prepare('SELECT statut_id FROM StatutParticipation WHERE libelle = ?');
        $statusStmt->execute([$bookingData['status'] ?? 'confirmé']);
        $statusId = (int)$statusStmt->fetchColumn();
        
        if (!$statusId) {
            // Créer le statut s'il n'existe pas
            $createStatusStmt = $pdo->prepare('INSERT INTO StatutParticipation (libelle) VALUES (?)');
            $createStatusStmt->execute([$bookingData['status'] ?? 'confirmé']);
            $statusId = (int)$pdo->lastInsertId();
        }
        
        // Créer la participation
        $stmt = $pdo->prepare(
            'INSERT INTO Participation (utilisateur_id, covoiturage_id, date_reservation, statut_id)
             VALUES (?, ?, ?, ?)'
        );
        
        $reservedAt = $bookingData['reserved_at'] ?? new \DateTime();
        if ($reservedAt instanceof \DateTime) {
            $reservedAt = $reservedAt->format('Y-m-d H:i:s');
        }
        
        $stmt->execute([
            $bookingData['user_id'],
            $bookingData['ride_id'], 
            $reservedAt,
            $statusId
        ]);
        
        return (int)$pdo->lastInsertId();
    }

    public function updateStatus(int $bookingId, string $status): bool
    {
        $pdo = $this->database->getMysqlConnection();
        
        // Récupérer l'ID du statut
        $statusStmt = $pdo->prepare('SELECT statut_id FROM StatutParticipation WHERE libelle = ?');
        $statusStmt->execute([$status]);
        $statusId = (int)$statusStmt->fetchColumn();
        
        if (!$statusId) {
            // Créer le statut s'il n'existe pas
            $createStatusStmt = $pdo->prepare('INSERT INTO StatutParticipation (libelle) VALUES (?)');
            $createStatusStmt->execute([$status]);
            $statusId = (int)$pdo->lastInsertId();
        }
        
        $stmt = $pdo->prepare('UPDATE Participation SET statut_id = ? WHERE participation_id = ?');
        return $stmt->execute([$statusId, $bookingId]);
    }

    public function delete(int $bookingId): bool
    {
        $pdo = $this->database->getMysqlConnection();
        $stmt = $pdo->prepare('DELETE FROM Participation WHERE participation_id = ?');
        return $stmt->execute([$bookingId]);
    }

    public function findByStatus(string $status): array
    {
        $pdo = $this->database->getMysqlConnection();
        
        $stmt = $pdo->prepare(
            'SELECT p.participation_id as booking_id,
                    p.utilisateur_id as user_id,
                    p.covoiturage_id as ride_id,
                    p.date_reservation as reserved_at,
                    sp.libelle as status
             FROM Participation p
             JOIN StatutParticipation sp ON p.statut_id = sp.statut_id
             WHERE sp.libelle = ?
             ORDER BY p.date_reservation DESC'
        );
        
        $stmt->execute([$status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countActiveBookingsForRide(int $rideId): int
    {
        $pdo = $this->database->getMysqlConnection();
        
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) 
             FROM Participation p
             JOIN StatutParticipation sp ON p.statut_id = sp.statut_id
             WHERE p.covoiturage_id = ? AND sp.libelle = ?'
        );
        
        $stmt->execute([$rideId, 'confirmé']);
        return (int)$stmt->fetchColumn();
    }

    public function findByUserIdAndStatus(int $userId, string $status): array
    {
        $pdo = $this->database->getMysqlConnection();
        
        $stmt = $pdo->prepare(
            'SELECT p.participation_id as booking_id,
                    p.covoiturage_id as ride_id,
                    p.date_reservation as reserved_at,
                    sp.libelle as status,
                    c.ville_depart as departure,
                    c.ville_destination as destination,
                    c.date_depart,
                    c.heure_depart as departure_time,
                    c.prix_personne as price
             FROM Participation p
             JOIN StatutParticipation sp ON p.statut_id = sp.statut_id
             JOIN Covoiturage c ON p.covoiturage_id = c.covoiturage_id
             WHERE p.utilisateur_id = ? AND sp.libelle = ?
             ORDER BY p.date_reservation DESC'
        );
        
        $stmt->execute([$userId, $status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function hasUserBookedRide(int $userId, int $rideId): bool
    {
        $pdo = $this->database->getMysqlConnection();
        
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) 
             FROM Participation p
             JOIN StatutParticipation sp ON p.statut_id = sp.statut_id
             WHERE p.utilisateur_id = ? AND p.covoiturage_id = ? 
             AND sp.libelle IN (?, ?)'
        );
        
        $stmt->execute([$userId, $rideId, 'confirmé', 'en_attente']);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function getUserBookingHistory(int $userId, int $limit = 20): array
    {
        $pdo = $this->database->getMysqlConnection();
        
        $stmt = $pdo->prepare(
            'SELECT p.participation_id as booking_id,
                    p.covoiturage_id as ride_id,
                    p.date_reservation as reserved_at,
                    sp.libelle as status,
                    c.ville_depart as departure,
                    c.ville_destination as destination,
                    c.date_depart,
                    c.heure_depart as departure_time,
                    c.prix_personne as price,
                    CONCAT(u.nom, " ", u.prenom) as driver_name
             FROM Participation p
             JOIN StatutParticipation sp ON p.statut_id = sp.statut_id
             JOIN Covoiturage c ON p.covoiturage_id = c.covoiturage_id
             JOIN Voiture v ON c.voiture_id = v.voiture_id
             JOIN Utilisateur u ON v.utilisateur_id = u.utilisateur_id
             WHERE p.utilisateur_id = ?
             ORDER BY p.date_reservation DESC
             LIMIT ?'
        );
        
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 