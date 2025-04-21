<?php

namespace App\Models\Entities;

/**
 * Interface pour toutes les entités
 */
interface EntityInterface
{
    /**
     * Transforme l'entité en tableau
     * 
     * @return array
     */
    public function toArray(): array;
    
    /**
     * Crée une instance d'entité à partir d'un tableau
     * 
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self;
    
    /**
     * Valide l'entité
     * 
     * @return array Erreurs de validation (vide si tout est valide)
     */
    public function validate(): array;
} 