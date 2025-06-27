<?php

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object représentant une adresse email
 */
final class Email
{
    private string $value;

    public function __construct(string $email)
    {
        $this->validate($email);
        $this->value = strtolower(trim($email));
    }

    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Retourne le domaine de l'email
     */
    public function getDomain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }

    /**
     * Retourne la partie locale de l'email (avant @)
     */
    public function getLocalPart(): string
    {
        return substr($this->value, 0, strpos($this->value, '@'));
    }

    /**
     * Vérifie l'égalité avec un autre email
     */
    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Vérifie si l'email appartient à un domaine spécifique
     */
    public function belongsToDomain(string $domain): bool
    {
        return $this->getDomain() === strtolower($domain);
    }

    /**
     * Retourne une version masquée de l'email pour l'affichage public
     */
    public function getMasked(): string
    {
        $localPart = $this->getLocalPart();
        $domain = $this->getDomain();
        
        if (strlen($localPart) <= 2) {
            $maskedLocal = str_repeat('*', strlen($localPart));
        } else {
            $maskedLocal = $localPart[0] . str_repeat('*', strlen($localPart) - 2) . substr($localPart, -1);
        }
        
        return $maskedLocal . '@' . $domain;
    }

    /**
     * Retourne une représentation sous forme de chaîne
     */
    public function __toString(): string
    {
        return $this->value;
    }

    private function validate(string $email): void
    {
        $email = trim($email);
        
        if (empty($email)) {
            throw new InvalidArgumentException('L\'adresse email ne peut pas être vide');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('L\'adresse email n\'est pas valide');
        }
        
        if (strlen($email) > 254) {
            throw new InvalidArgumentException('L\'adresse email ne peut pas dépasser 254 caractères');
        }
        
        // Vérification supplémentaire pour les domaines suspects
        $domain = substr($email, strpos($email, '@') + 1);
        if (strpos($domain, '.') === false) {
            throw new InvalidArgumentException('Le domaine de l\'email doit contenir au moins un point');
        }
    }
} 