<?php

namespace App\DataAccess\Sql\Entity;

/**
 * Classe User
 * 
 * Représente un utilisateur dans le système
 */
class User
{
    /**
     * Identifiant unique de l'utilisateur
     * 
     * @var int|null
     */
    private ?int $id;

    /**
     * Email de l'utilisateur
     * 
     * @var string
     */
    private string $email;

    /**
     * Mot de passe hashé de l'utilisateur
     * 
     * @var string
     */
    private string $password;

    /**
     * Prénom de l'utilisateur
     * 
     * @var string
     */
    private string $firstName;

    /**
     * Nom de l'utilisateur
     * 
     * @var string
     */
    private string $lastName;

    /**
     * Numéro de téléphone de l'utilisateur
     * 
     * @var string|null
     */
    private ?string $phone;

    /**
     * Rôle de l'utilisateur
     * 
     * @var string
     */
    private string $role;

    /**
     * Date de création du compte
     * 
     * @var \DateTime
     */
    private \DateTime $createdAt;

    /**
     * Date de dernière mise à jour du compte
     * 
     * @var \DateTime|null
     */
    private ?\DateTime $updatedAt;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->id = null;
        $this->phone = null;
        $this->role = 'ROLE_USER';
        $this->createdAt = new \DateTime();
        $this->updatedAt = null;
    }

    /**
     * Get identifiant unique de l'utilisateur
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set identifiant unique de l'utilisateur
     *
     * @param int|null $id Identifiant unique
     * @return self
     */
    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get email de l'utilisateur
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Set email de l'utilisateur
     *
     * @param string $email Email
     * @return self
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Get mot de passe hashé de l'utilisateur
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Set mot de passe hashé de l'utilisateur
     *
     * @param string $password Mot de passe hashé
     * @return self
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Get prénom de l'utilisateur
     *
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * Set prénom de l'utilisateur
     *
     * @param string $firstName Prénom
     * @return self
     */
    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    /**
     * Get nom de l'utilisateur
     *
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * Set nom de l'utilisateur
     *
     * @param string $lastName Nom
     * @return self
     */
    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    /**
     * Get nom complet de l'utilisateur
     *
     * @return string
     */
    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    /**
     * Get numéro de téléphone de l'utilisateur
     *
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * Set numéro de téléphone de l'utilisateur
     *
     * @param string|null $phone Numéro de téléphone
     * @return self
     */
    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * Get rôle de l'utilisateur
     *
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * Set rôle de l'utilisateur
     *
     * @param string $role Rôle
     * @return self
     */
    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    /**
     * Get date de création du compte
     *
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * Set date de création du compte
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
     * Get date de dernière mise à jour du compte
     *
     * @return \DateTime|null
     */
    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Set date de dernière mise à jour du compte
     *
     * @param \DateTime|null $updatedAt Date de mise à jour
     * @return self
     */
    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Met à jour la date de mise à jour
     * 
     * @return self
     */
    public function touch(): self
    {
        $this->updatedAt = new \DateTime();
        return $this;
    }

    /**
     * Valide que l'email est au bon format
     * 
     * @return bool
     */
    public function isEmailValid(): bool
    {
        return filter_var($this->email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Valide que le numéro de téléphone est au bon format
     * 
     * @return bool
     */
    public function isPhoneValid(): bool
    {
        if ($this->phone === null) {
            return true;
        }
        
        // Validation basique d'un format de téléphone français
        return preg_match('/^(0|\+33|0033)[1-9][0-9]{8}$/', $this->phone) === 1;
    }
} 