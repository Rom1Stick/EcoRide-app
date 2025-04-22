<?php

namespace App\DataAccess\NoSql\Model;

use DateTime;
use JsonSerializable;

/**
 * Modèle pour les avis utilisateurs dans MongoDB
 */
class Review implements JsonSerializable
{
    /**
     * Identifiant MongoDB
     *
     * @var string|null
     */
    private ?string $id = null;
    
    /**
     * ID de l'utilisateur qui a laissé l'avis
     *
     * @var int
     */
    private int $userId = 0;
    
    /**
     * ID du covoiturage concerné
     *
     * @var int
     */
    private int $covoiturageId = 0;
    
    /**
     * ID de l'utilisateur évalué (chauffeur ou passager)
     *
     * @var int
     */
    private int $targetUserId = 0;
    
    /**
     * Note (1-5)
     *
     * @var int
     */
    private int $rating = 0;
    
    /**
     * Commentaire
     *
     * @var string
     */
    private string $comment = '';
    
    /**
     * Type d'avis (chauffeur ou passager)
     *
     * @var string
     */
    private string $type = 'chauffeur';
    
    /**
     * Critères d'évaluation spécifiques
     *
     * @var array
     */
    private array $criteria = [];
    
    /**
     * État de l'avis (en attente, approuvé, signalé)
     *
     * @var string
     */
    private string $status = 'en_attente';
    
    /**
     * Date de création
     *
     * @var string
     */
    private string $createdAt;
    
    /**
     * Date de mise à jour
     *
     * @var string
     */
    private string $updatedAt;
    
    /**
     * Constructeur
     *
     * @param int $userId ID de l'utilisateur qui a laissé l'avis
     * @param int $covoiturageId ID du covoiturage concerné
     * @param int $targetUserId ID de l'utilisateur évalué
     * @param int $rating Note (1-5)
     * @param string $comment Commentaire
     * @param string $type Type d'avis (chauffeur ou passager)
     */
    public function __construct(int $userId = 0, int $covoiturageId = 0, int $targetUserId = 0, int $rating = 0, string $comment = '', string $type = 'chauffeur')
    {
        $this->userId = $userId;
        $this->covoiturageId = $covoiturageId;
        $this->targetUserId = $targetUserId;
        $this->rating = max(1, min(5, $rating)); // Limiter entre 1 et 5
        $this->comment = $comment;
        $this->type = $type;
        $now = (new DateTime())->format('Y-m-d H:i:s');
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }
    
