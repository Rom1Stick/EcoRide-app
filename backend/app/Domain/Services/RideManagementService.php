<?php

namespace App\Domain\Services;

use App\Domain\Entities\Ride;
use App\Domain\Entities\User;
use App\Domain\Repositories\RideRepositoryInterface;
use App\Domain\ValueObjects\Location;
use App\Domain\ValueObjects\Money;
use App\Domain\Exceptions\RideNotFoundException;
use App\Domain\Exceptions\BookingException;
use App\Domain\Exceptions\UnauthorizedException;
use DateTime;
use InvalidArgumentException;

/**
 * Service métier pour la gestion des trajets
 */
class RideManagementService
{
    private RideRepositoryInterface $rideRepository;

    public function __construct(RideRepositoryInterface $rideRepository)
    {
        $this->rideRepository = $rideRepository;
    }

    /**
     * Recherche des trajets selon des critères
     */
    public function searchRides(
        ?string $departureLocationName = null,
        ?string $arrivalLocationName = null,
        ?string $date = null,
        ?float $maxPrice = null,
        string $sortBy = 'departureTime',
        int $page = 1,
        int $limit = 10
    ): array {
        // Conversion des critères de recherche
        $departureLocation = $departureLocationName ? $this->findLocationByName($departureLocationName) : null;
        $arrivalLocation = $arrivalLocationName ? $this->findLocationByName($arrivalLocationName) : null;
        $searchDate = $date ? new DateTime($date) : null;

        // Recherche des trajets
        $rides = $this->rideRepository->searchRides(
            $departureLocation,
            $arrivalLocation,
            $searchDate,
            $sortBy,
            $page,
            $limit
        );

        // Filtrage par prix maximum si spécifié
        if ($maxPrice !== null) {
            $rides = array_filter($rides, function (Ride $ride) use ($maxPrice) {
                return $ride->getPricePerPerson()->getAmount() <= $maxPrice;
            });
        }

        // Calcul du nombre total pour la pagination
        $totalCount = $this->rideRepository->countSearchResults(
            $departureLocation,
            $arrivalLocation,
            $searchDate
        );

        return [
            'rides' => array_map(fn(Ride $ride) => $ride->toArray(), $rides),
            'pagination' => [
                'total' => $totalCount,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($totalCount / $limit)
            ]
        ];
    }

    /**
     * Récupère les détails d'un trajet
     */
    public function getRideDetails(int $rideId): array
    {
        $ride = $this->rideRepository->findById($rideId);
        
        if (!$ride) {
            throw new RideNotFoundException("Trajet avec l'ID $rideId non trouvé");
        }

        return $ride->toArray();
    }

    /**
     * Crée un nouveau trajet
     */
    public function createRide(
        User $driver,
        Location $departure,
        Location $arrival,
        DateTime $departureDateTime,
        float $priceAmount,
        int $totalSeats,
        ?string $vehicle = null
    ): Ride {
        // Validation des paramètres
        $this->validateRideCreation($departure, $arrival, $departureDateTime, $priceAmount, $totalSeats);

        // Calcul automatique de l'heure d'arrivée (estimation)
        $arrivalDateTime = $this->calculateEstimatedArrival($departureDateTime, $departure, $arrival);

        // Calcul de l'empreinte carbone
        $carbonFootprint = $this->calculateCarbonFootprint($departure, $arrival, $totalSeats);

        // Création du trajet
        $ride = new Ride(
            0, // L'ID sera assigné par le repository lors de la sauvegarde
            $departure,
            $arrival,
            $departureDateTime,
            $arrivalDateTime,
            new Money($priceAmount),
            $totalSeats,
            $driver,
            $vehicle ?? $this->getDefaultVehicle($driver),
            $carbonFootprint
        );

        // Sauvegarde
        $this->rideRepository->save($ride);

        return $ride;
    }

    /**
     * Met à jour un trajet existant
     */
    public function updateRide(
        int $rideId,
        User $currentUser,
        ?DateTime $newDepartureDateTime = null,
        ?float $newPrice = null,
        ?int $newTotalSeats = null
    ): Ride {
        $ride = $this->rideRepository->findById($rideId);
        
        if (!$ride) {
            throw new RideNotFoundException("Trajet avec l'ID $rideId non trouvé");
        }

        // Vérification des permissions
        if ($ride->getDriver()->getId() !== $currentUser->getId()) {
            throw new UnauthorizedException("Seul le conducteur peut modifier ce trajet");
        }

        // Vérification que le trajet peut être modifié
        if (!$ride->getStatus()->isModifiable()) {
            throw new InvalidArgumentException("Ce trajet ne peut plus être modifié");
        }

        // Application des modifications
        if ($newDepartureDateTime !== null) {
            $this->validateFutureDateTime($newDepartureDateTime);
            // Note: Dans une implémentation complète, on modifierait l'entité
        }

        if ($newPrice !== null) {
            $this->validatePrice($newPrice);
            // Note: Dans une implémentation complète, on modifierait l'entité
        }

        if ($newTotalSeats !== null) {
            $this->validateSeatCount($newTotalSeats, $ride->getTotalSeats() - $ride->getAvailableSeats());
            // Note: Dans une implémentation complète, on modifierait l'entité
        }

        $this->rideRepository->save($ride);

        return $ride;
    }

