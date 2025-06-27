<?php

namespace App\Domain\Entities;

use App\Domain\ValueObjects\Email;
use InvalidArgumentException;

/**
 * Entité représentant un utilisateur
 */
class User
{
    private int $id;
    private string $username;
    private Email $email;
    private string $passwordHash;
    private ?string $profilePicture;
    private float $averageRating;
    private int $totalRatings;
    private \DateTime $createdAt;
    private bool $isActive;

    public function __construct(
        int $id,
        string $username,
        Email $email,
        string $passwordHash,
        ?string $profilePicture = null,
        float $averageRating = 0.0,
        int $totalRatings = 0,
        ?\DateTime $createdAt = null,
        bool $isActive = true
    ) {
        $this->validateId($id);
        $this->validateUsername($username);
        $this->validatePasswordHash($passwordHash);
        $this->validateRating($averageRating, $totalRatings);
        
        $this->id = $id;
        $this->username = trim($username);
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->profilePicture = $profilePicture;
        $this->averageRating = $averageRating;
        $this->totalRatings = $totalRatings;
        $this->createdAt = $createdAt ?? new \DateTime();
        $this->isActive = $isActive;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function getAverageRating(): float
    {
        return $this->averageRating;
    }

    public function getTotalRatings(): int
    {
        return $this->totalRatings;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * Met à jour le nom d'utilisateur
     */
    public function updateUsername(string $newUsername): void
    {
        $this->validateUsername($newUsername);
        $this->username = trim($newUsername);
    }

    /**
     * Met à jour l'email
     */
    public function updateEmail(Email $newEmail): void
    {
        $this->email = $newEmail;
    }

    /**
     * Met à jour le mot de passe
     */
    public function updatePassword(string $newPasswordHash): void
    {
        $this->validatePasswordHash($newPasswordHash);
        $this->passwordHash = $newPasswordHash;
    }

    /**
     * Met à jour la photo de profil
     */
    public function updateProfilePicture(?string $profilePicture): void
    {
        $this->profilePicture = $profilePicture;
    }

    /**
     * Vérifie si le mot de passe correspond
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }

    /**
     * Ajoute une nouvelle note
     */
    public function addRating(float $rating): void
    {
        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('La note doit être comprise entre 1 et 5');
        }

        $totalScore = $this->averageRating * $this->totalRatings + $rating;
        $this->totalRatings++;
        $this->averageRating = round($totalScore / $this->totalRatings, 2);
    }

    /**
     * Désactive le compte utilisateur
     */
    public function deactivate(): void
    {
        $this->isActive = false;
    }

    /**
     * Réactive le compte utilisateur
     */
    public function activate(): void
    {
        $this->isActive = true;
    }

    /**
     * Vérifie si l'utilisateur est un nouveau membre (moins de 30 jours)
     */
    public function isNewMember(): bool
    {
        $thirtyDaysAgo = new \DateTime('-30 days');
        return $this->createdAt > $thirtyDaysAgo;
    }

    /**
     * Vérifie si l'utilisateur a une bonne réputation
     */
    public function hasGoodReputation(): bool
    {
        return $this->totalRatings >= 5 && $this->averageRating >= 4.0;
    }

    /**
     * Retourne les informations utilisateur pour l'API
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email->getValue(),
            'profilePicture' => $this->profilePicture,
            'rating' => [
                'average' => $this->averageRating,
                'total' => $this->totalRatings
            ],
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'isActive' => $this->isActive,
            'isNewMember' => $this->isNewMember(),
            'hasGoodReputation' => $this->hasGoodReputation()
        ];
    }

    private function validateId(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('L\'ID utilisateur doit être positif');
        }
    }

    private function validateUsername(string $username): void
    {
        $username = trim($username);
        
        if (empty($username)) {
            throw new InvalidArgumentException('Le nom d\'utilisateur ne peut pas être vide');
        }
        
        if (strlen($username) < 3) {
            throw new InvalidArgumentException('Le nom d\'utilisateur doit contenir au moins 3 caractères');
        }
        
        if (strlen($username) > 50) {
            throw new InvalidArgumentException('Le nom d\'utilisateur ne peut pas dépasser 50 caractères');
        }
        
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            throw new InvalidArgumentException('Le nom d\'utilisateur ne peut contenir que des lettres, chiffres, tirets et underscores');
        }
    }

    private function validatePasswordHash(string $passwordHash): void
    {
        if (empty($passwordHash)) {
            throw new InvalidArgumentException('Le hash du mot de passe ne peut pas être vide');
        }
        
        // Vérifier que c'est un hash valide (commence par $2y$ pour bcrypt)
        if (!preg_match('/^\$2y\$/', $passwordHash)) {
            throw new InvalidArgumentException('Le hash du mot de passe n\'est pas au format bcrypt');
        }
    }

    private function validateRating(float $averageRating, int $totalRatings): void
    {
        if ($averageRating < 0 || $averageRating > 5) {
            throw new InvalidArgumentException('La note moyenne doit être comprise entre 0 et 5');
        }
        
        if ($totalRatings < 0) {
            throw new InvalidArgumentException('Le nombre total de notes ne peut pas être négatif');
        }
        
        if ($totalRatings === 0 && $averageRating !== 0.0) {
            throw new InvalidArgumentException('La note moyenne doit être 0 si aucune note n\'a été donnée');
        }
    }
} 