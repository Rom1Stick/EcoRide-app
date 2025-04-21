<?php

namespace App\Models\Entities;

/**
 * Classe représentant un covoiturage
 */
class Trip implements EntityInterface
{
    public ?int $covoiturage_id = null;
    public ?int $voiture_id = null;
    public ?int $lieu_depart_id = null;
    public ?int $lieu_arrivee_id = null;
    public ?string $date_depart = null;
    public ?string $heure_depart = null;
    public ?string $date_arrivee = null;
    public ?string $heure_arrivee = null;
    public ?int $nb_place = null;
    public ?float $prix_personne = null;
    public ?int $statut_id = null;
    public ?string $date_creation = null;
    public ?float $empreinte_carbone = null;
    
    /**
     * Constructeur
     */
    public function __construct(
        ?int $covoiturage_id = null,
        ?int $voiture_id = null,
        ?int $lieu_depart_id = null,
        ?int $lieu_arrivee_id = null,
        ?string $date_depart = null,
        ?string $heure_depart = null,
        ?string $date_arrivee = null,
        ?string $heure_arrivee = null,
        ?int $nb_place = null,
        ?float $prix_personne = null,
        ?int $statut_id = null,
        ?string $date_creation = null,
        ?float $empreinte_carbone = null
    ) {
        $this->covoiturage_id = $covoiturage_id;
        $this->voiture_id = $voiture_id;
        $this->lieu_depart_id = $lieu_depart_id;
        $this->lieu_arrivee_id = $lieu_arrivee_id;
        $this->date_depart = $date_depart;
        $this->heure_depart = $heure_depart;
        $this->date_arrivee = $date_arrivee;
        $this->heure_arrivee = $heure_arrivee;
        $this->nb_place = $nb_place;
        $this->prix_personne = $prix_personne;
        $this->statut_id = $statut_id;
        $this->date_creation = $date_creation ?? date('Y-m-d H:i:s');
        $this->empreinte_carbone = $empreinte_carbone;
    }
    
    /**
     * Obtient l'ID du covoiturage
     *
     * @return int|null ID du covoiturage
     */
    public function getId(): ?int
    {
        return $this->covoiturage_id;
    }
    
    /**
     * Définit l'ID du covoiturage
     *
     * @param int $id Nouvel ID
     * @return self
     */
    public function setId(int $id): self
    {
        $this->covoiturage_id = $id;
        return $this;
    }
    
    /**
     * Obtient le nombre de places disponibles
     *
     * @return int Nombre de places disponibles
     */
    public function getAvailableSeats(): int
    {
        return $this->nb_place;
    }
    
    /**
     * Définit l'empreinte carbone du covoiturage
     *
     * @param float $value Empreinte carbone en kg CO2
     * @return self
     */
    public function setCarbonFootprint(float $value): self
    {
        $this->empreinte_carbone = $value;
        return $this;
    }
    
    /**
     * Création d'une instance depuis un tableau de données
     */
    public static function fromArray(array $data): self
    {
        $trip = new self();
        $trip->covoiturage_id = $data['covoiturage_id'] ?? null;
        $trip->voiture_id = $data['voiture_id'] ?? null;
        $trip->lieu_depart_id = $data['lieu_depart_id'] ?? null;
        $trip->lieu_arrivee_id = $data['lieu_arrivee_id'] ?? null;
        $trip->date_depart = $data['date_depart'] ?? null;
        $trip->heure_depart = $data['heure_depart'] ?? null;
        $trip->date_arrivee = $data['date_arrivee'] ?? null;
        $trip->heure_arrivee = $data['heure_arrivee'] ?? null;
        $trip->nb_place = $data['nb_place'] ?? null;
        $trip->prix_personne = $data['prix_personne'] ?? null;
        $trip->statut_id = $data['statut_id'] ?? null;
        $trip->date_creation = $data['date_creation'] ?? null;
        $trip->empreinte_carbone = $data['empreinte_carbone'] ?? null;

        return $trip;
    }
    
    /**
     * Convertit l'entité en tableau
     */
    public function toArray(): array
    {
        return [
            'covoiturage_id' => $this->covoiturage_id,
            'voiture_id' => $this->voiture_id,
            'lieu_depart_id' => $this->lieu_depart_id,
            'lieu_arrivee_id' => $this->lieu_arrivee_id,
            'date_depart' => $this->date_depart,
            'heure_depart' => $this->heure_depart,
            'date_arrivee' => $this->date_arrivee,
            'heure_arrivee' => $this->heure_arrivee,
            'nb_place' => $this->nb_place,
            'prix_personne' => $this->prix_personne,
            'statut_id' => $this->statut_id,
            'date_creation' => $this->date_creation,
            'empreinte_carbone' => $this->empreinte_carbone
        ];
    }
    
    /**
     * Valide les données de l'entité
     */
    public function validate(): array
    {
        $errors = [];
        
        if (empty($this->voiture_id)) {
            $errors['voiture_id'] = 'Le véhicule est obligatoire';
        }
        
        if (empty($this->lieu_depart_id)) {
            $errors['lieu_depart_id'] = 'Le lieu de départ est obligatoire';
        }
        
        if (empty($this->lieu_arrivee_id)) {
            $errors['lieu_arrivee_id'] = 'Le lieu d\'arrivée est obligatoire';
        }
        
        if ($this->lieu_depart_id === $this->lieu_arrivee_id) {
            $errors['lieu_arrivee_id'] = 'Le lieu d\'arrivée doit être différent du lieu de départ';
        }
        
        if (empty($this->date_depart)) {
            $errors['date_depart'] = 'La date de départ est obligatoire';
        } else {
            // Valider le format de la date
            $date = \DateTime::createFromFormat('Y-m-d', $this->date_depart);
            if (!$date || $date->format('Y-m-d') !== $this->date_depart) {
                $errors['date_depart'] = 'Format de date invalide (YYYY-MM-DD)';
            }
        }
        
        if (empty($this->heure_depart)) {
            $errors['heure_depart'] = 'L\'heure de départ est obligatoire';
        } else {
            // Valider le format de l'heure
            $time = \DateTime::createFromFormat('H:i:s', $this->heure_depart);
            if (!$time || $time->format('H:i:s') !== $this->heure_depart) {
                $errors['heure_depart'] = 'Format d\'heure invalide (HH:MM:SS)';
            }
        }
        
        if (empty($this->nb_place)) {
            $errors['nb_place'] = 'Le nombre de places est obligatoire';
        } elseif ($this->nb_place <= 0) {
            $errors['nb_place'] = 'Le nombre de places doit être supérieur à 0';
        } elseif ($this->nb_place > 8) {
            $errors['nb_place'] = 'Le nombre de places ne peut pas dépasser 8';
        }
        
        if (empty($this->prix_personne)) {
            $errors['prix_personne'] = 'Le prix est obligatoire';
        } elseif ($this->prix_personne < 0) {
            $errors['prix_personne'] = 'Le prix ne peut pas être négatif';
        }
        
        if (empty($this->statut_id)) {
            $errors['statut_id'] = 'Le statut est obligatoire';
        }
        
        return $errors;
    }
} 