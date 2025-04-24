<?php

namespace App\DataAccess\NoSql\Model;

use DateTime;
use JsonSerializable;

/**
 * Modèle pour les logs d'application dans MongoDB
 */
class Log implements JsonSerializable
{
    /**
     * Identifiant MongoDB
     *
     * @var string|null
     */
    private ?string $id = null;
    
    /**
     * Horodatage de l'événement
     *
     * @var string
     */
    private string $timestamp;
    
    /**
     * Niveau de log (info, warning, error, debug)
     *
     * @var string
     */
    private string $level = 'info';
    
    /**
     * Service ou module source
     *
     * @var string
     */
    private string $service = '';
    
    /**
     * Message de log
     *
     * @var string
     */
    private string $message = '';
    
    /**
     * Données supplémentaires (contexte)
     *
     * @var array
     */
    private array $meta = [];
    
    /**
     * Constructeur
     *
     * @param string $message Message de log
     * @param string $level Niveau de log
     * @param string $service Service ou module source
     * @param array $meta Métadonnées complémentaires
     */
    public function __construct(string $message = '', string $level = 'info', string $service = '', array $meta = [])
    {
        $this->timestamp = (new DateTime())->format('Y-m-d H:i:s');
        $this->level = $level;
        $this->service = $service;
        $this->message = $message;
        $this->meta = $meta;
    }
    
    /**
     * Obtenir l'ID
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }
    
    /**
     * Définir l'ID
     *
     * @param string $id
     * @return self
     */
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }
    
    /**
     * Obtenir l'horodatage
     *
     * @return string
     */
    public function getTimestamp(): string
    {
        return $this->timestamp;
    }
    
    /**
     * Définir l'horodatage
     *
     * @param string $timestamp
     * @return self
     */
    public function setTimestamp(string $timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
    }
    
    /**
     * Obtenir le niveau de log
     *
     * @return string
     */
    public function getLevel(): string
    {
        return $this->level;
    }
    
    /**
     * Définir le niveau de log
     *
     * @param string $level
     * @return self
     */
    public function setLevel(string $level): self
    {
        $this->level = $level;
        return $this;
    }
    
    /**
     * Obtenir le service source
     *
     * @return string
     */
    public function getService(): string
    {
        return $this->service;
    }
    
    /**
     * Définir le service source
     *
     * @param string $service
     * @return self
     */
    public function setService(string $service): self
    {
        $this->service = $service;
        return $this;
    }
    
    /**
     * Obtenir le message
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }
    
    /**
     * Définir le message
     *
     * @param string $message
     * @return self
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }
    
    /**
     * Obtenir les métadonnées
     *
     * @return array
     */
    public function getMeta(): array
    {
        return $this->meta;
    }
    
    /**
     * Définir les métadonnées
     *
     * @param array $meta
     * @return self
     */
    public function setMeta(array $meta): self
    {
        $this->meta = $meta;
        return $this;
    }
    
    /**
     * Ajouter une métadonnée
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function addMeta(string $key, $value): self
    {
        $this->meta[$key] = $value;
        return $this;
    }
    
    /**
     * Sérialiser l'objet pour JSON
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            '_id' => $this->id,
            'timestamp' => $this->timestamp,
            'niveau' => $this->level,
            'service' => $this->service,
            'message' => $this->message,
            'meta' => $this->meta
        ];
    }
    
    /**
     * Créer une instance à partir d'un tableau
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $log = new self();
        
        if (isset($data['_id'])) {
            $log->setId((string)$data['_id']);
        }
        
        if (isset($data['timestamp'])) {
            $log->setTimestamp($data['timestamp']);
        }
        
        if (isset($data['niveau'])) {
            $log->setLevel($data['niveau']);
        }
        
        if (isset($data['service'])) {
            $log->setService($data['service']);
        }
        
        if (isset($data['message'])) {
            $log->setMessage($data['message']);
        }
        
        if (isset($data['meta'])) {
            $log->setMeta((array)$data['meta']);
        }
        
        return $log;
    }
} 