<?php

namespace App\Models\Entities;

/**
 * Entité représentant un véhicule
 */
class Vehicle implements EntityInterface
{
    public ?int $voiture_id = null;
    public ?int $modele_id = null;
    public ?string $immatriculation = null;
    public ?int $energie_id = null;
    public ?string $couleur = null;
    public ?string $date_premiere_immat = null;
    public ?int $utilisateur_id = null;
    
    /**
     * Constructeur
     */
    public function __construct(
        ?int $voiture_id = null,
        ?int $modele_id = null,
        ?string $immatriculation = null,
        ?int $energie_id = null,
        ?string $couleur = null,
        ?string $date_premiere_immat = null,
        ?int $utilisateur_id = null
    ) {
        $this->voiture_id = $voiture_id;
        $this->modele_id = $modele_id;
        $this->immatriculation = $immatriculation;
        $this->energie_id = $energie_id;
        $this->couleur = $couleur;
        $this->date_premiere_immat = $date_premiere_immat;
        $this->utilisateur_id = $utilisateur_id;
    }
    
    /**
     * Création d'une instance depuis un tableau de données
     */
    public static function fromArray(array $data): self
    {
        $vehicle = new self();
        $vehicle->voiture_id = $data['voiture_id'] ?? null;
        $vehicle->modele_id = $data['modele_id'] ?? null;
        $vehicle->immatriculation = $data['immatriculation'] ?? null;
        $vehicle->energie_id = $data['energie_id'] ?? null;
        $vehicle->couleur = $data['couleur'] ?? null;
        $vehicle->date_premiere_immat = $data['date_premiere_immat'] ?? null;
        $vehicle->utilisateur_id = $data['utilisateur_id'] ?? null;

        return $vehicle;
    }
    
    /**
     * Convertit l'entité en tableau
     */
    public function toArray(): array
    {
        return [
            'voiture_id' => $this->voiture_id,
            'modele_id' => $this->modele_id,
            'immatriculation' => $this->immatriculation,
            'energie_id' => $this->energie_id,
            'couleur' => $this->couleur,
            'date_premiere_immat' => $this->date_premiere_immat,
            'utilisateur_id' => $this->utilisateur_id
        ];
    }
    
    /**
     * Valide les données de l'entité
     */
    public function validate(): array
    {
        $errors = [];
        
        if (empty($this->modele_id)) {
            $errors['modele_id'] = 'Le modèle est obligatoire';
        }
        
        if (empty($this->immatriculation)) {
            $errors['immatriculation'] = 'L\'immatriculation est obligatoire';
        } elseif (!preg_match('/^[A-Z]{2}-[0-9]{3}-[A-Z]{2}$/', $this->immatriculation)) {
            $errors['immatriculation'] = 'Format d\'immatriculation invalide (XX-999-XX)';
        }
        
        if (empty($this->energie_id)) {
            $errors['energie_id'] = 'Le type d\'énergie est obligatoire';
        }
        
        if (empty($this->utilisateur_id)) {
            $errors['utilisateur_id'] = 'L\'utilisateur est obligatoire';
        }
        
        if (empty($this->couleur)) {
            $errors['couleur'] = 'La couleur est obligatoire';
        }
        
        if (!empty($this->date_premiere_immat)) {
            // Valider le format de date
            $date = \DateTime::createFromFormat('Y-m-d', $this->date_premiere_immat);
            if (!$date || $date->format('Y-m-d') !== $this->date_premiere_immat) {
                $errors['date_premiere_immat'] = 'Format de date invalide (YYYY-MM-DD)';
            }
        }
        
        return $errors;
    }
} 