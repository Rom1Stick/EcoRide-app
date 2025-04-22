<?php

namespace App\DataAccess\Sql\Entity;

/**
 * Entité représentant un trajet dans le système EcoRide
 */
class Trip
{
    /**
     * Identifiant unique du trajet
     *
     * @var int|null
     */
    private ?int $id;

    /**
     * Identifiant du conducteur
     *
     * @var int
     */
    private int $driverId;

    /**
     * Identifiant du véhicule
     *
     * @var int
     */
    private int $vehicleId;

    /**
     * Ville de départ
     *
     * @var string
     */
    private string $originCity;

    /**
     * Adresse de départ
     *
     * @var string
     */
    private string $originAddress;

    /**
     * Latitude du point de départ
     *
     * @var float
     */
    private float $originLat;

    /**
     * Longitude du point de départ
     *
     * @var float
     */
    private float $originLng;

    /**
     * Ville de destination
     *
     * @var string
     */
    private string $destinationCity;

    /**
     * Adresse de destination
     *
     * @var string
     */
    private string $destinationAddress;

    /**
     * Latitude du point de destination
     *
     * @var float
     */
    private float $destinationLat;

    /**
     * Longitude du point de destination
     *
     * @var float
     */
    private float $destinationLng;

    /**
     * Date et heure de départ prévues
     *
     * @var \DateTime
     */
    private \DateTime $departureTime;

    /**
     * Date et heure d'arrivée estimées
     *
     * @var \DateTime|null
     */
    private ?\DateTime $estimatedArrivalTime;

    /**
     * Distance totale en kilomètres
     *
     * @var float
     */
    private float $distance;

    /**
     * Durée estimée en minutes
     *
     * @var int
     */
    private int $duration;

    /**
     * Prix par passager en euros
     *
     * @var float
     */
    private float $pricePerSeat;

    /**
     * Nombre de places disponibles
     *
     * @var int
     */
    private int $availableSeats;

    /**
     * Options disponibles (séparées par des virgules)
     * Ex: "animaux,bagages,musique"
     *
     * @var string|null
     */
    private ?string $options;

    /**
     * Commentaires du conducteur
     *
     * @var string|null
     */
    private ?string $driverNotes;

    /**
     * Statut du trajet
     * (scheduled, in_progress, completed, cancelled)
     *
     * @var string
     */
    private string $status;

    /**
     * Si le trajet est récurrent
     *
     * @var bool
     */
    private bool $isRecurring;

    /**
     * Fréquence de récurrence si applicable
     * (daily, weekly, workdays, weekends)
     *
     * @var string|null
     */
    private ?string $recurrencePattern;

    /**
     * Nombre estimé de kg de CO2 économisés
     *
     * @var float|null
     */
    private ?float $co2Saved;

    /**
     * Date de création
     *
     * @var \DateTime
     */
    private \DateTime $createdAt;

    /**
     * Date de mise à jour
     *
     * @var \DateTime
     */
    private \DateTime $updatedAt;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->id = null;
        $this->estimatedArrivalTime = null;
        $this->options = null;
        $this->driverNotes = null;
        $this->status = 'scheduled';
        $this->isRecurring = false;
        $this->recurrencePattern = null;
        $this->co2Saved = null;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    /**
     * Obtient l'identifiant du trajet
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Définit l'identifiant du trajet
     *
     * @param int $id Identifiant
     * @return self
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Obtient l'identifiant du conducteur
     *
     * @return int
     */
    public function getDriverId(): int
    {
        return $this->driverId;
    }

    /**
     * Définit l'identifiant du conducteur
     *
     * @param int $driverId Identifiant du conducteur
     * @return self
     */
    public function setDriverId(int $driverId): self
    {
        $this->driverId = $driverId;
        return $this;
    }

    /**
     * Obtient l'identifiant du véhicule
     *
     * @return int
     */
    public function getVehicleId(): int
    {
        return $this->vehicleId;
    }

    /**
     * Définit l'identifiant du véhicule
     *
     * @param int $vehicleId Identifiant du véhicule
     * @return self
     */
    public function setVehicleId(int $vehicleId): self
    {
        $this->vehicleId = $vehicleId;
        return $this;
    }

    /**
     * Obtient la ville d'origine
     *
     * @return string
     */
    public function getOriginCity(): string
    {
        return $this->originCity;
    }

    /**
     * Définit la ville d'origine
     *
     * @param string $originCity Ville de départ
     * @return self
     */
    public function setOriginCity(string $originCity): self
    {
        $this->originCity = $originCity;
        return $this;
    }

