<?php

namespace App\Models\Documents;

/**
 * Classe représentant un document d'avis dans MongoDB
 */
class ReviewDocument
{
    private ?string $id = null;
    private int $userId;
    private int $tripId;
    private string $comment;
    private int $rating;
    private string $status;
    private array $metadata;
    private string $createdAt;
    private ?string $updatedAt = null;
    
    /**
     * Constructeur
     *
     * @param int $userId Identifiant de l'utilisateur
     * @param int $tripId Identifiant du trajet
     * @param string $comment Commentaire de l'avis
     * @param int $rating Note (de 1 à 5)
     * @param string $status Statut de l'avis (validé, en attente, rejeté)
     */
    public function __construct(int $userId, int $tripId, string $comment, int $rating, string $status = 'pending')
    {
        $this->userId = $userId;
        $this->tripId = $tripId;
        $this->comment = $comment;
        $this->rating = $rating;
        $this->status = $status;
        $this->metadata = [];
        $this->createdAt = date('Y-m-d\TH:i:s.v\Z');
    }
    
    /**
     * Valide les données de l'avis avant persistance
     *
     * @return array Tableau d'erreurs de validation (vide si aucune erreur)
     */
    public function validate(): array
    {
        $errors = [];
        
        if ($this->userId <= 0) {
            $errors['userId'] = 'L\'identifiant utilisateur est invalide';
        }
        
        if ($this->tripId <= 0) {
            $errors['tripId'] = 'L\'identifiant du trajet est invalide';
        }
        
        if (empty($this->comment)) {
            $errors['comment'] = 'Le commentaire ne peut pas être vide';
        }
        
        if ($this->rating < 1 || $this->rating > 5) {
            $errors['rating'] = 'La note doit être comprise entre 1 et 5';
        }
        
        $validStatuses = ['pending', 'approved', 'rejected'];
        if (!in_array($this->status, $validStatuses)) {
            $errors['status'] = 'Le statut doit être l\'un des suivants : ' . implode(', ', $validStatuses);
        }
        
        return $errors;
    }
    
    // Getters & Setters
    
    public function getId(): ?string
    {
        return $this->id;
    }
    
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }
    
    public function getUserId(): int
    {
        return $this->userId;
    }
    
    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }
    
    public function getTripId(): int
    {
        return $this->tripId;
    }
    
    public function setTripId(int $tripId): self
    {
        $this->tripId = $tripId;
        return $this;
    }
    
    public function getComment(): string
    {
        return $this->comment;
    }
    
    public function setComment(string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }
    
    public function getRating(): int
    {
        return $this->rating;
    }
    
    public function setRating(int $rating): self
    {
        $this->rating = $rating;
        return $this;
    }
    
    public function getStatus(): string
    {
        return $this->status;
    }
    
    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }
    
    public function getMetadata(): array
    {
        return $this->metadata;
    }
    
    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }
    
    public function addMetadata(string $key, $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }
    
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }
    
    public function setCreatedAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
    
    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }
    
    public function setUpdatedAt(?string $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
    
    /**
     * Crée une instance d'avis à partir d'un tableau de données (document MongoDB)
     *
     * @param array $data Données de l'avis
     * @return ReviewDocument
     */
    public static function fromArray(array $data): self
    {
        $review = new self(
            $data['userId'] ?? 0,
            $data['tripId'] ?? 0,
            $data['comment'] ?? '',
            $data['rating'] ?? 1,
            $data['status'] ?? 'pending'
        );
        
        if (isset($data['_id'])) {
            $review->setId((string)$data['_id']);
        }
        
        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $review->setMetadata($data['metadata']);
        }
        
        if (isset($data['createdAt'])) {
            $review->setCreatedAt($data['createdAt']);
        }
        
        if (isset($data['updatedAt'])) {
            $review->setUpdatedAt($data['updatedAt']);
        }
        
        return $review;
    }
    
    /**
     * Convertit l'avis en tableau pour la persistance MongoDB
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = [
            'userId' => $this->userId,
            'tripId' => $this->tripId,
            'comment' => $this->comment,
            'rating' => $this->rating,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'createdAt' => $this->createdAt
        ];
        
        if ($this->updatedAt !== null) {
            $data['updatedAt'] = $this->updatedAt;
        }
        
        return $data;
    }
} 