    /**
     * Réserve des places dans un trajet
     */
    public function bookRide(int $rideId, User $passenger, int $seatsRequested): void
    {
        $ride = $this->rideRepository->findById($rideId);
        
        if (!$ride) {
            throw new RideNotFoundException("Trajet avec l'ID $rideId non trouvé");
        }

        // Vérification que le trajet accepte les réservations
        if (!$ride->isAvailableForBooking()) {
            throw new BookingException("Ce trajet n'accepte plus de réservations");
        }

        // Tentative de réservation (la logique métier est dans l'entité)
        try {
            $ride->bookSeats($passenger, $seatsRequested);
            $this->rideRepository->save($ride);
        } catch (InvalidArgumentException $e) {
            throw new BookingException($e->getMessage());
        }
    }

    /**
     * Annule une réservation
     */
    public function cancelBooking(int $rideId, User $passenger): void
    {
        $ride = $this->rideRepository->findById($rideId);
        
        if (!$ride) {
            throw new RideNotFoundException("Trajet avec l'ID $rideId non trouvé");
        }

        $ride->cancelBooking($passenger);
        $this->rideRepository->save($ride);
    }

    /**
     * Supprime un trajet
     */
    public function deleteRide(int $rideId, User $currentUser): void
    {
        $ride = $this->rideRepository->findById($rideId);
        
        if (!$ride) {
            throw new RideNotFoundException("Trajet avec l'ID $rideId non trouvé");
        }

        // Vérification des permissions
        if ($ride->getDriver()->getId() !== $currentUser->getId()) {
            throw new UnauthorizedException("Seul le conducteur peut supprimer ce trajet");
        }

        // Vérification que le trajet peut être supprimé
        if (!$ride->getStatus()->isCancellable()) {
            throw new InvalidArgumentException("Ce trajet ne peut plus être supprimé");
        }

        $this->rideRepository->delete($ride);
    }

    /**
     * Récupère les trajets d'un conducteur
     */
    public function getDriverRides(User $driver): array
    {
        $rides = $this->rideRepository->findByDriver($driver);
        return array_map(fn(Ride $ride) => $ride->toArray(), $rides);
    }

    private function validateRideCreation(
        Location $departure,
        Location $arrival,
        DateTime $departureDateTime,
        float $priceAmount,
        int $totalSeats
    ): void {
        if ($departure->equals($arrival)) {
            throw new InvalidArgumentException("Le lieu de départ et d'arrivée ne peuvent pas être identiques");
        }

        $this->validateFutureDateTime($departureDateTime);
        $this->validatePrice($priceAmount);
        $this->validateSeatCount($totalSeats, 0);
    }

    private function validateFutureDateTime(DateTime $dateTime): void
    {
        if ($dateTime <= new DateTime()) {
            throw new InvalidArgumentException("La date de départ doit être dans le futur");
        }
    }

    private function validatePrice(float $price): void
    {
        if ($price <= 0) {
            throw new InvalidArgumentException("Le prix doit être positif");
        }
        
        if ($price > 1000) {
            throw new InvalidArgumentException("Le prix ne peut pas dépasser 1000€");
        }
    }

    private function validateSeatCount(int $totalSeats, int $bookedSeats): void
    {
        if ($totalSeats <= 0) {
            throw new InvalidArgumentException("Le nombre de places doit être positif");
        }
        
        if ($totalSeats > 8) {
            throw new InvalidArgumentException("Le nombre maximum de places est de 8");
        }
        
        if ($totalSeats < $bookedSeats) {
            throw new InvalidArgumentException("Le nombre de places ne peut pas être inférieur aux places déjà réservées");
        }
    }

    private function calculateEstimatedArrival(DateTime $departure, Location $from, Location $to): DateTime
    {
        // Estimation simple: 1h de trajet par défaut
        // Dans une implémentation réelle, on utiliserait une API de géolocalisation
        $estimatedDuration = 60; // minutes
        
        if ($from->hasCoordinates() && $to->hasCoordinates()) {
            $distance = $from->distanceTo($to);
            if ($distance !== null) {
                // Estimation: 50 km/h de moyenne
                $estimatedDuration = ($distance / 50) * 60;
            }
        }

        return (clone $departure)->add(new \DateInterval('PT' . (int)$estimatedDuration . 'M'));
    }

    private function calculateCarbonFootprint(Location $from, Location $to, int $passengers): float
    {
        // Calcul simplifié de l'empreinte carbone
        $distance = 100; // km par défaut
        
        if ($from->hasCoordinates() && $to->hasCoordinates()) {
            $distance = $from->distanceTo($to) ?? 100;
        }

        // Estimation: 120g CO2/km pour une voiture, divisé par le nombre de passagers
        $carbonPerKm = 120; // grammes de CO2
        $totalCarbon = $distance * $carbonPerKm;
        
        return round($totalCarbon / ($passengers + 1), 2); // +1 pour inclure le conducteur
    }

    private function findLocationByName(string $name): ?Location
    {
        // Dans une implémentation réelle, on interrogerait un repository de lieux
        // Pour l'instant, on retourne null pour simplifier
        return null;
    }

    private function getDefaultVehicle(User $driver): object
    {
        // Dans une implémentation réelle, on récupérerait le véhicule par défaut de l'utilisateur
        return new \stdClass();
    }
} 