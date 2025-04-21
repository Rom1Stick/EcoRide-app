<?php

namespace App\Models\Entities;

use DateTime;
use MongoDB\BSON\UTCDateTime;

/**
 * Classe représentant un avis utilisateur stocké dans MongoDB
 */
class Review
{
    public string $id;
    public int $user_id;
    public string $entity_type;
    public int $entity_id;
    public int $rating;
    public string $comment;
    public array $metadata;
    public bool $is_deleted;
    public UTCDateTime $created_at;
    public ?UTCDateTime $updated_at;
    
    /**
     * Constructeur
     *
     * @param int $userId ID de l'utilisateur
     * @param string $entityType Type d'entité (voiture, vélo, etc.)
     * @param int $entityId ID de l'entité
     * @param int $rating Note (1-5)
     * @param string $comment Commentaire
     * @param array $metadata Métadonnées additionnelles (optionnel)
     */
    public function __construct(
        int $userId,
        string $entityType,
        int $entityId,
        int $rating,
        string $comment,
        array $metadata = []
    ) {
        $this->id = uniqid('rev_');
        $this->user_id = $userId;
        $this->entity_type = strtolower($entityType);
        $this->entity_id = $entityId;
        $this->rating = max(1, min(5, $rating)); // S'assure que la note est entre 1 et 5
        $this->comment = $comment;
        $this->metadata = $metadata;
        $this->is_deleted = false;
        $this->created_at = new UTCDateTime(new DateTime());
        $this->updated_at = null;
    }
    
    /**
     * Convertit l'objet en tableau
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'metadata' => $this->metadata,
            'is_deleted' => $this->is_deleted,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
    
    /**
     * Crée une instance à partir d'un tableau
     *
     * @param array $data Données
     * @return self Instance
     */
    public static function fromArray(array $data): self
    {
        $review = new self(
            $data['user_id'],
            $data['entity_type'],
            $data['entity_id'],
            $data['rating'],
            $data['comment'],
            $data['metadata'] ?? []
        );
        
        $review->id = $data['id'];
        $review->is_deleted = $data['is_deleted'] ?? false;
        
        if (isset($data['created_at'])) {
            $review->created_at = $data['created_at'] instanceof UTCDateTime
                ? $data['created_at']
                : new UTCDateTime(new DateTime($data['created_at']));
        }
        
        if (isset($data['updated_at'])) {
            $review->updated_at = $data['updated_at'] instanceof UTCDateTime
                ? $data['updated_at']
                : new UTCDateTime(new DateTime($data['updated_at']));
        }
        
        return $review;
    }
    
    /**
     * Met à jour l'avis
     *
     * @param int $rating Nouvelle note
     * @param string $comment Nouveau commentaire
     * @param array|null $metadata Nouvelles métadonnées (null pour ne pas modifier)
     * @return self
     */
    public function update(int $rating, string $comment, ?array $metadata = null): self
    {
        $this->rating = max(1, min(5, $rating));
        $this->comment = $comment;
        
        if ($metadata !== null) {
            $this->metadata = $metadata;
        }
        
        $this->updated_at = new UTCDateTime(new DateTime());
        
        return $this;
    }
    
    /**
     * Marque l'avis comme supprimé
     *
     * @return self
     */
    public function markAsDeleted(): self
    {
        $this->is_deleted = true;
        $this->updated_at = new UTCDateTime(new DateTime());
        
        return $this;
    }
    
    /**
     * Vérifie si l'avis est supprimé
     *
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->is_deleted;
    }
    
    /**
     * Ajoute des métadonnées à l'avis
     *
     * @param string $key Clé
     * @param mixed $value Valeur
     * @return self
     */
    public function addMetadata(string $key, $value): self
    {
        $this->metadata[$key] = $value;
        $this->updated_at = new UTCDateTime(new DateTime());
        
        return $this;
    }
    
    /**
     * Récupère une métadonnée spécifique
     *
     * @param string $key Clé
     * @param mixed $default Valeur par défaut
     * @return mixed
     */
    public function getMetadata(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }
    
    /**
     * Récupère toutes les métadonnées
     *
     * @return array
     */
    public function getAllMetadata(): array
    {
        return $this->metadata;
    }
    
    /**
     * Convertit la date de création en objet DateTime
     *
     * @return DateTime
     */
    public function getCreatedAtDateTime(): DateTime
    {
        return $this->created_at->toDateTime();
    }
    
    /**
     * Convertit la date de mise à jour en objet DateTime
     *
     * @return DateTime|null
     */
    public function getUpdatedAtDateTime(): ?DateTime
    {
        return $this->updated_at ? $this->updated_at->toDateTime() : null;
    }
} 