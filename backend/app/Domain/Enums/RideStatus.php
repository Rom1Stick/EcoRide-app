<?php

namespace App\Domain\Enums;

/**
 * Enum représentant les différents statuts d'un trajet
 */
enum RideStatus: string
{
    case PLANNED = 'planned';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    /**
     * Retourne le libellé français du statut
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PLANNED => 'Planifié',
            self::IN_PROGRESS => 'En cours',
            self::COMPLETED => 'Terminé',
            self::CANCELLED => 'Annulé'
        };
    }

    /**
     * Retourne la couleur associée au statut (pour l'interface)
     */
    public function getColor(): string
    {
        return match($this) {
            self::PLANNED => 'blue',
            self::IN_PROGRESS => 'green',
            self::COMPLETED => 'gray',
            self::CANCELLED => 'red'
        };
    }

    /**
     * Vérifie si le trajet peut être modifié
     */
    public function isModifiable(): bool
    {
        return $this === self::PLANNED;
    }

    /**
     * Vérifie si le trajet peut être annulé
     */
    public function isCancellable(): bool
    {
        return in_array($this, [self::PLANNED, self::IN_PROGRESS]);
    }

    /**
     * Vérifie si le trajet accepte les réservations
     */
    public function acceptsBookings(): bool
    {
        return $this === self::PLANNED;
    }

    /**
     * Retourne tous les statuts possibles
     */
    public static function getAllStatuses(): array
    {
        return array_map(fn($status) => [
            'value' => $status->value,
            'label' => $status->getLabel(),
            'color' => $status->getColor()
        ], self::cases());
    }

    /**
     * Créé un statut à partir d'une chaîne de caractères
     */
    public static function fromString(string $status): self
    {
        return self::tryFrom($status) ?? throw new \InvalidArgumentException("Statut invalide: $status");
    }
} 