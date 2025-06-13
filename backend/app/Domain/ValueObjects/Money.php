<?php

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object représentant un montant monétaire
 */
final class Money
{
    private float $amount;
    private string $currency;

    public function __construct(float $amount, string $currency = 'EUR')
    {
        $this->validateAmount($amount);
        $this->validateCurrency($currency);
        
        $this->amount = round($amount, 2);
        $this->currency = strtoupper($currency);
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Additionne deux montants
     */
    public function add(Money $other): Money
    {
        $this->ensureSameCurrency($other);
        return new Money($this->amount + $other->amount, $this->currency);
    }

    /**
     * Soustrait un montant
     */
    public function subtract(Money $other): Money
    {
        $this->ensureSameCurrency($other);
        return new Money($this->amount - $other->amount, $this->currency);
    }

    /**
     * Multiplie par un facteur
     */
    public function multiply(float $factor): Money
    {
        if ($factor < 0) {
            throw new InvalidArgumentException('Le facteur de multiplication ne peut pas être négatif');
        }
        
        return new Money($this->amount * $factor, $this->currency);
    }

    /**
     * Divise par un facteur
     */
    public function divide(float $divisor): Money
    {
        if ($divisor <= 0) {
            throw new InvalidArgumentException('Le diviseur doit être positif');
        }
        
        return new Money($this->amount / $divisor, $this->currency);
    }

    /**
     * Vérifie l'égalité avec un autre montant
     */
    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    /**
     * Vérifie si ce montant est supérieur à un autre
     */
    public function isGreaterThan(Money $other): bool
    {
        $this->ensureSameCurrency($other);
        return $this->amount > $other->amount;
    }

    /**
     * Vérifie si ce montant est inférieur à un autre
     */
    public function isLessThan(Money $other): bool
    {
        $this->ensureSameCurrency($other);
        return $this->amount < $other->amount;
    }

    /**
     * Retourne une représentation formatée du montant
     */
    public function format(): string
    {
        return number_format($this->amount, 2, ',', ' ') . ' ' . $this->currency;
    }

    /**
     * Retourne une représentation sous forme de chaîne
     */
    public function __toString(): string
    {
        return $this->format();
    }

    private function validateAmount(float $amount): void
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Le montant ne peut pas être négatif');
        }
        
        if (!is_finite($amount)) {
            throw new InvalidArgumentException('Le montant doit être un nombre fini');
        }
    }

    private function validateCurrency(string $currency): void
    {
        if (empty($currency)) {
            throw new InvalidArgumentException('La devise ne peut pas être vide');
        }
        
        if (strlen($currency) !== 3) {
            throw new InvalidArgumentException('La devise doit faire 3 caractères (format ISO 4217)');
        }
    }

    private function ensureSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Les devises doivent être identiques pour cette opération');
        }
    }
} 