<?php

namespace App\DataAccess\NoSql\Model;

use DateTime;
use JsonSerializable;

/**
 * Modèle pour les configurations système dans MongoDB
 */
class Configuration implements JsonSerializable
{
    /**
     * Identifiant MongoDB
     *
     * @var string|null
     */
    private ?string $id = null;
    
    /**
     * Code unique de la configuration
     *
     * @var string
     */
    private string $code = '';
    
    /**
     * Valeur de la configuration (peut être un scalaire ou un tableau)
     *
     * @var mixed
     */
    private $value;
    
    /**
     * Description de la configuration
     *
     * @var string
     */
    private string $description = '';
    
    /**
     * État d'activation de la configuration
     *
     * @var bool
     */
    private bool $active = true;
    
    /**
     * Catégorie de la configuration
     *
     * @var string
     */
    private string $category = 'general';
    
    /**
     * Environnement (prod, dev, test, etc.)
     *
     * @var string
     */
    private string $environment = 'prod';
    
    /**
     * Utilisateur ayant modifié la configuration
     *
     * @var string
     */
    private string $modifiedBy = 'system';
    
    /**
     * Date de création
     *
     * @var string|null
     */
    private ?string $createdAt = null;
    
    /**
     * Date de dernière modification
     *
     * @var string|null
     */
    private ?string $updatedAt = null;
    
    /**
     * Constructeur
     *
     * @param string $code Code unique de la configuration
     * @param mixed $value Valeur de la configuration
     * @param string $description Description de la configuration
     */
    public function __construct(string $code = '', $value = null, string $description = '')
    {
        $this->code = $code;
        $this->value = $value;
        $this->description = $description;
        $now = (new DateTime())->format('Y-m-d H:i:s');
        $this->createdAt = $now;
        $this->updatedAt = $now;
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
     * Obtenir le code
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }
    
    /**
     * Définir le code
     *
     * @param string $code
     * @return self
     */
    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }
    
    /**
     * Obtenir la valeur
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
    
    /**
     * Définir la valeur
     *
     * @param mixed $value
     * @return self
     */
    public function setValue($value): self
    {
        $this->value = $value;
        return $this;
    }
    
    /**
     * Obtenir la description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }
    
    /**
     * Définir la description
     *
     * @param string $description
     * @return self
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }
    
    /**
     * Vérifier si la configuration est active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }
    
    /**
     * Définir l'état d'activation
     *
     * @param bool $active
     * @return self
     */
    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }
    
    /**
     * Obtenir la catégorie
     *
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }
    
    /**
     * Définir la catégorie
     *
     * @param string $category
     * @return self
     */
    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }
    
    /**
     * Obtenir l'environnement
     *
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }
    
    /**
     * Définir l'environnement
     *
     * @param string $environment
     * @return self
     */
    public function setEnvironment(string $environment): self
    {
        $this->environment = $environment;
        return $this;
    }
    
    /**
     * Obtenir l'utilisateur ayant modifié la configuration
     *
     * @return string
     */
    public function getModifiedBy(): string
    {
        return $this->modifiedBy;
    }
    
    /**
     * Définir l'utilisateur ayant modifié la configuration
     *
     * @param string $modifiedBy
     * @return self
     */
    public function setModifiedBy(string $modifiedBy): self
    {
        $this->modifiedBy = $modifiedBy;
        return $this;
    }
    
    /**
     * Obtenir la date de création
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }
    
    /**
     * Définir la date de création
     *
     * @param string|null $createdAt
     * @return self
     */
    public function setCreatedAt(?string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
    
    /**
     * Obtenir la date de dernière modification
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }
    
    /**
     * Définir la date de dernière modification
     *
     * @param string|null $updatedAt
     * @return self
     */
    public function setUpdatedAt(?string $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
    
    /**
     * Mettre à jour l'horodatage
     *
     * @param string $modifiedBy Utilisateur effectuant la modification
     * @return self
     */
    public function touch(string $modifiedBy = 'system'): self
    {
        $this->updatedAt = (new DateTime())->format('Y-m-d H:i:s');
        $this->modifiedBy = $modifiedBy;
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
            'code' => $this->code,
            'value' => $this->value,
            'description' => $this->description,
            'active' => $this->active,
            'category' => $this->category,
            'environment' => $this->environment,
            'modified_by' => $this->modifiedBy,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
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
        $config = new self();
        
        if (isset($data['_id'])) {
            $config->setId((string)$data['_id']);
        }
        
        if (isset($data['code'])) {
            $config->setCode($data['code']);
        }
        
        if (isset($data['value'])) {
            $config->setValue($data['value']);
        }
        
        if (isset($data['description'])) {
            $config->setDescription($data['description']);
        }
        
        if (isset($data['active'])) {
            $config->setActive((bool)$data['active']);
        }
        
        if (isset($data['category'])) {
            $config->setCategory($data['category']);
        }
        
        if (isset($data['environment'])) {
            $config->setEnvironment($data['environment']);
        }
        
        if (isset($data['modified_by'])) {
            $config->setModifiedBy($data['modified_by']);
        }
        
        if (isset($data['created_at'])) {
            $config->setCreatedAt($data['created_at']);
        }
        
        if (isset($data['updated_at'])) {
            $config->setUpdatedAt($data['updated_at']);
        }
        
        return $config;
    }
} 