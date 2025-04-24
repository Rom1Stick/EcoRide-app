<?php

namespace App\DataAccess\Sql\Entity;

/**
 * Classe CreditBalance
 * 
 * Représente le solde de crédits d'un utilisateur dans le système
 */
class CreditBalance
{
    /**
     * Identifiant unique du solde
     * 
     * @var int|null
     */
    private ?int $id = null;

    /**
     * Identifiant de l'utilisateur
     * 
     * @var int
     */
    private int $userId;

    /**
     * Solde actuel en crédits
     * 
     * @var float
     */
    private float $balance = 0.0;

    /**
     * Total des crédits gagnés
     * 
     * @var float
     */
    private float $totalEarned = 0.0;

    /**
     * Total des crédits dépensés
     * 
     * @var float
     */
    private float $totalSpent = 0.0;

    /**
     * Total des crédits bonus
     * 
     * @var float
     */
    private float $bonusCredits = 0.0;

    /**
     * État du compte (actif, bloqué, etc.)
     * 
     * @var string
     */
    private string $status = 'active';

    /**
     * Code de devise (EUR, USD, etc.)
     * 
     * @var string
     */
    private string $currencyCode = 'EUR';

    /**
     * Date de la dernière transaction
     * 
     * @var \DateTime|null
     */
    private ?\DateTime $lastTransactionDate = null;

    /**
     * Date de création de l'enregistrement
     * 
     * @var \DateTime
     */
    private \DateTime $createdAt;

    /**
     * Date de dernière mise à jour
     * 
     * @var \DateTime|null
     */
    private ?\DateTime $updatedAt = null;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * Récupère l'identifiant
     * 
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Définit l'identifiant
     * 
     * @param int|null $id Identifiant
     * @return self
     */
    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Récupère l'identifiant de l'utilisateur
     * 
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * Définit l'identifiant de l'utilisateur
     * 
     * @param int $userId Identifiant de l'utilisateur
     * @return self
     */
    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Récupère le solde actuel
     * 
     * @return float
     */
    public function getBalance(): float
    {
        return $this->balance;
    }

    /**
     * Définit le solde actuel
     * 
     * @param float $balance Solde
     * @return self
     */
    public function setBalance(float $balance): self
    {
        $this->balance = $balance;
        return $this;
    }

    /**
     * Récupère le total des crédits gagnés
     * 
     * @return float
     */
    public function getTotalEarned(): float
    {
        return $this->totalEarned;
    }

    /**
     * Définit le total des crédits gagnés
     * 
     * @param float $totalEarned Total des crédits gagnés
     * @return self
     */
    public function setTotalEarned(float $totalEarned): self
    {
        $this->totalEarned = $totalEarned;
        return $this;
    }

    /**
     * Récupère le total des crédits dépensés
     * 
     * @return float
     */
    public function getTotalSpent(): float
    {
        return $this->totalSpent;
    }

    /**
     * Définit le total des crédits dépensés
     * 
     * @param float $totalSpent Total des crédits dépensés
     * @return self
     */
    public function setTotalSpent(float $totalSpent): self
    {
        $this->totalSpent = $totalSpent;
        return $this;
    }

    /**
     * Récupère le total des crédits bonus
     * 
     * @return float
     */
    public function getBonusCredits(): float
    {
        return $this->bonusCredits;
    }

    /**
     * Définit le total des crédits bonus
     * 
     * @param float $bonusCredits Total des crédits bonus
     * @return self
     */
    public function setBonusCredits(float $bonusCredits): self
    {
        $this->bonusCredits = $bonusCredits;
        return $this;
    }

    /**
     * Récupère l'état du compte
     * 
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Définit l'état du compte
     * 
     * @param string $status État du compte
     * @return self
     */
    public function setStatus(string $status): self
    {
        $allowedStatuses = ['active', 'blocked', 'suspended', 'closed'];
        
        if (!in_array($status, $allowedStatuses)) {
            throw new \InvalidArgumentException("Statut non valide: $status");
        }
        
        $this->status = $status;
        return $this;
    }

    /**
     * Récupère le code de devise
     * 
     * @return string
     */
    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    /**
     * Définit le code de devise
     * 
     * @param string $currencyCode Code de devise
     * @return self
     */
    public function setCurrencyCode(string $currencyCode): self
    {
        $this->currencyCode = $currencyCode;
        return $this;
    }

    /**
     * Récupère la date de la dernière transaction
     * 
     * @return \DateTime|null
     */
    public function getLastTransactionDate(): ?\DateTime
    {
        return $this->lastTransactionDate;
    }

    /**
     * Définit la date de la dernière transaction
     * 
     * @param \DateTime|null $lastTransactionDate Date de la dernière transaction
     * @return self
     */
    public function setLastTransactionDate(?\DateTime $lastTransactionDate): self
    {
        $this->lastTransactionDate = $lastTransactionDate;
        return $this;
    }

    /**
     * Récupère la date de création
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
     * Récupère la date de mise à jour
     * 
     * @return \DateTime|null
     */
    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Définit la date de mise à jour
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
     * Met à jour la date de dernière modification
     * 
     * @return self
     */
    public function touch(): self
    {
        $this->updatedAt = new \DateTime();
        return $this;
    }

    /**
     * Ajoute des crédits au solde
     * 
     * @param float $amount Montant à ajouter
     * @param bool $isBonus Indique si ce sont des crédits bonus
     * @return self
     * @throws \InvalidArgumentException Si le montant est négatif
     */
    public function credit(float $amount, bool $isBonus = false): self
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException("Le montant à créditer doit être positif");
        }
        
        $this->balance += $amount;
        $this->totalEarned += $amount;
        
        if ($isBonus) {
            $this->bonusCredits += $amount;
        }
        
        $this->lastTransactionDate = new \DateTime();
        $this->touch();
        
        return $this;
    }

    /**
     * Retire des crédits du solde
     * 
     * @param float $amount Montant à retirer
     * @return self
     * @throws \InvalidArgumentException Si le montant est négatif
     * @throws \LogicException Si le solde est insuffisant
     */
    public function debit(float $amount): self
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException("Le montant à débiter doit être positif");
        }
        
        if ($this->balance < $amount) {
            throw new \LogicException("Solde insuffisant");
        }
        
        $this->balance -= $amount;
        $this->totalSpent += $amount;
        $this->lastTransactionDate = new \DateTime();
        $this->touch();
        
        return $this;
    }

    /**
     * Vérifie si le solde est suffisant pour un montant donné
     * 
     * @param float $amount Montant à vérifier
     * @return bool
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Bloque le compte
     * 
     * @return self
     */
    public function block(): self
    {
        $this->status = 'blocked';
        $this->touch();
        
        return $this;
    }

    /**
     * Active le compte
     * 
     * @return self
     */
    public function activate(): self
    {
        $this->status = 'active';
        $this->touch();
        
        return $this;
    }

    /**
     * Vérifie si le compte est actif
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
} 