    /**
     * Obtenir l'ID
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }
    
    /**
     * Définir l'ID
     *
     * @param string $id
     * @return self
     */
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }
    
    /**
     * Obtenir l'ID de l'utilisateur qui a laissé l'avis
     *
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }
    
    /**
     * Définir l'ID de l'utilisateur qui a laissé l'avis
     *
     * @param int $userId
     * @return self
     */
    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }
    
    /**
     * Obtenir l'ID du covoiturage concerné
     *
     * @return int
     */
    public function getCovoiturageId(): int
    {
        return $this->covoiturageId;
    }
    
    /**
     * Définir l'ID du covoiturage concerné
     *
     * @param int $covoiturageId
     * @return self
     */
    public function setCovoiturageId(int $covoiturageId): self
    {
        $this->covoiturageId = $covoiturageId;
        return $this;
    }
    
    /**
     * Obtenir l'ID de l'utilisateur évalué
     *
     * @return int
     */
    public function getTargetUserId(): int
    {
        return $this->targetUserId;
    }
    
    /**
     * Définir l'ID de l'utilisateur évalué
     *
     * @param int $targetUserId
     * @return self
     */
    public function setTargetUserId(int $targetUserId): self
    {
        $this->targetUserId = $targetUserId;
        return $this;
    }
    
    /**
     * Obtenir la note
     *
     * @return int
     */
    public function getRating(): int
    {
        return $this->rating;
    }
    
    /**
     * Définir la note
     *
     * @param int $rating
     * @return self
     */
    public function setRating(int $rating): self
    {
        $this->rating = max(1, min(5, $rating)); // Limiter entre 1 et 5
        return $this;
    }
    
    /**
     * Obtenir le commentaire
     *
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }
    
    /**
     * Définir le commentaire
     *
     * @param string $comment
     * @return self
     */
    public function setComment(string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }
    
    /**
     * Obtenir le type d'avis
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
    
    /**
     * Définir le type d'avis
     *
     * @param string $type
     * @return self
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }
    
    /**
     * Obtenir les critères d'évaluation
     *
     * @return array
     */
    public function getCriteria(): array
    {
        return $this->criteria;
    }
    
    /**
     * Définir les critères d'évaluation
     *
     * @param array $criteria
     * @return self
     */
    public function setCriteria(array $criteria): self
    {
        $this->criteria = $criteria;
        return $this;
    }
    
    /**
     * Ajouter un critère d'évaluation
     *
     * @param string $key
     * @param int $value
     * @return self
     */
    public function addCriterion(string $key, int $value): self
    {
        $this->criteria[$key] = max(1, min(5, $value));
        return $this;
    }
    
    /**
     * Obtenir l'état de l'avis
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }
    
    /**
     * Définir l'état de l'avis
     *
     * @param string $status
     * @return self
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }
    
    /**
     * Obtenir la date de création
     *
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }
    
    /**
     * Définir la date de création
     *
     * @param string $createdAt
     * @return self
     */
    public function setCreatedAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
    
    /**
     * Obtenir la date de mise à jour
     *
     * @return string
     */
    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }
    
    /**
     * Définir la date de mise à jour
     *
     * @param string $updatedAt
     * @return self
     */
    public function setUpdatedAt(string $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
    
    /**
     * Mettre à jour la date de mise à jour
     *
     * @return self
     */
    public function touch(): self
    {
        $this->updatedAt = (new DateTime())->format('Y-m-d H:i:s');
        return $this;
    }
    
    /**
     * Vérifier si l'avis est approuvé
     *
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->status === 'approuve';
    }
    
    /**
     * Approuver l'avis
     *
     * @return self
     */
    public function approve(): self
    {
        $this->status = 'approuve';
        return $this->touch();
    }
    
    /**
     * Signaler l'avis
     *
     * @return self
     */
    public function flag(): self
    {
        $this->status = 'signale';
        return $this->touch();
    }
    
    /**
     * Sérialiser l'objet pour JSON
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            '_id' => $this->id,
            'userId' => $this->userId,
            'covoiturageId' => $this->covoiturageId,
            'targetUserId' => $this->targetUserId,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'type' => $this->type,
            'criteria' => $this->criteria,
            'status' => $this->status,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt
        ];
    }
    
    /**
     * Créer une instance à partir d'un tableau
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $review = new self();
        
        if (isset($data['_id'])) {
            $review->setId((string)$data['_id']);
        }
        
        if (isset($data['userId'])) {
            $review->setUserId((int)$data['userId']);
        }
        
        if (isset($data['covoiturageId'])) {
            $review->setCovoiturageId((int)$data['covoiturageId']);
        }
        
        if (isset($data['targetUserId'])) {
            $review->setTargetUserId((int)$data['targetUserId']);
        }
        
        if (isset($data['rating'])) {
            $review->setRating((int)$data['rating']);
        }
        
        if (isset($data['comment'])) {
            $review->setComment($data['comment']);
        }
        
        if (isset($data['type'])) {
            $review->setType($data['type']);
        }
        
        if (isset($data['criteria'])) {
            $review->setCriteria((array)$data['criteria']);
        }
        
        if (isset($data['status'])) {
            $review->setStatus($data['status']);
        }
        
        if (isset($data['createdAt'])) {
            $review->setCreatedAt($data['createdAt']);
        }
        
        if (isset($data['updatedAt'])) {
            $review->setUpdatedAt($data['updatedAt']);
        }
        
        return $review;
    }
} 