    /**
     * Obtient l'adresse d'origine
     *
     * @return string
     */
    public function getOriginAddress(): string
    {
        return $this->originAddress;
    }

    /**
     * Définit l'adresse d'origine
     *
     * @param string $originAddress Adresse de départ
     * @return self
     */
    public function setOriginAddress(string $originAddress): self
    {
        $this->originAddress = $originAddress;
        return $this;
    }

    /**
     * Obtient la latitude du point de départ
     *
     * @return float
     */
    public function getOriginLat(): float
    {
        return $this->originLat;
    }

    /**
     * Définit la latitude du point de départ
     *
     * @param float $originLat Latitude
     * @return self
     */
    public function setOriginLat(float $originLat): self
    {
        $this->originLat = $originLat;
        return $this;
    }

    /**
     * Obtient la longitude du point de départ
     *
     * @return float
     */
    public function getOriginLng(): float
    {
        return $this->originLng;
    }

    /**
     * Définit la longitude du point de départ
     *
     * @param float $originLng Longitude
     * @return self
     */
    public function setOriginLng(float $originLng): self
    {
        $this->originLng = $originLng;
        return $this;
    }

    /**
     * Obtient la ville de destination
     *
     * @return string
     */
    public function getDestinationCity(): string
    {
        return $this->destinationCity;
    }

    /**
     * Définit la ville de destination
     *
     * @param string $destinationCity Ville de destination
     * @return self
     */
    public function setDestinationCity(string $destinationCity): self
    {
        $this->destinationCity = $destinationCity;
        return $this;
    }

    /**
     * Obtient l'adresse de destination
     *
     * @return string
     */
    public function getDestinationAddress(): string
    {
        return $this->destinationAddress;
    }

    /**
     * Définit l'adresse de destination
     *
     * @param string $destinationAddress Adresse de destination
     * @return self
     */
    public function setDestinationAddress(string $destinationAddress): self
    {
        $this->destinationAddress = $destinationAddress;
        return $this;
    }

    /**
     * Obtient la latitude du point de destination
     *
     * @return float
     */
    public function getDestinationLat(): float
    {
        return $this->destinationLat;
    }

    /**
     * Définit la latitude du point de destination
     *
     * @param float $destinationLat Latitude
     * @return self
     */
    public function setDestinationLat(float $destinationLat): self
    {
        $this->destinationLat = $destinationLat;
        return $this;
    }

    /**
     * Obtient la longitude du point de destination
     *
     * @return float
     */
    public function getDestinationLng(): float
    {
        return $this->destinationLng;
    }

    /**
     * Définit la longitude du point de destination
     *
     * @param float $destinationLng Longitude
     * @return self
     */
    public function setDestinationLng(float $destinationLng): self
    {
        $this->destinationLng = $destinationLng;
        return $this;
    }

    /**
     * Obtient la date et l'heure de départ
     *
     * @return \DateTime
     */
    public function getDepartureTime(): \DateTime
    {
        return $this->departureTime;
    }

    /**
     * Définit la date et l'heure de départ
     *
     * @param \DateTime $departureTime Date et heure de départ
     * @return self
     */
    public function setDepartureTime(\DateTime $departureTime): self
    {
        $this->departureTime = $departureTime;
        return $this;
    }

    /**
     * Obtient la date et l'heure d'arrivée estimées
     *
     * @return \DateTime|null
     */
    public function getEstimatedArrivalTime(): ?\DateTime
    {
        return $this->estimatedArrivalTime;
    }

    /**
     * Définit la date et l'heure d'arrivée estimées
     *
     * @param \DateTime|null $estimatedArrivalTime Date et heure d'arrivée
     * @return self
     */
    public function setEstimatedArrivalTime(?\DateTime $estimatedArrivalTime): self
    {
        $this->estimatedArrivalTime = $estimatedArrivalTime;
        return $this;
    }

    /**
     * Obtient la distance totale
     *
     * @return float
     */
    public function getDistance(): float
    {
        return $this->distance;
    }

    /**
     * Définit la distance totale
     *
     * @param float $distance Distance en km
     * @return self
     */
    public function setDistance(float $distance): self
    {
        $this->distance = $distance;
        return $this;
    }

    /**
     * Obtient la durée estimée
     *
     * @return int
     */
    public function getDuration(): int
    {
        return $this->duration;
    }

    /**
     * Définit la durée estimée
     *
     * @param int $duration Durée en minutes
     * @return self
     */
    public function setDuration(int $duration): self
    {
        $this->duration = $duration;
        return $this;
    }

    /**
     * Obtient le prix par place
     *
     * @return float
     */
    public function getPricePerSeat(): float
    {
        return $this->pricePerSeat;
    }

