<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\RideRepositoryInterface;
use App\Domain\Entities\Ride;
use App\Domain\Entities\User;
use App\Domain\ValueObjects\Location;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\Email;
use App\Domain\Enums\RideStatus;
use App\Infrastructure\Persistence\RideMapper;
use App\Infrastructure\Persistence\UserMapper;
use App\Infrastructure\Persistence\LocationMapper;
use App\Core\Database\DatabaseInterface;
use App\Core\Logger;
use PDO;
use Exception;

/**
 * Implémentation MySQL du repository des trajets
 */
class MySQLRideRepository implements RideRepositoryInterface
{
    private DatabaseInterface $database;
    private Logger $logger;
    private RideMapper $rideMapper;
    private UserMapper $userMapper;
    private LocationMapper $locationMapper;

    public function __construct(
        DatabaseInterface $database,
        Logger $logger,
        RideMapper $rideMapper,
        UserMapper $userMapper,
        LocationMapper $locationMapper
    ) {
        $this->database = $database;
        $this->logger = $logger;
        $this->rideMapper = $rideMapper;
        $this->userMapper = $userMapper;
        $this->locationMapper = $locationMapper;
    }

    /**
     * Trouve un trajet par son ID
     */
    public function findById(int $id): ?Ride
    {
        try {
            $sql = "
                SELECT 
                    c.covoiturage_id, 
                    c.date_depart, 
                    c.heure_depart,
                    c.date_arrivee,
                    c.heure_arrivee,
                    c.nb_place,
                    c.prix_personne,
                    c.empreinte_carbone,
                    c.voiture_id,
                    sc.libelle as statut_covoiturage,
                    -- Lieu de départ
                    ld.lieu_id as lieu_depart_id,
                    ld.nom AS lieu_depart,
                    ld.latitude as lieu_depart_lat,
                    ld.longitude as lieu_depart_lng,
                    -- Lieu d'arrivée
                    la.lieu_id as lieu_arrivee_id,
                    la.nom AS lieu_arrivee,
                    la.latitude as lieu_arrivee_lat,
                    la.longitude as lieu_arrivee_lng,
                    -- Conducteur/Utilisateur
                    u.utilisateur_id,
                    u.pseudo,
                    u.email,
                    u.photo_path,
                    IFNULL(AVG(a.note), 0) AS note_moyenne,
                    COUNT(a.avis_id) AS nombre_avis,
                    u.date_creation,
                    -- Véhicule
                    m.nom AS modele,
                    ma.libelle AS marque,
                    te.energie_id,
                    te.libelle AS type_energie,
                    -- Places réservées
                    (
                        SELECT COUNT(*)
                        FROM Participation p
                        WHERE p.covoiturage_id = c.covoiturage_id
                        AND p.statut_id = (SELECT statut_id FROM StatutParticipation WHERE libelle = 'confirmé')
                    ) AS places_reservees
                FROM 
                    Covoiturage c
                JOIN 
                    Lieu ld ON c.lieu_depart_id = ld.lieu_id
                JOIN 
                    Lieu la ON c.lieu_arrivee_id = la.lieu_id
                JOIN 
                    Voiture v ON c.voiture_id = v.voiture_id
                JOIN 
                    Utilisateur u ON v.utilisateur_id = u.utilisateur_id
                JOIN 
                    Modele m ON v.modele_id = m.modele_id
                JOIN 
                    Marque ma ON m.marque_id = ma.marque_id
                JOIN 
                    TypeEnergie te ON v.energie_id = te.energie_id
                LEFT JOIN 
                    StatutCovoiturage sc ON c.statut_id = sc.statut_id
                LEFT JOIN 
                    Avis a ON a.covoiturage_id = c.covoiturage_id
                WHERE 
                    c.covoiturage_id = ?
                GROUP BY 
                    c.covoiturage_id
            ";

            $stmt = $this->database->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return null;
            }

            return $this->rideMapper->mapToEntity($result);

        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la recherche du trajet par ID', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Trouve tous les trajets d'un conducteur
     */
    public function findByDriver(User $driver): array
    {
        try {
            $sql = "
                SELECT 
                    c.covoiturage_id, 
                    c.date_depart, 
                    c.heure_depart,
                    c.date_arrivee,
                    c.heure_arrivee,
                    c.nb_place,
                    c.prix_personne,
                    c.empreinte_carbone,
                    c.voiture_id,
                    sc.libelle as statut_covoiturage,
                    -- Lieu de départ
                    ld.lieu_id as lieu_depart_id,
                    ld.nom AS lieu_depart,
                    ld.latitude as lieu_depart_lat,
                    ld.longitude as lieu_depart_lng,
                    -- Lieu d'arrivée
                    la.lieu_id as lieu_arrivee_id,
                    la.nom AS lieu_arrivee,
                    la.latitude as lieu_arrivee_lat,
                    la.longitude as lieu_arrivee_lng,
                    -- Conducteur/Utilisateur
                    u.utilisateur_id,
                    u.pseudo,
                    u.email,
                    u.photo_path,
                    IFNULL(AVG(a.note), 0) AS note_moyenne,
                    COUNT(a.avis_id) AS nombre_avis,
                    u.date_creation,
                    -- Véhicule
                    m.nom AS modele,
                    ma.libelle AS marque,
                    te.energie_id,
                    te.libelle AS type_energie,
                    -- Places réservées
                    (
                        SELECT COUNT(*)
                        FROM Participation p
                        WHERE p.covoiturage_id = c.covoiturage_id
                        AND p.statut_id = (SELECT statut_id FROM StatutParticipation WHERE libelle = 'confirmé')
                    ) AS places_reservees
                FROM 
                    Covoiturage c
                JOIN 
                    Lieu ld ON c.lieu_depart_id = ld.lieu_id
                JOIN 
                    Lieu la ON c.lieu_arrivee_id = la.lieu_id
                JOIN 
                    Voiture v ON c.voiture_id = v.voiture_id
                JOIN 
                    Utilisateur u ON v.utilisateur_id = u.utilisateur_id
                JOIN 
                    Modele m ON v.modele_id = m.modele_id
                JOIN 
                    Marque ma ON m.marque_id = ma.marque_id
                JOIN 
                    TypeEnergie te ON v.energie_id = te.energie_id
                LEFT JOIN 
                    StatutCovoiturage sc ON c.statut_id = sc.statut_id
                LEFT JOIN 
                    Avis a ON a.covoiturage_id = c.covoiturage_id
                WHERE 
                    u.utilisateur_id = ?
                GROUP BY 
                    c.covoiturage_id
                ORDER BY 
                    c.date_depart DESC, c.heure_depart DESC
            ";

            $stmt = $this->database->prepare($sql);
            $stmt->execute([$driver->getId()]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $rides = [];
            foreach ($results as $result) {
                $rides[] = $this->rideMapper->mapToEntity($result);
            }

            return $rides;

        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la recherche des trajets du conducteur', [
                'driver_id' => $driver->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Recherche des trajets selon des critères
     */
    public function searchRides(
        ?Location $departure = null,
        ?Location $arrival = null,
        ?\DateTime $date = null,
        ?string $sortBy = 'departureTime',
        int $page = 1,
        int $limit = 10
    ): array {
        try {
            $offset = ($page - 1) * $limit;
            
            $sql = "
                SELECT 
                    c.covoiturage_id, 
                    c.date_depart, 
                    c.heure_depart,
                    c.date_arrivee,
                    c.heure_arrivee,
                    c.nb_place,
                    c.prix_personne,
                    c.empreinte_carbone,
                    c.voiture_id,
                    sc.libelle as statut_covoiturage,
                    -- Lieu de départ
                    ld.lieu_id as lieu_depart_id,
                    ld.nom AS lieu_depart,
                    ld.latitude as lieu_depart_lat,
                    ld.longitude as lieu_depart_lng,
                    -- Lieu d'arrivée
                    la.lieu_id as lieu_arrivee_id,
                    la.nom AS lieu_arrivee,
                    la.latitude as lieu_arrivee_lat,
                    la.longitude as lieu_arrivee_lng,
                    -- Conducteur/Utilisateur
                    u.utilisateur_id,
                    u.pseudo,
                    u.email,
                    u.photo_path,
                    IFNULL(AVG(a.note), 0) AS note_moyenne,
                    COUNT(a.avis_id) AS nombre_avis,
                    u.date_creation,
                    -- Véhicule
                    m.nom AS modele,
                    ma.libelle AS marque,
                    te.energie_id,
                    te.libelle AS type_energie,
                    -- Places réservées
                    (
                        SELECT COUNT(*)
                        FROM Participation p
                        WHERE p.covoiturage_id = c.covoiturage_id
                        AND p.statut_id = (SELECT statut_id FROM StatutParticipation WHERE libelle = 'confirmé')
                    ) AS places_reservees
                FROM 
                    Covoiturage c
                JOIN 
                    Lieu ld ON c.lieu_depart_id = ld.lieu_id
                JOIN 
                    Lieu la ON c.lieu_arrivee_id = la.lieu_id
                JOIN 
                    Voiture v ON c.voiture_id = v.voiture_id
                JOIN 
                    Utilisateur u ON v.utilisateur_id = u.utilisateur_id
                JOIN 
                    Modele m ON v.modele_id = m.modele_id
                JOIN 
                    Marque ma ON m.marque_id = ma.marque_id
                JOIN 
                    TypeEnergie te ON v.energie_id = te.energie_id
                LEFT JOIN 
                    StatutCovoiturage sc ON c.statut_id = sc.statut_id
                LEFT JOIN 
                    Avis a ON a.covoiturage_id = c.covoiturage_id
                WHERE 
                    c.statut_id = (SELECT statut_id FROM StatutCovoiturage WHERE libelle = 'planifié')
                AND
                    (c.date_depart > CURDATE() OR (c.date_depart = CURDATE() AND c.heure_depart > CURTIME()))
                AND
                    (c.nb_place - (
                        SELECT COUNT(*)
                        FROM Participation p
                        WHERE p.covoiturage_id = c.covoiturage_id
                        AND p.statut_id = (SELECT statut_id FROM StatutParticipation WHERE libelle = 'confirmé')
                    )) > 0
            ";

            $params = [];

            // Filtrage par lieu de départ
            if ($departure) {
                $sql .= " AND ld.lieu_id = ?";
                $params[] = $departure->getId();
            }

            // Filtrage par lieu d'arrivée
            if ($arrival) {
                $sql .= " AND la.lieu_id = ?";
                $params[] = $arrival->getId();
            }

            // Filtrage par date
            if ($date) {
                $sql .= " AND c.date_depart = ?";
                $params[] = $date->format('Y-m-d');
            }

            // Groupement pour l'agrégation des avis
            $sql .= " GROUP BY c.covoiturage_id";

            // Tri des résultats
            $sql .= match ($sortBy) {
                'price' => " ORDER BY c.prix_personne ASC, c.date_depart ASC, c.heure_depart ASC",
                default => " ORDER BY c.date_depart ASC, c.heure_depart ASC"
            };

            // Pagination
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->database->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $rides = [];
            foreach ($results as $result) {
                $rides[] = $this->rideMapper->mapToEntity($result);
            }

            return $rides;

        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la recherche de trajets', [
                'departure' => $departure?->getName(),
                'arrival' => $arrival?->getName(),
                'date' => $date?->format('Y-m-d'),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Sauvegarde un trajet
     */
    public function save(Ride $ride): void
    {
        try {
            if ($ride->getId() === 0) {
                $this->insert($ride);
            } else {
                $this->update($ride);
            }
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la sauvegarde du trajet', [
                'ride_id' => $ride->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Supprime un trajet
     */
    public function delete(Ride $ride): void
    {
        try {
            $sql = "DELETE FROM Covoiturage WHERE covoiturage_id = ?";
            $stmt = $this->database->prepare($sql);
            $stmt->execute([$ride->getId()]);

            $this->logger->info('Trajet supprimé', ['ride_id' => $ride->getId()]);

        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la suppression du trajet', [
                'ride_id' => $ride->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Compte le nombre total de trajets correspondant aux critères
     */
    public function countSearchResults(
        ?Location $departure = null,
        ?Location $arrival = null,
        ?\DateTime $date = null
    ): int {
        try {
            $sql = "
                SELECT COUNT(DISTINCT c.covoiturage_id) as total
                FROM 
                    Covoiturage c
                JOIN 
                    Lieu ld ON c.lieu_depart_id = ld.lieu_id
                JOIN 
                    Lieu la ON c.lieu_arrivee_id = la.lieu_id
                WHERE 
                    c.statut_id = (SELECT statut_id FROM StatutCovoiturage WHERE libelle = 'planifié')
                AND
                    (c.date_depart > CURDATE() OR (c.date_depart = CURDATE() AND c.heure_depart > CURTIME()))
                AND
                    (c.nb_place - (
                        SELECT COUNT(*)
                        FROM Participation p
                        WHERE p.covoiturage_id = c.covoiturage_id
                        AND p.statut_id = (SELECT statut_id FROM StatutParticipation WHERE libelle = 'confirmé')
                    )) > 0
            ";

            $params = [];

            if ($departure) {
                $sql .= " AND ld.lieu_id = ?";
                $params[] = $departure->getId();
            }

            if ($arrival) {
                $sql .= " AND la.lieu_id = ?";
                $params[] = $arrival->getId();
            }

            if ($date) {
                $sql .= " AND c.date_depart = ?";
                $params[] = $date->format('Y-m-d');
            }

            $stmt = $this->database->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int) $result['total'];

        } catch (Exception $e) {
            $this->logger->error('Erreur lors du comptage des trajets', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Trouve les trajets disponibles pour réservation
     */
    public function findAvailableRides(int $limit = 10): array
    {
        return $this->searchRides(null, null, null, 'departureTime', 1, $limit);
    }

    /**
     * Trouve les trajets populaires (les plus réservés)
     */
    public function findPopularRides(int $limit = 10): array
    {
        try {
            $sql = "
                SELECT 
                    c.covoiturage_id, 
                    COUNT(p.participation_id) as reservations_count
                FROM 
                    Covoiturage c
                LEFT JOIN 
                    Participation p ON c.covoiturage_id = p.covoiturage_id
                WHERE 
                    c.statut_id = (SELECT statut_id FROM StatutCovoiturage WHERE libelle = 'planifié')
                AND
                    (c.date_depart > CURDATE() OR (c.date_depart = CURDATE() AND c.heure_depart > CURTIME()))
                GROUP BY 
                    c.covoiturage_id
                ORDER BY 
                    reservations_count DESC
                LIMIT ?
            ";

            $stmt = $this->database->prepare($sql);
            $stmt->execute([$limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $rides = [];
            foreach ($results as $result) {
                $ride = $this->findById($result['covoiturage_id']);
                if ($ride) {
                    $rides[] = $ride;
                }
            }

            return $rides;

        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la recherche des trajets populaires', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function insert(Ride $ride): void
    {
        $sql = "
            INSERT INTO Covoiturage (
                lieu_depart_id, 
                lieu_arrivee_id, 
                date_depart, 
                heure_depart,
                date_arrivee,
                heure_arrivee,
                nb_place,
                prix_personne,
                empreinte_carbone,
                voiture_id,
                statut_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, (SELECT statut_id FROM StatutCovoiturage WHERE libelle = 'planifié'))
        ";

        $stmt = $this->database->prepare($sql);
        $stmt->execute([
            $ride->getDeparture()->getId(),
            $ride->getArrival()->getId(),
            $ride->getDepartureDateTime()->format('Y-m-d'),
            $ride->getDepartureDateTime()->format('H:i:s'),
            $ride->getArrivalDateTime()->format('Y-m-d'),
            $ride->getArrivalDateTime()->format('H:i:s'),
            $ride->getTotalSeats(),
            $ride->getPricePerPerson()->getAmount(),
            $ride->getCarbonFootprint(),
            1 // TODO: Récupérer l'ID du véhicule depuis l'entité Vehicle
        ]);

        $this->logger->info('Nouveau trajet créé', [
            'departure' => $ride->getDeparture()->getName(),
            'arrival' => $ride->getArrival()->getName()
        ]);
    }

    private function update(Ride $ride): void
    {
        $sql = "
            UPDATE Covoiturage 
            SET 
                lieu_depart_id = ?, 
                lieu_arrivee_id = ?, 
                date_depart = ?, 
                heure_depart = ?,
                date_arrivee = ?,
                heure_arrivee = ?,
                nb_place = ?,
                prix_personne = ?,
                empreinte_carbone = ?
            WHERE 
                covoiturage_id = ?
        ";

        $stmt = $this->database->prepare($sql);
        $stmt->execute([
            $ride->getDeparture()->getId(),
            $ride->getArrival()->getId(),
            $ride->getDepartureDateTime()->format('Y-m-d'),
            $ride->getDepartureDateTime()->format('H:i:s'),
            $ride->getArrivalDateTime()->format('Y-m-d'),
            $ride->getArrivalDateTime()->format('H:i:s'),
            $ride->getTotalSeats(),
            $ride->getPricePerPerson()->getAmount(),
            $ride->getCarbonFootprint(),
            $ride->getId()
        ]);

        $this->logger->info('Trajet mis à jour', ['ride_id' => $ride->getId()]);
    }
} 