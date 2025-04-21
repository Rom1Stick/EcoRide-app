<?php

namespace App\Models\Entities;

/**
 * Classe représentant un covoiturage dans le système EcoRide
 */
class Trip
{
    private ?int $id = null;
    private int $departureLocationId;
    private int $arrivalLocationId;
    private string $departureDate;
    private string $departureTime;
    private ?string $arrivalDate = null;
    private ?string $arrivalTime = null;
    private int $statusId;
    private int $availableSeats;
    private float $pricePerPerson;
    private int $vehicleId;
    private ?string $creationDate = null;
    private ?float $carbonFootprint = null;
    
    /**
     * Constructeur avec les champs obligatoires
     *
     * @param int $departureLocationId Identifiant du lieu de départ
     * @param int $arrivalLocationId Identifiant du lieu d'arrivée
     * @param string $departureDate Date de départ (format Y-m-d)
     * @param string $departureTime Heure de départ (format H:i:s)
     * @param int $statusId Identifiant du statut
     * @param int $availableSeats Nombre de places disponibles
     * @param float $pricePerPerson Prix par personne
     * @param int $vehicleId Identifiant du véhicule
     */
    public function __construct(
        int $departureLocationId,
        int $arrivalLocationId,
        string $departureDate,
        string $departureTime,
        int $statusId,
        int $availableSeats,
        float $pricePerPerson,
        int $vehicleId
    ) {
        $this->departureLocationId = $departureLocationId;
        $this->arrivalLocationId = $arrivalLocationId;
        $this->departureDate = $departureDate;
        $this->departureTime = $departureTime;
        $this->statusId = $statusId;
        $this->availableSeats = $availableSeats;
        $this->pricePerPerson = $pricePerPerson;
        $this->vehicleId = $vehicleId;
        
        // Date de création par défaut à l'instantiation
        $this->creationDate = date('Y-m-d H:i:s');
    }
    
