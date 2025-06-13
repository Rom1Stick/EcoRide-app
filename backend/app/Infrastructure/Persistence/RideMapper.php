<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\Ride;
use App\Domain\Entities\User;
use App\Domain\ValueObjects\Location;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\Email;
use App\Domain\Enums\RideStatus;
use DateTime;

/**
 * Mapper pour convertir les données SQL en entités Ride
 */
class RideMapper
{
    private UserMapper $userMapper;
    private LocationMapper $locationMapper;
    private VehicleMapper $vehicleMapper;

    public function __construct(
        UserMapper $userMapper,
        LocationMapper $locationMapper,
        VehicleMapper $vehicleMapper
    ) {
        $this->userMapper = $userMapper;
        $this->locationMapper = $locationMapper;
        $this->vehicleMapper = $vehicleMapper;
    }

    /**
     * Convertit un tableau de données SQL en entité Ride
     */
    public function mapToEntity(array $data): Ride
    {
        // Création des Value Objects pour les lieux
        $departure = $this->locationMapper->mapToLocation(
            (int) $data['lieu_depart_id'],
            $data['lieu_depart'],
            $data['lieu_depart_lat'] ?? null,
            $data['lieu_depart_lng'] ?? null
        );

        $arrival = $this->locationMapper->mapToLocation(
            (int) $data['lieu_arrivee_id'],
            $data['lieu_arrivee'],
            $data['lieu_arrivee_lat'] ?? null,
            $data['lieu_arrivee_lng'] ?? null
        );

        // Création de l'entité User (conducteur)
        $driver = $this->userMapper->mapToEntity($data);

        // Création du véhicule
        $vehicle = $this->vehicleMapper->mapToVehicle($data);

        // Combinaison date + heure pour les DateTime
        $departureDateTime = new DateTime($data['date_depart'] . ' ' . $data['heure_depart']);
        $arrivalDateTime = new DateTime($data['date_arrivee'] . ' ' . $data['heure_arrivee']);

        // Value Object Money pour le prix
        $pricePerPerson = new Money((float) $data['prix_personne']);

        // Conversion du statut
        $status = $this->mapStatus($data['statut_covoiturage'] ?? 'planifié');

        // Calcul des places disponibles
        $totalSeats = (int) $data['nb_place'];
        $bookedSeats = (int) ($data['places_reservees'] ?? 0);
        $availableSeats = $totalSeats - $bookedSeats;

        // Création de l'entité Ride
        $ride = new Ride(
            (int) $data['covoiturage_id'],
            $departure,
            $arrival,
            $departureDateTime,
            $arrivalDateTime,
            $pricePerPerson,
            $totalSeats,
            $driver,
            $vehicle,
            (float) ($data['empreinte_carbone'] ?? 0.0)
        );

        // Mise à jour des places disponibles (contournement car l'entité ne permet pas de setter directement)
        // Note: Dans une implémentation complète, on ajouterait une méthode pour cela
        return $ride;
    }

    /**
     * Convertit une entité Ride en tableau pour l'insertion/mise à jour SQL
     */
    public function mapToArray(Ride $ride): array
    {
        return [
            'covoiturage_id' => $ride->getId(),
            'lieu_depart_id' => $ride->getDeparture()->getId(),
            'lieu_arrivee_id' => $ride->getArrival()->getId(),
            'date_depart' => $ride->getDepartureDateTime()->format('Y-m-d'),
            'heure_depart' => $ride->getDepartureDateTime()->format('H:i:s'),
            'date_arrivee' => $ride->getArrivalDateTime()->format('Y-m-d'),
            'heure_arrivee' => $ride->getArrivalDateTime()->format('H:i:s'),
            'nb_place' => $ride->getTotalSeats(),
            'prix_personne' => $ride->getPricePerPerson()->getAmount(),
            'empreinte_carbone' => $ride->getCarbonFootprint(),
            'statut_id' => $this->mapStatusToId($ride->getStatus())
        ];
    }

    /**
     * Mappe un statut string vers l'enum RideStatus
     */
    private function mapStatus(string $statusLabel): RideStatus
    {
        return match (strtolower($statusLabel)) {
            'planifié', 'planned' => RideStatus::PLANNED,
            'en cours', 'in_progress' => RideStatus::IN_PROGRESS,
            'terminé', 'completed' => RideStatus::COMPLETED,
            'annulé', 'cancelled' => RideStatus::CANCELLED,
            default => RideStatus::PLANNED
        };
    }

    /**
     * Mappe un enum RideStatus vers l'ID de statut en base
     */
    private function mapStatusToId(RideStatus $status): int
    {
        // Cette méthode devrait idéalement interroger la base pour récupérer l'ID
        // Pour simplifier, on retourne des valeurs hardcodées
        return match ($status) {
            RideStatus::PLANNED => 1,
            RideStatus::IN_PROGRESS => 2,
            RideStatus::COMPLETED => 3,
            RideStatus::CANCELLED => 4
        };
    }

    /**
     * Convertit plusieurs résultats SQL en array d'entités Ride
     */
    public function mapToEntities(array $results): array
    {
        $rides = [];
        foreach ($results as $result) {
            $rides[] = $this->mapToEntity($result);
        }
        return $rides;
    }
} 