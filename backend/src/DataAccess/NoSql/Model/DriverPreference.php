<?php

namespace App\DataAccess\NoSql\Model;

/**
 * Classe DriverPreference
 * 
 * Représente les préférences d'un conducteur dans le système
 */
class DriverPreference
{
    /**
     * Identifiant MongoDB 
     * 
     * @var string|null
     */
    private ?string $id = null;

    /**
     * Identifiant de l'utilisateur conducteur
     * 
     * @var int
     */
    private int $driverId;

    /**
     * Préférences de type de trajet
     * 
     * @var array
     */
    private array $tripTypes = [];

    /**
     * Préférences de durée maximale de trajet
     * 
     * @var int|null
     */
    private ?int $maxTripDuration = null;

    /**
     * Préférences de musique pendant le trajet
     * 
     * @var array
     */
    private array $musicPreferences = [];

    /**
     * Préférences de conversation
     * 
     * @var string
     */
    private string $conversation = 'moderate';

    /**
     * Accepte les animaux
     * 
     * @var bool
     */
    private bool $petsAllowed = false;

    /**
     * Accepte les fumeurs
     * 
     * @var bool
     */
    private bool $smokingAllowed = false;

    /**
     * Accepte les bagages volumineux
     * 
     * @var bool
     */
    private bool $largeLuggageAllowed = true;

    /**
     * Préférence de climatisation
     * 
     * @var string
     */
    private string $airCondition = 'auto';

    /**
     * Nombre d'arrêts maximum autorisé
     * 
     * @var int|null
     */
    private ?int $maxStops = null;

    /**
     * Distance de détour maximum (en km)
     * 
     * @var float|null
     */
    private ?float $maxDetourDistance = null;

    /**
     * Types de passagers préférés
     * 
     * @var array
     */
    private array $preferredPassengerTypes = [];

    /**
     * Rayon de récupération maximum (en km)
     * 
     * @var float|null
     */
    private ?float $maxPickupRadius = null;

    /**
     * Préférences de paiement
     * 
     * @var array
     */
    private array $paymentPreferences = [];

    /**
     * Préférences personnalisées
     * 
     * @var array
     */
    private array $customPreferences = [];

    /**
     * Date de création au format ISO
     * 
     * @var string|null
     */
    private ?string $createdAt = null;

