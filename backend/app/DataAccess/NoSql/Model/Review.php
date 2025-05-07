<?php

namespace App\DataAccess\NoSql\Model;

/**
 * Modèle pour les avis (reviews) stockés dans MongoDB
 */
class Review implements \JsonSerializable
{
    /**
     * @var string|null ID MongoDB
     */
    private $id;

    /**
     * @var int ID du covoiturage
     */
    private $covoiturageId;

    /**
     * @var int ID de l'utilisateur qui a laissé l'avis
     */
    private $userId;

    /**
     * @var int ID de l'utilisateur évalué (cible)
     */
    private $targetUserId;

    /**
     * @var float Note (rating) sur 5
     */
    private $rating;

    /**
     * @var string Commentaire
     */
    private $comment;

    /**
     * @var string Date de création
     */
    private $createdAt;

    /**
     * @var string|null Date de mise à jour
     */
    private $updatedAt;

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return Review
     */
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getCovoiturageId(): int
    {
        return $this->covoiturageId;
    }

    /**
     * @param int $covoiturageId
     * @return Review
     */
    public function setCovoiturageId(int $covoiturageId): self
    {
        $this->covoiturageId = $covoiturageId;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return Review
     */
    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return int
     */
    public function getTargetUserId(): int
    {
        return $this->targetUserId;
    }

    /**
     * @param int $targetUserId
     * @return Review
     */
    public function setTargetUserId(int $targetUserId): self
    {
        $this->targetUserId = $targetUserId;
        return $this;
    }

    /**
     * @return float
     */
    public function getRating(): float
    {
        return $this->rating;
    }

    /**
     * @param float $rating
     * @return Review
     */
    public function setRating(float $rating): self
    {
        $this->rating = $rating;
        return $this;
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     * @return Review
     */
    public function setComment(string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * @param string $createdAt
     * @return Review
     */
    public function setCreatedAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    /**
     * @param string $updatedAt
     * @return Review
     */
    public function setUpdatedAt(string $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Convertit l'objet en tableau pour JSON
     * 
     * @return array
     */
    public function jsonSerialize(): array
    {
        $data = [
            'covoiturageId' => $this->covoiturageId,
            'userId' => $this->userId,
            'targetUserId' => $this->targetUserId,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'createdAt' => $this->createdAt
        ];

        if ($this->id) {
            $data['_id'] = $this->id;
        }

        if ($this->updatedAt) {
            $data['updatedAt'] = $this->updatedAt;
        }

        return $data;
    }

    /**
     * Crée un objet Review à partir d'un document MongoDB
     * 
     * @param object $document
     * @return Review
     */
    public static function fromDocument(object $document): self
    {
        $review = new self();

        if (isset($document->_id)) {
            $review->setId((string) $document->_id);
        }

        $review->setCovoiturageId($document->covoiturageId)
            ->setUserId($document->userId)
            ->setTargetUserId($document->targetUserId)
            ->setRating($document->rating)
            ->setComment($document->comment);

        // Convertir la date MongoDB en chaîne pour notre modèle
        if (isset($document->createdAt)) {
            if ($document->createdAt instanceof \MongoDB\BSON\UTCDateTime) {
                $dateTime = $document->createdAt->toDateTime();
                $review->setCreatedAt($dateTime->format('Y-m-d H:i:s'));
            } else {
                $review->setCreatedAt($document->createdAt);
            }
        }

        if (isset($document->updatedAt)) {
            if ($document->updatedAt instanceof \MongoDB\BSON\UTCDateTime) {
                $dateTime = $document->updatedAt->toDateTime();
                $review->setUpdatedAt($dateTime->format('Y-m-d H:i:s'));
            } else {
                $review->setUpdatedAt($document->updatedAt);
            }
        }

        return $review;
    }
} 