    /**
     * Définit le prix par place
     *
     * @param float $pricePerSeat Prix en euros
     * @return self
     */
    public function setPricePerSeat(float $pricePerSeat): self
    {
        $this->pricePerSeat = $pricePerSeat;
        return $this;
    }

    /**
     * Obtient le nombre de places disponibles
     *
     * @return int
     */
    public function getAvailableSeats(): int
    {
        return $this->availableSeats;
    }

    /**
     * Définit le nombre de places disponibles
     *
     * @param int $availableSeats Nombre de places
     * @return self
     */
    public function setAvailableSeats(int $availableSeats): self
    {
        $this->availableSeats = $availableSeats;
        return $this;
    }

    /**
     * Obtient les options disponibles
     *
     * @return string|null
     */
    public function getOptions(): ?string
    {
        return $this->options;
    }

    /**
     * Définit les options disponibles
     *
     * @param string|null $options Options séparées par des virgules
     * @return self
     */
    public function setOptions(?string $options): self
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Obtient les commentaires du conducteur
     *
     * @return string|null
     */
    public function getDriverNotes(): ?string
    {
        return $this->driverNotes;
    }

    /**
     * Définit les commentaires du conducteur
     *
     * @param string|null $driverNotes Commentaires
     * @return self
     */
    public function setDriverNotes(?string $driverNotes): self
    {
        $this->driverNotes = $driverNotes;
        return $this;
    }

    /**
     * Obtient le statut du trajet
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Définit le statut du trajet
     *
     * @param string $status Statut
     * @return self
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Vérifie si le trajet est récurrent
     *
     * @return bool
     */
    public function isRecurring(): bool
    {
        return $this->isRecurring;
    }

    /**
     * Définit si le trajet est récurrent
     *
     * @param bool $isRecurring Récurrence
     * @return self
     */
    public function setIsRecurring(bool $isRecurring): self
    {
        $this->isRecurring = $isRecurring;
        return $this;
    }

    /**
     * Obtient le modèle de récurrence
     *
     * @return string|null
     */
    public function getRecurrencePattern(): ?string
    {
        return $this->recurrencePattern;
    }

    /**
     * Définit le modèle de récurrence
     *
     * @param string|null $recurrencePattern Modèle de récurrence
     * @return self
     */
    public function setRecurrencePattern(?string $recurrencePattern): self
    {
        $this->recurrencePattern = $recurrencePattern;
        return $this;
    }

    /**
     * Obtient la quantité de CO2 économisée
     *
     * @return float|null
     */
    public function getCo2Saved(): ?float
    {
        return $this->co2Saved;
    }

    /**
     * Définit la quantité de CO2 économisée
     *
     * @param float|null $co2Saved CO2 économisé en kg
     * @return self
     */
    public function setCo2Saved(?float $co2Saved): self
    {
        $this->co2Saved = $co2Saved;
        return $this;
    }

    /**
     * Obtient la date de création
     *
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * Définit la date de création
     *
     * @param \DateTime $createdAt Date de création
     * @return self
     */
    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Obtient la date de mise à jour
     *
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Définit la date de mise à jour
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
     * Met à jour la date de modification
     *
     * @return void
     */
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * Calcule l'heure d'arrivée estimée
     *
     * @return void
     */
    public function calculateEstimatedArrivalTime(): void
    {
        $arrivalTime = clone $this->departureTime;
        $arrivalTime->modify("+{$this->duration} minutes");
        $this->estimatedArrivalTime = $arrivalTime;
    }

    /**
     * Calcule le CO2 économisé par rapport à des déplacements individuels
     * Utilise une estimation basée sur la distance et le nombre de passagers
     *
     * @return void
     */
    public function calculateCO2Savings(): void
    {
        // On estime qu'une voiture moyenne émet 120g de CO2 par km
        // On multiplie par le nombre de voitures "économisées" (passagers)
        $defaultEmissionPerKm = 0.12; // 120g/km = 0.12kg/km
        
        // Nombre de passagers potentiels (hors conducteur)
        $potentialPassengers = $this->availableSeats;
        
        // CO2 économisé = distance * émission moyenne * passagers potentiels
        if ($potentialPassengers > 0) {
            $this->co2Saved = $this->distance * $defaultEmissionPerKm * $potentialPassengers;
        } else {
            $this->co2Saved = 0.0;
        }
    }

    /**
     * Vérifie si le trajet est annulable
     * Un trajet est annulable s'il n'a pas encore commencé
     *
     * @return bool
     */
    public function isCancellable(): bool
    {
        return $this->status === 'scheduled' && 
               $this->departureTime > new \DateTime();
    }

