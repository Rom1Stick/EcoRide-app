<?php

namespace App\Domain\Entities;

use DateTime;
use InvalidArgumentException;

/**
 * Entité métier représentant un trajet de covoiturage
 */
class Ride
{
    private int $id;
    private Location $departure;
    private Location $arrival;
    private DateTime $departureDateTime;
    private DateTime $arrivalDateTime;
    private Money $pricePerPerson;
    private int $totalSeats;
    private int $availableSeats;
    private User $driver;
    private Vehicle $vehicle;
    private float $carbonFootprint;
    private RideStatus $status;
    private array $participants = [];

    public function __construct(
        int $id,
        Location $departure,
        Location $arrival,
        DateTime $departureDateTime,
        DateTime $arrivalDateTime,
        Money $pricePerPerson,
        int $totalSeats,
        User $driver,
        Vehicle $vehicle,
        float $carbonFootprint
    ) {
        $this->validateSeats($totalSeats);
        $this->validateDates($departureDateTime, $arrivalDateTime);
        
        $this->id = $id;
        $this->departure = $departure;
        $this->arrival = $arrival;
        $this->departureDateTime = $departureDateTime;
        $this->arrivalDateTime = $arrivalDateTime;
        $this->pricePerPerson = $pricePerPerson;
        $this->totalSeats = $totalSeats;
        $this->availableSeats = $totalSeats;
        $this->driver = $driver;
        $this->vehicle = $vehicle;
        $this->carbonFootprint = $carbonFootprint;
        $this->status = RideStatus::PLANNED;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDeparture(): Location
    {
        return $this->departure;
    }

    public function getArrival(): Location
    {
        return $this->arrival;
    }

    public function getDepartureDateTime(): DateTime
    {
        return $this->departureDateTime;
    }

    public function getArrivalDateTime(): DateTime
    {
        return $this->arrivalDateTime;
    }

    public function getPricePerPerson(): Money
    {
        return $this->pricePerPerson;
    }

    public function getTotalSeats(): int
    {
        return $this->totalSeats;
    }

    public function getAvailableSeats(): int
    {
        return $this->availableSeats;
    }

    public function getDriver(): User
    {
        return $this->driver;
    }

    public function getVehicle(): Vehicle
    {
        return $this->vehicle;
    }

    public function getCarbonFootprint(): float
    {
        return $this->carbonFootprint;
    }

    public function getStatus(): RideStatus
    {
        return $this->status;
    }

    public function getParticipants(): array
    {
        return $this->participants;
    }

    /**
     * Réserve des places pour un utilisateur
     */
    public function bookSeats(User $passenger, int $seatsRequested): void
    {
        if ($seatsRequested <= 0) {
            throw new InvalidArgumentException('Le nombre de places doit être positif');
        }

        if ($seatsRequested > $this->availableSeats) {
            throw new InvalidArgumentException('Pas assez de places disponibles');
        }

        if ($passenger->getId() === $this->driver->getId()) {
            throw new InvalidArgumentException('Le conducteur ne peut pas réserver ses propres places');
        }

        $this->availableSeats -= $seatsRequested;
        $this->participants[] = new Participation($passenger, $seatsRequested);
    }

    /**
     * Annule une réservation
     */
    public function cancelBooking(User $passenger): void
    {
        foreach ($this->participants as $key => $participation) {
            if ($participation->getPassenger()->getId() === $passenger->getId()) {
                $this->availableSeats += $participation->getSeatsCount();
                unset($this->participants[$key]);
                break;
            }
        }
    }

    /**
     * Vérifie si le trajet est disponible pour réservation
     */
    public function isAvailableForBooking(): bool
    {
        return $this->status === RideStatus::PLANNED 
            && $this->availableSeats > 0 
            && $this->departureDateTime > new DateTime();
    }

    /**
     * Calcule le coût total pour un nombre de places
     */
    public function calculateTotalCost(int $seats): Money
    {
        return $this->pricePerPerson->multiply($seats);
    }

    /**
     * Retourne les informations du trajet formatées pour l'API
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'departure' => [
                'location' => $this->departure->getName(),
                'date' => $this->departureDateTime->format('Y-m-d'),
                'time' => $this->departureDateTime->format('H:i')
            ],
            'arrival' => [
                'location' => $this->arrival->getName(),
                'date' => $this->arrivalDateTime->format('Y-m-d'),
                'time' => $this->arrivalDateTime->format('H:i')
            ],
            'price' => $this->pricePerPerson->getAmount(),
            'seats' => [
                'total' => $this->totalSeats,
                'available' => $this->availableSeats
            ],
            'driver' => $this->driver->toArray(),
            'vehicle' => $this->vehicle->toArray(),
            'carbonFootprint' => $this->carbonFootprint,
            'status' => $this->status->value
        ];
    }

    private function validateSeats(int $seats): void
    {
        if ($seats <= 0) {
            throw new InvalidArgumentException('Le nombre de places doit être positif');
        }
        
        if ($seats > 8) {
            throw new InvalidArgumentException('Le nombre maximum de places est de 8');
        }
    }

    private function validateDates(DateTime $departure, DateTime $arrival): void
    {
        if ($departure >= $arrival) {
            throw new InvalidArgumentException('La date d\'arrivée doit être postérieure à la date de départ');
        }
        
        if ($departure <= new DateTime()) {
            throw new InvalidArgumentException('La date de départ doit être dans le futur');
        }
    }
} 