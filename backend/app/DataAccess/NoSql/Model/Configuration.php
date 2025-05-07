<?php

namespace App\DataAccess\NoSql\Model;

/**
 * Modèle de configuration pour MongoDB
 */
class Configuration
{
    /**
     * @var string|null ID MongoDB
     */
    private $id;

    /**
     * @var string Code unique de la configuration
     */
    private $code;

    /**
     * @var string Valeur de la configuration
     */
    private $value;

    /**
     * @var string Description de la configuration
     */
    private $description;

    /**
     * @var string Catégorie de la configuration
     */
    private $category;

    /**
     * @var string Environnement (prod, dev, test, etc.)
     */
    private $environment;

    /**
     * @var bool Si la configuration est active
     */
    private $active = true;

    /**
     * @var \DateTime Date de création
     */
    private $createdAt;

    /**
     * @var \DateTime|null Date de mise à jour
     */
    private $updatedAt;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return Configuration
     */
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return Configuration
     */
    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @return Configuration
     */
    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return Configuration
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * @param string $category
     * @return Configuration
     */
    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * @param string $environment
     * @return Configuration
     */
    public function setEnvironment(string $environment): self
    {
        $this->environment = $environment;
        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     * @return Configuration
     */
    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     * @return Configuration
     */
    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     * @return Configuration
     */
    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Met à jour la date de mise à jour
     * 
     * @return Configuration
     */
    public function updateTimestamp(): self
    {
        $this->updatedAt = new \DateTime();
        return $this;
    }
} 