<?php

namespace App\DataAccess\NoSql\Model;

/**
 * Classe UserPreference
 * 
 * Représente les préférences d'un utilisateur stockées dans MongoDB
 */
class UserPreference
{
    /**
     * ID MongoDB (ObjectId)
     * 
     * @var string|null
     */
    private ?string $id;

    /**
     * ID de l'utilisateur (référence à la table MySQL)
     * 
     * @var int
     */
    private int $userId;

    /**
     * Préférences standard
     * 
     * @var array
     */
    private array $standard;

    /**
     * Préférences personnalisées
     * 
     * @var array
     */
    private array $custom;

    /**
     * Date de dernière mise à jour
     * 
     * @var string|null
     */
    private ?string $lastUpdated;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->id = null;
        $this->standard = [];
        $this->custom = [];
        $this->lastUpdated = null;
    }

    /**
     * Get identifiant MongoDB
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Set identifiant MongoDB
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
     * Get ID de l'utilisateur
     *
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * Set ID de l'utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @return self
     */
    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Get préférences standard
     *
     * @return array
     */
    public function getStandard(): array
    {
        return $this->standard;
    }

    /**
     * Set préférences standard
     *
     * @param array $standard Préférences standard
     * @return self
     */
    public function setStandard(array $standard): self
    {
        $this->standard = $standard;
        return $this;
    }

    /**
     * Get préférences personnalisées
     *
     * @return array
     */
    public function getCustom(): array
    {
        return $this->custom;
    }

    /**
     * Set préférences personnalisées
     *
     * @param array $custom Préférences personnalisées
     * @return self
     */
    public function setCustom(array $custom): self
    {
        $this->custom = $custom;
        return $this;
    }

    /**
     * Get date de dernière mise à jour
     *
     * @return string|null
     */
    public function getLastUpdated(): ?string
    {
        return $this->lastUpdated;
    }

    /**
     * Set date de dernière mise à jour
     *
     * @param string|null $lastUpdated Date de mise à jour
     * @return self
     */
    public function setLastUpdated(?string $lastUpdated): self
    {
        $this->lastUpdated = $lastUpdated;
        return $this;
    }

    /**
     * Récupère une préférence standard par sa clé
     *
     * @param string $key Clé de la préférence
     * @param mixed $default Valeur par défaut si la clé n'existe pas
     * @return mixed Valeur de la préférence
     */
    public function getStandardPreference(string $key, $default = null)
    {
        return $this->standard[$key] ?? $default;
    }

    /**
     * Définit une préférence standard
     *
     * @param string $key Clé de la préférence
     * @param mixed $value Valeur de la préférence
     * @return self
     */
    public function setStandardPreference(string $key, $value): self
    {
        $this->standard[$key] = $value;
        return $this;
    }

    /**
     * Récupère une préférence personnalisée par sa clé
     *
     * @param string $key Clé de la préférence
     * @param mixed $default Valeur par défaut si la clé n'existe pas
     * @return mixed Valeur de la préférence
     */
    public function getCustomPreference(string $key, $default = null)
    {
        foreach ($this->custom as $preference) {
            if ($preference['key'] === $key) {
                return $preference['value'];
            }
        }
        
        return $default;
    }

    /**
     * Définit une préférence personnalisée
     *
     * @param string $key Clé de la préférence
     * @param mixed $value Valeur de la préférence
     * @return self
     */
    public function setCustomPreference(string $key, $value): self
    {
        $found = false;
        
        foreach ($this->custom as &$preference) {
            if ($preference['key'] === $key) {
                $preference['value'] = $value;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $this->custom[] = [
                'key' => $key,
                'value' => $value
            ];
        }
        
        return $this;
    }

    /**
     * Convertit l'objet en tableau pour MongoDB
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'userId' => $this->userId,
            'standard' => $this->standard,
            'custom' => $this->custom,
        ];
    }

    /**
     * Crée une instance à partir d'un document MongoDB
     *
     * @param array $data Données du document
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $preference = new self();
        
        if (isset($data['_id'])) {
            $preference->setId((string)$data['_id']);
        }
        
        if (isset($data['userId'])) {
            $preference->setUserId((int)$data['userId']);
        }
        
        if (isset($data['standard']) && is_array($data['standard'])) {
            $preference->setStandard($data['standard']);
        }
        
        if (isset($data['custom']) && is_array($data['custom'])) {
            $preference->setCustom($data['custom']);
        }
        
        if (isset($data['lastUpdated'])) {
            $preference->setLastUpdated($data['lastUpdated']);
        }
        
        return $preference;
    }
} 