    /**
     * Date de dernière mise à jour au format ISO
     * 
     * @var string|null
     */
    private ?string $updatedAt = null;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->createdAt = (new \DateTime())->format('c');
    }

    /**
     * Récupère l'identifiant
     * 
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Définit l'identifiant
     * 
     * @param string|null $id Identifiant
     * @return self
     */
    public function setId(?string $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Récupère l'identifiant du conducteur
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
     * Récupère les préférences de type de trajet
     * 
     * @return array
     */
    public function getTripTypes(): array
    {
        return $this->tripTypes;
    }

    /**
     * Définit les préférences de type de trajet
     * 
     * @param array $tripTypes Types de trajet
     * @return self
     */
    public function setTripTypes(array $tripTypes): self
    {
        $this->tripTypes = $tripTypes;
        return $this;
    }

    /**
     * Ajoute un type de trajet préféré
     * 
     * @param string $tripType Type de trajet (daily, occasional, longDistance, etc.)
     * @return self
     */
    public function addTripType(string $tripType): self
    {
        if (!in_array($tripType, $this->tripTypes)) {
            $this->tripTypes[] = $tripType;
        }
        return $this;
    }

    /**
     * Récupère la durée maximale de trajet
     * 
     * @return int|null
     */
    public function getMaxTripDuration(): ?int
    {
        return $this->maxTripDuration;
    }

    /**
     * Définit la durée maximale de trajet
     * 
     * @param int|null $maxTripDuration Durée en minutes
     * @return self
     */
    public function setMaxTripDuration(?int $maxTripDuration): self
    {
        $this->maxTripDuration = $maxTripDuration;
        return $this;
    }

    /**
     * Récupère les préférences de musique
     * 
     * @return array
     */
    public function getMusicPreferences(): array
    {
        return $this->musicPreferences;
    }

    /**
     * Définit les préférences de musique
     * 
     * @param array $musicPreferences Préférences de musique
     * @return self
     */
    public function setMusicPreferences(array $musicPreferences): self
    {
        $this->musicPreferences = $musicPreferences;
        return $this;
    }

    /**
     * Ajoute un genre de musique préféré
     * 
     * @param string $genre Genre de musique
     * @return self
     */
    public function addMusicGenre(string $genre): self
    {
        if (!in_array($genre, $this->musicPreferences)) {
            $this->musicPreferences[] = $genre;
        }
        return $this;
    }

    /**
     * Récupère la préférence de conversation
     * 
     * @return string
     */
    public function getConversation(): string
    {
        return $this->conversation;
    }

    /**
     * Définit la préférence de conversation
     * 
     * @param string $conversation Préférence (quiet, moderate, chatty)
     * @return self
     */
    public function setConversation(string $conversation): self
    {
        $allowedValues = ['quiet', 'moderate', 'chatty'];
        
        if (!in_array($conversation, $allowedValues)) {
            throw new \InvalidArgumentException("Valeur non valide pour conversation: $conversation");
        }
        
        $this->conversation = $conversation;
        return $this;
    }

    /**
     * Indique si les animaux sont autorisés
     * 
     * @return bool
     */
    public function getPetsAllowed(): bool
    {
        return $this->petsAllowed;
    }

    /**
     * Définit si les animaux sont autorisés
     * 
     * @param bool $petsAllowed Autorisation
     * @return self
     */
    public function setPetsAllowed(bool $petsAllowed): self
    {
        $this->petsAllowed = $petsAllowed;
        return $this;
    }

    /**
     * Indique si les fumeurs sont autorisés
     * 
     * @return bool
     */
    public function getSmokingAllowed(): bool
    {
        return $this->smokingAllowed;
    }

    /**
     * Définit si les fumeurs sont autorisés
     * 
     * @param bool $smokingAllowed Autorisation
     * @return self
     */
    public function setSmokingAllowed(bool $smokingAllowed): self
    {
        $this->smokingAllowed = $smokingAllowed;
        return $this;
    }

    /**
     * Indique si les bagages volumineux sont autorisés
     * 
     * @return bool
     */
    public function getLargeLuggageAllowed(): bool
    {
        return $this->largeLuggageAllowed;
    }

    /**
     * Définit si les bagages volumineux sont autorisés
     * 
     * @param bool $largeLuggageAllowed Autorisation
     * @return self
     */
    public function setLargeLuggageAllowed(bool $largeLuggageAllowed): self
    {
        $this->largeLuggageAllowed = $largeLuggageAllowed;
        return $this;
    }

    /**
     * Récupère la préférence de climatisation
     * 
     * @return string
     */
    public function getAirCondition(): string
    {
        return $this->airCondition;
    }

    /**
     * Définit la préférence de climatisation
     * 
     * @param string $airCondition Préférence (off, low, auto, high)
     * @return self
     */
    public function setAirCondition(string $airCondition): self
    {
        $allowedValues = ['off', 'low', 'auto', 'high'];
        
        if (!in_array($airCondition, $allowedValues)) {
            throw new \InvalidArgumentException("Valeur non valide pour airCondition: $airCondition");
        }
        
        $this->airCondition = $airCondition;
        return $this;
    }

    /**
     * Récupère le nombre maximum d'arrêts autorisé
     * 
     * @return int|null
     */
    public function getMaxStops(): ?int
    {
        return $this->maxStops;
    }

    /**
     * Définit le nombre maximum d'arrêts autorisé
     * 
     * @param int|null $maxStops Nombre maximum d'arrêts
     * @return self
     */
    public function setMaxStops(?int $maxStops): self
    {
        $this->maxStops = $maxStops;
        return $this;
    }

    /**
     * Récupère la distance de détour maximum
     * 
     * @return float|null
     */
    public function getMaxDetourDistance(): ?float
    {
        return $this->maxDetourDistance;
    }

    /**
     * Définit la distance de détour maximum
     * 
     * @param float|null $maxDetourDistance Distance en km
     * @return self
     */
    public function setMaxDetourDistance(?float $maxDetourDistance): self
    {
        $this->maxDetourDistance = $maxDetourDistance;
        return $this;
    }

    /**
     * Récupère les types de passagers préférés
     * 
     * @return array
     */
    public function getPreferredPassengerTypes(): array
    {
        return $this->preferredPassengerTypes;
    }

    /**
     * Définit les types de passagers préférés
     * 
     * @param array $preferredPassengerTypes Types de passagers
     * @return self
     */
    public function setPreferredPassengerTypes(array $preferredPassengerTypes): self
    {
        $this->preferredPassengerTypes = $preferredPassengerTypes;
        return $this;
    }

    /**
     * Ajoute un type de passager préféré
     * 
     * @param string $passengerType Type de passager
     * @return self
     */
    public function addPreferredPassengerType(string $passengerType): self
    {
        if (!in_array($passengerType, $this->preferredPassengerTypes)) {
            $this->preferredPassengerTypes[] = $passengerType;
        }
        return $this;
    }

    /**
     * Récupère le rayon de récupération maximum
     * 
     * @return float|null
     */
    public function getMaxPickupRadius(): ?float
    {
        return $this->maxPickupRadius;
    }

    /**
     * Définit le rayon de récupération maximum
     * 
     * @param float|null $maxPickupRadius Rayon en km
     * @return self
     */
    public function setMaxPickupRadius(?float $maxPickupRadius): self
    {
        $this->maxPickupRadius = $maxPickupRadius;
        return $this;
    }

    /**
     * Récupère les préférences de paiement
     * 
     * @return array
     */
    public function getPaymentPreferences(): array
    {
        return $this->paymentPreferences;
    }

    /**
     * Définit les préférences de paiement
     * 
     * @param array $paymentPreferences Préférences de paiement
     * @return self
     */
    public function setPaymentPreferences(array $paymentPreferences): self
    {
        $this->paymentPreferences = $paymentPreferences;
        return $this;
    }

    /**
     * Ajoute une préférence de paiement
     * 
     * @param string $paymentMethod Méthode de paiement
     * @return self
     */
    public function addPaymentPreference(string $paymentMethod): self
    {
        if (!in_array($paymentMethod, $this->paymentPreferences)) {
            $this->paymentPreferences[] = $paymentMethod;
        }
        return $this;
    }

    /**
     * Récupère les préférences personnalisées
     * 
     * @return array
     */
    public function getCustomPreferences(): array
    {
        return $this->customPreferences;
    }

    /**
     * Définit les préférences personnalisées
     * 
     * @param array $customPreferences Préférences personnalisées
     * @return self
     */
    public function setCustomPreferences(array $customPreferences): self
    {
        $this->customPreferences = $customPreferences;
        return $this;
    }

    /**
     * Ajoute une préférence personnalisée
     * 
     * @param string $key Clé de la préférence
     * @param mixed $value Valeur de la préférence
     * @return self
     */
    public function setCustomPreference(string $key, $value): self
    {
        $this->customPreferences[$key] = $value;
        return $this;
    }

    /**
     * Récupère une préférence personnalisée
     * 
     * @param string $key Clé de la préférence
     * @param mixed $default Valeur par défaut
     * @return mixed
     */
    public function getCustomPreference(string $key, $default = null)
    {
        return $this->customPreferences[$key] ?? $default;
    }

    /**
     * Récupère la date de création
     * 
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    /**
     * Définit la date de création
     * 
     * @param string|null $createdAt Date de création
     * @return self
     */
    public function setCreatedAt(?string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Récupère la date de mise à jour
     * 
     * @return string|null
     */
    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    /**
     * Définit la date de mise à jour
     * 
     * @param string|null $updatedAt Date de mise à jour
     * @return self
     */
    public function setUpdatedAt(?string $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Met à jour la date de dernière modification
     * 
     * @return self
     */
    public function touch(): self
    {
        $this->updatedAt = (new \DateTime())->format('c');
        return $this;
    }

    /**
     * Convertit l'objet en tableau associatif pour stockage MongoDB
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            '_id' => $this->id,
            'driverId' => $this->driverId,
            'tripTypes' => $this->tripTypes,
            'maxTripDuration' => $this->maxTripDuration,
            'musicPreferences' => $this->musicPreferences,
            'conversation' => $this->conversation,
            'petsAllowed' => $this->petsAllowed,
            'smokingAllowed' => $this->smokingAllowed,
            'largeLuggageAllowed' => $this->largeLuggageAllowed,
            'airCondition' => $this->airCondition,
            'maxStops' => $this->maxStops,
            'maxDetourDistance' => $this->maxDetourDistance,
            'preferredPassengerTypes' => $this->preferredPassengerTypes,
            'maxPickupRadius' => $this->maxPickupRadius,
            'paymentPreferences' => $this->paymentPreferences,
            'customPreferences' => $this->customPreferences,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt
        ];
    }

    /**
     * Crée une instance à partir d'un tableau associatif MongoDB
     * 
     * @param array $data Données
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $preference = new self();
        
        if (isset($data['_id'])) {
            $preference->setId((string)$data['_id']);
        }
        
        if (isset($data['driverId'])) {
            $preference->setDriverId($data['driverId']);
        }
        
        if (isset($data['tripTypes'])) {
            $preference->setTripTypes($data['tripTypes']);
        }
        
        if (isset($data['maxTripDuration'])) {
            $preference->setMaxTripDuration($data['maxTripDuration']);
        }
        
        if (isset($data['musicPreferences'])) {
            $preference->setMusicPreferences($data['musicPreferences']);
        }
        
        if (isset($data['conversation'])) {
            $preference->setConversation($data['conversation']);
        }
        
        if (isset($data['petsAllowed'])) {
            $preference->setPetsAllowed($data['petsAllowed']);
        }
        
        if (isset($data['smokingAllowed'])) {
            $preference->setSmokingAllowed($data['smokingAllowed']);
        }
        
        if (isset($data['largeLuggageAllowed'])) {
            $preference->setLargeLuggageAllowed($data['largeLuggageAllowed']);
        }
        
        if (isset($data['airCondition'])) {
            $preference->setAirCondition($data['airCondition']);
        }
        
        if (isset($data['maxStops'])) {
            $preference->setMaxStops($data['maxStops']);
        }
        
        if (isset($data['maxDetourDistance'])) {
            $preference->setMaxDetourDistance($data['maxDetourDistance']);
        }
        
        if (isset($data['preferredPassengerTypes'])) {
            $preference->setPreferredPassengerTypes($data['preferredPassengerTypes']);
        }
        
        if (isset($data['maxPickupRadius'])) {
            $preference->setMaxPickupRadius($data['maxPickupRadius']);
        }
        
        if (isset($data['paymentPreferences'])) {
            $preference->setPaymentPreferences($data['paymentPreferences']);
        }
        
        if (isset($data['customPreferences'])) {
            $preference->setCustomPreferences($data['customPreferences']);
        }
        
        if (isset($data['createdAt'])) {
            $preference->setCreatedAt($data['createdAt']);
        }
        
        if (isset($data['updatedAt'])) {
            $preference->setUpdatedAt($data['updatedAt']);
        }
        
        return $preference;
    }
} 