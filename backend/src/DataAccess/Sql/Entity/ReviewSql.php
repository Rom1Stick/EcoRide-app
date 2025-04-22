<?php

namespace App\DataAccess\Sql\Entity;

/**
 * Classe ReviewSql
 * 
 * Représente un avis laissé après un trajet dans le système
 */
class ReviewSql
{
    /**
     * Identifiant unique de l'avis
     * 
     * @var int|null
     */
    private ?int $id = null;

    /**
     * Identifiant du trajet concerné
     * 
     * @var int
     */
    private int $tripId;

    /**
     * Identifiant de l'utilisateur qui laisse l'avis
     * 
     * @var int
     */
    private int $reviewerId;

    /**
     * Identifiant de l'utilisateur évalué (conducteur ou passager)
     * 
     * @var int
     */
    private int $userId;

    /**
     * Type d'avis ('driver' ou 'passenger')
     * 
     * @var string
     */
    private string $type;

    /**
     * Note (1-5)
     * 
     * @var int
     */
    private int $rating;

    /**
     * Commentaire
     * 
     * @var string|null
     */
    private ?string $comment = null;

    /**
     * Indicateur si l'avis est vérifié
     * 
     * @var bool
     */
    private bool $verified = false;

    /**
     * Indicateur si l'avis a été signalé
     * 
     * @var bool
     */
    private bool $reported = false;

    /**
     * Raison du signalement
     * 
     * @var string|null
     */
    private ?string $reportReason = null;

    /**
     * Date de création de l'enregistrement
     * 
     * @var \DateTime
     */
    private \DateTime $createdAt;

    /**
     * Date de dernière mise à jour
     * 
     * @var \DateTime|null
     */
    private ?\DateTime $updatedAt = null;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * Récupère l'identifiant
     * 
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Définit l'identifiant
     * 
     * @param int|null $id Identifiant
     * @return self
     */
    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Récupère l'identifiant du trajet
     * 
     * @return int
     */
    public function getTripId(): int
    {
        return $this->tripId;
    }

    /**
     * Définit l'identifiant du trajet
     * 
     * @param int $tripId Identifiant du trajet
     * @return self
     */
    public function setTripId(int $tripId): self
    {
        $this->tripId = $tripId;
        return $this;
    }

    /**
     * Récupère l'identifiant de l'utilisateur qui laisse l'avis
     * 
     * @return int
     */
    public function getReviewerId(): int
    {
        return $this->reviewerId;
    }

    /**
     * Définit l'identifiant de l'utilisateur qui laisse l'avis
     * 
     * @param int $reviewerId Identifiant de l'utilisateur
     * @return self
     */
    public function setReviewerId(int $reviewerId): self
    {
        $this->reviewerId = $reviewerId;
        return $this;
    }

    /**
     * Récupère l'identifiant de l'utilisateur évalué
     * 
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * Définit l'identifiant de l'utilisateur évalué
     * 
     * @param int $userId Identifiant de l'utilisateur
     * @return self
     */
    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Récupère le type d'avis
     * 
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Définit le type d'avis
     * 
     * @param string $type Type d'avis ('driver' ou 'passenger')
     * @return self
     */
    public function setType(string $type): self
    {
        if (!in_array($type, ['driver', 'passenger'])) {
            throw new \InvalidArgumentException("Type d'avis non valide: $type");
        }
        
        $this->type = $type;
        return $this;
    }

    /**
     * Récupère la note
     * 
     * @return int
     */
    public function getRating(): int
    {
        return $this->rating;
    }

    /**
     * Définit la note
     * 
     * @param int $rating Note (1-5)
     * @return self
     */
    public function setRating(int $rating): self
    {
        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException("La note doit être comprise entre 1 et 5");
        }
        
        $this->rating = $rating;
        return $this;
    }

    /**
     * Récupère le commentaire
     * 
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * Définit le commentaire
     * 
     * @param string|null $comment Commentaire
     * @return self
     */
    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * Indique si l'avis est vérifié
     * 
     * @return bool
     */
    public function isVerified(): bool
    {
        return $this->verified;
    }

    /**
     * Définit si l'avis est vérifié
     * 
     * @param bool $verified Indicateur de vérification
     * @return self
     */
    public function setVerified(bool $verified): self
    {
        $this->verified = $verified;
        return $this;
    }

    /**
     * Indique si l'avis a été signalé
     * 
     * @return bool
     */
    public function isReported(): bool
    {
        return $this->reported;
    }

    /**
     * Définit si l'avis a été signalé
     * 
     * @param bool $reported Indicateur de signalement
     * @return self
     */
    public function setReported(bool $reported): self
    {
        $this->reported = $reported;
        return $this;
    }

    /**
     * Récupère la raison du signalement
     * 
     * @return string|null
     */
    public function getReportReason(): ?string
    {
        return $this->reportReason;
    }

    /**
     * Définit la raison du signalement
     * 
     * @param string|null $reportReason Raison du signalement
     * @return self
     */
    public function setReportReason(?string $reportReason): self
    {
        $this->reportReason = $reportReason;
        return $this;
    }

    /**
     * Récupère la date de création
     * 
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * Définit la date de création
     * 
     * @param \DateTime $createdAt Date de création
     * @return self
     */
    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Récupère la date de mise à jour
     * 
     * @return \DateTime|null
     */
    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Définit la date de mise à jour
     * 
     * @param \DateTime|null $updatedAt Date de mise à jour
     * @return self
     */
    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Met à jour la date de dernière modification
     * 
     * @return self
     */
    public function touch(): self
    {
        $this->updatedAt = new \DateTime();
        return $this;
    }

    /**
     * Signale l'avis
     * 
     * @param string $reason Raison du signalement
     * @return self
     */
    public function report(string $reason): self
    {
        $this->reported = true;
        $this->reportReason = $reason;
        $this->touch();
        
        return $this;
    }

    /**
     * Vérifie l'avis
     * 
     * @return self
     */
    public function verify(): self
    {
        $this->verified = true;
        $this->touch();
        
        return $this;
    }

    /**
     * Vérifie si l'avis est positif (4 ou 5)
     * 
     * @return bool
     */
    public function isPositive(): bool
    {
        return $this->rating >= 4;
    }

    /**
     * Vérifie si l'avis est négatif (1 ou 2)
     * 
     * @return bool
     */
    public function isNegative(): bool
    {
        return $this->rating <= 2;
    }

    /**
     * Vérifie si l'avis est neutre (3)
     * 
     * @return bool
     */
    public function isNeutral(): bool
    {
        return $this->rating === 3;
    }
} 