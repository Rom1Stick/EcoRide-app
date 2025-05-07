<?php

namespace App\DataAccess\NoSql\Model;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use DateTime;

/**
 * Modèle représentant un journal d'activité dans MongoDB
 */
class ActivityLog
{
    /**
     * ID du journal
     *
     * @var ObjectId|null
     */
    private ?ObjectId $_id;

    /**
     * ID de l'utilisateur concerné (optionnel)
     *
     * @var int|null
     */
    private ?int $userId;

    /**
     * ID du trajet concerné (optionnel)
     *
     * @var int|null
     */
    private ?int $tripId;

    /**
     * ID de la réservation concernée (optionnel)
     *
     * @var int|null
     */
    private ?int $bookingId;

    /**
     * Type d'événement
     *
     * @var string
     */
    private string $eventType;

    /**
     * Niveau de l'événement (info, warning, error, critical)
     *
     * @var string
     */
    private string $level;

    /**
     * Description de l'événement
     *
     * @var string
     */
    private string $description;

    /**
     * Données supplémentaires (JSON)
     *
     * @var array|null
     */
    private ?array $data;

    /**
     * Source de l'événement (API, web, mobile, système)
     *
     * @var string
     */
    private string $source;

    /**
     * Adresse IP de la source
     *
     * @var string|null
     */
    private ?string $ipAddress;

    /**
     * Timestamp de création
     *
     * @var UTCDateTime
     */
    private UTCDateTime $timestamp;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->_id = null;
        $this->userId = null;
        $this->tripId = null;
        $this->bookingId = null;
        $this->data = null;
        $this->ipAddress = null;
        $this->timestamp = new UTCDateTime(new DateTime());
    }

    /**
     * Obtient l'ID
     * 
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->_id !== null ? (string)$this->_id : null;
    }

    /**
     * Définit l'ID
     * 
     * @param string $id ID à définir
     * @return self
     */
    public function setId(string $id): self
    {
        $this->_id = new ObjectId($id);
        return $this;
    }

    /**
     * Obtient l'ID utilisateur
     * 
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * Définit l'ID utilisateur
     * 
     * @param int|null $userId ID utilisateur
     * @return self
     */
    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Obtient l'ID du trajet
     * 
     * @return int|null
     */
    public function getTripId(): ?int
    {
        return $this->tripId;
    }

    /**
     * Définit l'ID du trajet
     * 
     * @param int|null $tripId ID du trajet
     * @return self
     */
    public function setTripId(?int $tripId): self
    {
        $this->tripId = $tripId;
        return $this;
    }

    /**
     * Obtient l'ID de la réservation
     * 
     * @return int|null
     */
    public function getBookingId(): ?int
    {
        return $this->bookingId;
    }

    /**
     * Définit l'ID de la réservation
     * 
     * @param int|null $bookingId ID de la réservation
     * @return self
     */
    public function setBookingId(?int $bookingId): self
    {
        $this->bookingId = $bookingId;
        return $this;
    }

    /**
     * Obtient le type d'événement
     * 
     * @return string
     */
    public function getEventType(): string
    {
        return $this->eventType;
    }

    /**
     * Définit le type d'événement
     * 
     * @param string $eventType Type d'événement
     * @return self
     */
    public function setEventType(string $eventType): self
    {
        $this->eventType = $eventType;
        return $this;
    }

    /**
     * Obtient le niveau
     * 
     * @return string
     */
    public function getLevel(): string
    {
        return $this->level;
    }

    /**
     * Définit le niveau
     * 
     * @param string $level Niveau
     * @return self
     */
    public function setLevel(string $level): self
    {
        $this->level = $level;
        return $this;
    }

    /**
     * Obtient la description
     * 
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Définit la description
     * 
     * @param string $description Description
     * @return self
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Obtient les données supplémentaires
     * 
     * @return array|null
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * Définit les données supplémentaires
     * 
     * @param array|null $data Données
     * @return self
     */
    public function setData(?array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Obtient la source
     * 
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Définit la source
     * 
     * @param string $source Source
     * @return self
     */
    public function setSource(string $source): self
    {
        $this->source = $source;
        return $this;
    }

    /**
     * Obtient l'adresse IP
     * 
     * @return string|null
     */
    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    /**
     * Définit l'adresse IP
     * 
     * @param string|null $ipAddress Adresse IP
     * @return self
     */
    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    /**
     * Obtient le timestamp
     * 
     * @return DateTime
     */
    public function getTimestamp(): DateTime
    {
        return $this->timestamp->toDateTime();
    }

    /**
     * Définit le timestamp
     * 
     * @param DateTime $timestamp Timestamp
     * @return self
     */
    public function setTimestamp(DateTime $timestamp): self
    {
        $this->timestamp = new UTCDateTime($timestamp);
        return $this;
    }

    /**
     * Convertit l'objet en tableau
     * 
     * @return array
     */
    public function toArray(): array
    {
        $data = [
            'eventType' => $this->eventType,
            'level' => $this->level,
            'description' => $this->description,
            'source' => $this->source,
            'timestamp' => $this->timestamp
        ];

        if ($this->_id !== null) {
            $data['_id'] = $this->_id;
        }

        if ($this->userId !== null) {
            $data['userId'] = $this->userId;
        }

        if ($this->tripId !== null) {
            $data['tripId'] = $this->tripId;
        }

        if ($this->bookingId !== null) {
            $data['bookingId'] = $this->bookingId;
        }

        if ($this->data !== null) {
            $data['data'] = $this->data;
        }

        if ($this->ipAddress !== null) {
            $data['ipAddress'] = $this->ipAddress;
        }

        return $data;
    }

    /**
     * Crée un objet à partir d'un tableau
     * 
     * @param array $data Données
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $log = new self();

        if (isset($data['_id'])) {
            $log->_id = $data['_id'];
        }

        if (isset($data['userId'])) {
            $log->userId = $data['userId'];
        }

        if (isset($data['tripId'])) {
            $log->tripId = $data['tripId'];
        }

        if (isset($data['bookingId'])) {
            $log->bookingId = $data['bookingId'];
        }

        if (isset($data['eventType'])) {
            $log->eventType = $data['eventType'];
        }

        if (isset($data['level'])) {
            $log->level = $data['level'];
        }

        if (isset($data['description'])) {
            $log->description = $data['description'];
        }

        if (isset($data['data'])) {
            $log->data = $data['data'];
        }

        if (isset($data['source'])) {
            $log->source = $data['source'];
        }

        if (isset($data['ipAddress'])) {
            $log->ipAddress = $data['ipAddress'];
        }

        if (isset($data['timestamp'])) {
            $log->timestamp = $data['timestamp'];
        }

        return $log;
    }
} 