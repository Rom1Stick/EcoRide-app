<?php

namespace App\Models\Entities;

use DateTime;
use MongoDB\BSON\UTCDateTime;

/**
 * Classe représentant les préférences utilisateur stockées dans MongoDB
 */
class UserPreference
{
    public string $id;
    public int $user_id;
    public array $preferences;
    public UTCDateTime $created_at;
    public ?UTCDateTime $updated_at;
    
    /**
     * Constructeur
     *
     * @param int $userId ID de l'utilisateur
     * @param array $preferences Préférences initiales (optionnel)
     */
    public function __construct(int $userId, array $preferences = [])
    {
        $this->id = uniqid('pref_');
        $this->user_id = $userId;
        $this->preferences = $preferences;
        $this->created_at = new UTCDateTime(new DateTime());
        $this->updated_at = null;
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
            'user_id' => $this->user_id,
            'preferences' => $this->preferences,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
    
    /**
     * Crée une instance à partir d'un tableau
     *
     * @param array $data Données
     * @return self Instance
     */
    public static function fromArray(array $data): self
    {
        $preferences = new self(
            $data['user_id'],
            $data['preferences'] ?? []
        );
        
        $preferences->id = $data['id'];
        
        if (isset($data['created_at'])) {
            $preferences->created_at = $data['created_at'] instanceof UTCDateTime
                ? $data['created_at']
                : new UTCDateTime(new DateTime($data['created_at']));
        }
        
        if (isset($data['updated_at'])) {
            $preferences->updated_at = $data['updated_at'] instanceof UTCDateTime
                ? $data['updated_at']
                : new UTCDateTime(new DateTime($data['updated_at']));
        }
        
        return $preferences;
    }
    
    /**
     * Récupère une préférence utilisateur
     *
     * @param string $key Clé de la préférence
     * @param mixed $default Valeur par défaut si la préférence n'existe pas
     * @return mixed Valeur de la préférence
     */
    public function getPreference(string $key, $default = null)
    {
        return $this->preferences[$key] ?? $default;
    }
    
    /**
     * Définit une préférence utilisateur
     *
     * @param string $key Clé de la préférence
     * @param mixed $value Valeur de la préférence
     * @return self
     */
    public function setPreference(string $key, $value): self
    {
        $this->preferences[$key] = $value;
        $this->updated_at = new UTCDateTime(new DateTime());
        return $this;
    }
    
    /**
     * Définit plusieurs préférences utilisateur en une fois
     *
     * @param array $preferences Tableau associatif de préférences
     * @return self
     */
    public function setPreferences(array $preferences): self
    {
        foreach ($preferences as $key => $value) {
            $this->preferences[$key] = $value;
        }
        
        $this->updated_at = new UTCDateTime(new DateTime());
        return $this;
    }
    
    /**
     * Vérifie si une préférence existe
     *
     * @param string $key Clé de la préférence
     * @return bool
     */
    public function hasPreference(string $key): bool
    {
        return array_key_exists($key, $this->preferences);
    }
    
    /**
     * Supprime une préférence
     *
     * @param string $key Clé de la préférence
     * @return self
     */
    public function removePreference(string $key): self
    {
        if ($this->hasPreference($key)) {
            unset($this->preferences[$key]);
            $this->updated_at = new UTCDateTime(new DateTime());
        }
        
        return $this;
    }
    
    /**
     * Réinitialise toutes les préférences
     *
     * @return self
     */
    public function resetPreferences(): self
    {
        $this->preferences = [];
        $this->updated_at = new UTCDateTime(new DateTime());
        return $this;
    }
    
    /**
     * Récupère toutes les préférences
     *
     * @return array
     */
    public function getAllPreferences(): array
    {
        return $this->preferences;
    }
    
    /**
     * Vérifie si l'utilisateur a des préférences
     *
     * @return bool
     */
    public function hasPreferences(): bool
    {
        return !empty($this->preferences);
    }
    
    /**
     * Convertit la date de création en objet DateTime
     *
     * @return DateTime
     */
    public function getCreatedAtDateTime(): DateTime
    {
        return $this->created_at->toDateTime();
    }
    
    /**
     * Convertit la date de mise à jour en objet DateTime
     *
     * @return DateTime|null
     */
    public function getUpdatedAtDateTime(): ?DateTime
    {
        return $this->updated_at ? $this->updated_at->toDateTime() : null;
    }
} 