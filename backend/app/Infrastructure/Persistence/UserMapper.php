<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\User;
use App\Domain\ValueObjects\Email;
use DateTime;

/**
 * Mapper pour convertir les données SQL en entités User
 */
class UserMapper
{
    /**
     * Convertit un tableau de données SQL en entité User
     */
    public function mapToEntity(array $data): User
    {
        $email = new Email($data['email']);
        
        // Les données utilisateur peuvent venir du JOIN avec la table Utilisateur
        $createdAt = isset($data['date_creation']) 
            ? new DateTime($data['date_creation']) 
            : new DateTime();

        // Calcul de la note moyenne depuis les données agrégées
        $averageRating = (float) ($data['note_moyenne'] ?? 0.0);
        $totalRatings = (int) ($data['nombre_avis'] ?? 0);

        return new User(
            (int) $data['utilisateur_id'],
            $data['pseudo'],
            $email,
            $this->generateDummyPasswordHash(), // Pas de mot de passe dans les requêtes de lecture
            $data['photo_path'] ?? null,
            $averageRating,
            $totalRatings,
            $createdAt,
            true // Supposons que les utilisateurs sont actifs par défaut
        );
    }

    /**
     * Convertit une entité User en tableau pour l'insertion/mise à jour SQL
     */
    public function mapToArray(User $user): array
    {
        return [
            'utilisateur_id' => $user->getId(),
            'pseudo' => $user->getUsername(),
            'email' => $user->getEmail()->getValue(),
            'mot_de_passe' => $user->getPasswordHash(),
            'photo_path' => $user->getProfilePicture(),
            'date_creation' => $user->getCreatedAt()->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Mappe des données utilisateur spécifiques d'un conducteur
     */
    public function mapDriverFromRideData(array $data): User
    {
        return $this->mapToEntity([
            'utilisateur_id' => $data['utilisateur_id'],
            'pseudo' => $data['pseudo'],
            'email' => $data['email'] ?? $data['pseudo'] . '@example.com', // Fallback si email manquant
            'photo_path' => $data['photo_path'] ?? null,
            'note_moyenne' => $data['note_moyenne'] ?? 0.0,
            'nombre_avis' => $data['nombre_avis'] ?? 0,
            'date_creation' => $data['date_creation'] ?? date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Convertit plusieurs résultats SQL en array d'entités User
     */
    public function mapToEntities(array $results): array
    {
        $users = [];
        foreach ($results as $result) {
            $users[] = $this->mapToEntity($result);
        }
        return $users;
    }

    /**
     * Génère un hash de mot de passe fictif pour les lectures
     * En production, les mots de passe ne devraient pas être inclus dans les requêtes de lecture
     */
    private function generateDummyPasswordHash(): string
    {
        return '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // Hash de "password"
    }
} 