    /**
     * Valide les données du covoiturage avant persistance
     *
     * @return array Tableau d'erreurs de validation (vide si aucune erreur)
     */
    public function validate(): array
    {
        $errors = [];
        
        if ($this->departureLocationId <= 0) {
            $errors['departureLocationId'] = 'Le lieu de départ est obligatoire';
        }
        
        if ($this->arrivalLocationId <= 0) {
            $errors['arrivalLocationId'] = 'Le lieu d\'arrivée est obligatoire';
        }
        
        if (empty($this->departureDate)) {
            $errors['departureDate'] = 'La date de départ est obligatoire';
        } else {
            $date = \DateTime::createFromFormat('Y-m-d', $this->departureDate);
            if (!$date || $date->format('Y-m-d') !== $this->departureDate) {
                $errors['departureDate'] = 'Format de date de départ invalide (YYYY-MM-DD)';
            }
        }
        
        if (empty($this->departureTime)) {
            $errors['departureTime'] = 'L\'heure de départ est obligatoire';
        } else {
            $time = \DateTime::createFromFormat('H:i:s', $this->departureTime);
            if (!$time || $time->format('H:i:s') !== $this->departureTime) {
                $errors['departureTime'] = 'Format d\'heure de départ invalide (HH:MM:SS)';
            }
        }
        
        if ($this->arrivalDate !== null) {
            $date = \DateTime::createFromFormat('Y-m-d', $this->arrivalDate);
            if (!$date || $date->format('Y-m-d') !== $this->arrivalDate) {
                $errors['arrivalDate'] = 'Format de date d\'arrivée invalide (YYYY-MM-DD)';
            }
            
            // Vérifier que la date d'arrivée est postérieure à la date de départ
            if (empty($errors['departureDate']) && empty($errors['arrivalDate'])) {
                $departureDateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $this->departureDate . ' ' . $this->departureTime);
                $arrivalDateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $this->arrivalDate . ' ' . ($this->arrivalTime ?? '00:00:00'));
                
                if ($arrivalDateTime < $departureDateTime) {
                    $errors['arrivalDate'] = 'La date et heure d\'arrivée doivent être postérieures à la date et heure de départ';
                }
            }
        }
        
        if ($this->arrivalTime !== null) {
            $time = \DateTime::createFromFormat('H:i:s', $this->arrivalTime);
            if (!$time || $time->format('H:i:s') !== $this->arrivalTime) {
                $errors['arrivalTime'] = 'Format d\'heure d\'arrivée invalide (HH:MM:SS)';
            }
        }
        
        if ($this->statusId <= 0) {
            $errors['statusId'] = 'Le statut est obligatoire';
        }
        
        if ($this->availableSeats <= 0) {
            $errors['availableSeats'] = 'Le nombre de places doit être supérieur à 0';
        } elseif ($this->availableSeats > 8) {
            $errors['availableSeats'] = 'Le nombre de places ne peut pas dépasser 8';
        }
        
        if ($this->pricePerPerson < 0) {
            $errors['pricePerPerson'] = 'Le prix par personne ne peut pas être négatif';
        }
        
        if ($this->vehicleId <= 0) {
            $errors['vehicleId'] = 'Le véhicule est obligatoire';
        }
        
        if ($this->carbonFootprint !== null && $this->carbonFootprint < 0) {
            $errors['carbonFootprint'] = 'L\'empreinte carbone ne peut pas être négative';
        }
        
        return $errors;
    }
    
    // Getters & Setters
    
    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    
    public function getDepartureLocationId(): int
    {
        return $this->departureLocationId;
    }
    
    public function setDepartureLocationId(int $departureLocationId): self
    {
        $this->departureLocationId = $departureLocationId;
        return $this;
    }
    
    public function getArrivalLocationId(): int
    {
        return $this->arrivalLocationId;
    }
    
    public function setArrivalLocationId(int $arrivalLocationId): self
    {
        $this->arrivalLocationId = $arrivalLocationId;
        return $this;
    }
    
    public function getDepartureDate(): string
    {
        return $this->departureDate;
    }
    
    public function setDepartureDate(string $departureDate): self
    {
        $this->departureDate = $departureDate;
        return $this;
    }
    
    public function getDepartureTime(): string
    {
        return $this->departureTime;
    }
    
    public function setDepartureTime(string $departureTime): self
    {
        $this->departureTime = $departureTime;
        return $this;
    }
    
    public function getArrivalDate(): ?string
    {
        return $this->arrivalDate;
    }
    
    public function setArrivalDate(?string $arrivalDate): self
    {
        $this->arrivalDate = $arrivalDate;
        return $this;
    }
    
    public function getArrivalTime(): ?string
    {
        return $this->arrivalTime;
    }
    
    public function setArrivalTime(?string $arrivalTime): self
    {
        $this->arrivalTime = $arrivalTime;
        return $this;
    }
    
    public function getStatusId(): int
    {
        return $this->statusId;
    }
    
    public function setStatusId(int $statusId): self
    {
        $this->statusId = $statusId;
        return $this;
    }
    
    public function getAvailableSeats(): int
    {
        return $this->availableSeats;
    }
    
    public function setAvailableSeats(int $availableSeats): self
    {
        $this->availableSeats = $availableSeats;
        return $this;
    }
    
    public function getPricePerPerson(): float
    {
        return $this->pricePerPerson;
    }
    
    public function setPricePerPerson(float $pricePerPerson): self
    {
        $this->pricePerPerson = $pricePerPerson;
        return $this;
    }
    
    public function getVehicleId(): int
    {
        return $this->vehicleId;
    }
    
    public function setVehicleId(int $vehicleId): self
    {
        $this->vehicleId = $vehicleId;
        return $this;
    }
    
    public function getCreationDate(): ?string
    {
        return $this->creationDate;
    }
    
    public function setCreationDate(?string $creationDate): self
    {
        $this->creationDate = $creationDate;
        return $this;
    }
    
    public function getCarbonFootprint(): ?float
    {
        return $this->carbonFootprint;
    }
    
    public function setCarbonFootprint(?float $carbonFootprint): self
    {
        $this->carbonFootprint = $carbonFootprint;
        return $this;
    }
    
    /**
     * Crée une instance de covoiturage à partir d'un tableau de données
     *
     * @param array $data Données du covoiturage
     * @return Trip
     */
    public static function fromArray(array $data): self
    {
        $trip = new self(
            $data['lieu_depart_id'] ?? 0,
            $data['lieu_arrivee_id'] ?? 0,
            $data['date_depart'] ?? '',
            $data['heure_depart'] ?? '',
            $data['statut_id'] ?? 0,
            $data['nb_place'] ?? 0,
            $data['prix_personne'] ?? 0.0,
            $data['voiture_id'] ?? 0
        );
        
        if (isset($data['covoiturage_id'])) {
            $trip->setId((int)$data['covoiturage_id']);
        }
        
        if (isset($data['date_arrivee'])) {
            $trip->setArrivalDate($data['date_arrivee']);
        }
        
        if (isset($data['heure_arrivee'])) {
            $trip->setArrivalTime($data['heure_arrivee']);
        }
        
        if (isset($data['date_creation'])) {
            $trip->setCreationDate($data['date_creation']);
        }
        
        if (isset($data['empreinte_carbone'])) {
            $trip->setCarbonFootprint((float)$data['empreinte_carbone']);
        }
        
        return $trip;
    }
    
    /**
     * Convertit le covoiturage en tableau pour la persistance
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = [
            'lieu_depart_id' => $this->departureLocationId,
            'lieu_arrivee_id' => $this->arrivalLocationId,
            'date_depart' => $this->departureDate,
            'heure_depart' => $this->departureTime,
            'statut_id' => $this->statusId,
            'nb_place' => $this->availableSeats,
            'prix_personne' => $this->pricePerPerson,
            'voiture_id' => $this->vehicleId
        ];
        
        if ($this->id !== null) {
            $data['covoiturage_id'] = $this->id;
        }
        
        if ($this->arrivalDate !== null) {
            $data['date_arrivee'] = $this->arrivalDate;
        }
        
        if ($this->arrivalTime !== null) {
            $data['heure_arrivee'] = $this->arrivalTime;
        }
        
        if ($this->creationDate !== null) {
            $data['date_creation'] = $this->creationDate;
        }
        
        if ($this->carbonFootprint !== null) {
            $data['empreinte_carbone'] = $this->carbonFootprint;
        }
        
        return $data;
    }
} 