    /**
     * Vérifie si le trajet est modifiable
     * Un trajet est modifiable s'il n'a pas encore commencé
     * et s'il reste au moins 1h avant le départ
     *
     * @return bool
     */
    public function isModifiable(): bool
    {
        if ($this->status !== 'scheduled') {
            return false;
        }

        $now = new \DateTime();
        $interval = $now->diff($this->departureTime);
        $hoursRemaining = $interval->h + ($interval->days * 24);
        
        return $hoursRemaining >= 1;
    }

    /**
     * Convertit l'objet en tableau
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'driverId' => $this->driverId,
            'vehicleId' => $this->vehicleId,
            'originCity' => $this->originCity,
            'originAddress' => $this->originAddress,
            'originLat' => $this->originLat,
            'originLng' => $this->originLng,
            'destinationCity' => $this->destinationCity,
            'destinationAddress' => $this->destinationAddress,
            'destinationLat' => $this->destinationLat,
            'destinationLng' => $this->destinationLng,
            'departureTime' => $this->departureTime->format('Y-m-d H:i:s'),
            'estimatedArrivalTime' => $this->estimatedArrivalTime ? 
                $this->estimatedArrivalTime->format('Y-m-d H:i:s') : null,
            'distance' => $this->distance,
            'duration' => $this->duration,
            'pricePerSeat' => $this->pricePerSeat,
            'availableSeats' => $this->availableSeats,
            'options' => $this->options,
            'driverNotes' => $this->driverNotes,
            'status' => $this->status,
            'isRecurring' => $this->isRecurring,
            'recurrencePattern' => $this->recurrencePattern,
            'co2Saved' => $this->co2Saved,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Crée un objet à partir d'un tableau
     *
     * @param array $data Données
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $trip = new self();

        if (isset($data['id'])) {
            $trip->setId($data['id']);
        }

        if (isset($data['driverId'])) {
            $trip->setDriverId($data['driverId']);
        }

        if (isset($data['vehicleId'])) {
            $trip->setVehicleId($data['vehicleId']);
        }

        if (isset($data['originCity'])) {
            $trip->setOriginCity($data['originCity']);
        }

        if (isset($data['originAddress'])) {
            $trip->setOriginAddress($data['originAddress']);
        }

        if (isset($data['originLat'])) {
            $trip->setOriginLat((float)$data['originLat']);
        }

        if (isset($data['originLng'])) {
            $trip->setOriginLng((float)$data['originLng']);
        }

        if (isset($data['destinationCity'])) {
            $trip->setDestinationCity($data['destinationCity']);
        }

        if (isset($data['destinationAddress'])) {
            $trip->setDestinationAddress($data['destinationAddress']);
        }

        if (isset($data['destinationLat'])) {
            $trip->setDestinationLat((float)$data['destinationLat']);
        }

        if (isset($data['destinationLng'])) {
            $trip->setDestinationLng((float)$data['destinationLng']);
        }

        if (isset($data['departureTime'])) {
            $trip->setDepartureTime(new \DateTime($data['departureTime']));
        }

        if (isset($data['estimatedArrivalTime']) && $data['estimatedArrivalTime']) {
            $trip->setEstimatedArrivalTime(new \DateTime($data['estimatedArrivalTime']));
        }

        if (isset($data['distance'])) {
            $trip->setDistance((float)$data['distance']);
        }

        if (isset($data['duration'])) {
            $trip->setDuration((int)$data['duration']);
        }

        if (isset($data['pricePerSeat'])) {
            $trip->setPricePerSeat((float)$data['pricePerSeat']);
        }

        if (isset($data['availableSeats'])) {
            $trip->setAvailableSeats((int)$data['availableSeats']);
        }

        if (isset($data['options'])) {
            $trip->setOptions($data['options']);
        }

        if (isset($data['driverNotes'])) {
            $trip->setDriverNotes($data['driverNotes']);
        }

        if (isset($data['status'])) {
            $trip->setStatus($data['status']);
        }

        if (isset($data['isRecurring'])) {
            $trip->setIsRecurring((bool)$data['isRecurring']);
        }

        if (isset($data['recurrencePattern'])) {
            $trip->setRecurrencePattern($data['recurrencePattern']);
        }

        if (isset($data['co2Saved'])) {
            $trip->setCo2Saved((float)$data['co2Saved']);
        }

        if (isset($data['createdAt'])) {
            $trip->setCreatedAt(new \DateTime($data['createdAt']));
        }

        if (isset($data['updatedAt'])) {
            $trip->setUpdatedAt(new \DateTime($data['updatedAt']));
        }

        return $trip;
    }
} 