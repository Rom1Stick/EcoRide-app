<?php

namespace App\Core\Traits;

use DateTime;

/**
 * Trait pour gérer les timestamps automatiques
 * Élimine la duplication de la méthode updateTimestamp()
 */
trait TimestampableTrait
{
    /**
     * Met à jour la date de modification
     */
    public function updateTimestamp(): void
    {
        $this->updatedAt = new DateTime();
    }

    /**
     * Met à jour automatiquement les timestamps lors de la création
     */
    public function touch(): self
    {
        $now = new DateTime();
        
        if (property_exists($this, 'createdAt') && $this->createdAt === null) {
            $this->createdAt = $now;
        }
        
        if (property_exists($this, 'updatedAt')) {
            $this->updatedAt = $now;
        }
        
        return $this;
    }
} 