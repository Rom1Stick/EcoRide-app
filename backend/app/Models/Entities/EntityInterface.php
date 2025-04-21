<?php

namespace App\Models\Entities;

/**
 * Interface pour les entités du modèle
 */
interface EntityInterface
{
    /**
     * Création d'une instance depuis un tableau de données
     *
     * @param array $data Données sources
     * @return self Instance de l'entité
     */
    public static function fromArray(array $data): self;
    
    /**
     * Convertit l'entité en tableau
     *
     * @return array Représentation sous forme de tableau
     */
    public function toArray(): array;
    
    /**
     * Valide les données de l'entité
     *
     * @return array Tableau des erreurs de validation (vide si aucune erreur)
     */
    public function validate(): array;
} 