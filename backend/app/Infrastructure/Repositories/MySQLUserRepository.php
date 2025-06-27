<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Entities\User;
use App\Infrastructure\Persistence\UserMapper;
use App\Core\Database\DatabaseInterface;
use PDO;

/**
 * Implémentation MySQL du repository pour les utilisateurs
 */
class MySQLUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private DatabaseInterface $database,
        private UserMapper $userMapper
    ) {}

    public function findById(int $userId): ?User
    {
        $pdo = $this->database->getMysqlConnection();
        
        $stmt = $pdo->prepare(
            'SELECT utilisateur_id, nom, prenom, email, pseudo, photo_path, date_creation,
                    COALESCE(AVG(n.note), 0) as average_rating,
                    COUNT(DISTINCT c.covoiturage_id) as total_rides
             FROM Utilisateur u
             LEFT JOIN Note n ON u.utilisateur_id = n.utilisateur_note_id
             LEFT JOIN Voiture v ON u.utilisateur_id = v.utilisateur_id
             LEFT JOIN Covoiturage c ON v.voiture_id = c.voiture_id
             WHERE u.utilisateur_id = ?
             GROUP BY u.utilisateur_id'
        );
        
        $stmt->execute([$userId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$userData) {
            return null;
        }
        
        return $this->userMapper->fromDatabase($userData);
    }

    public function findByEmail(string $email): ?User
    {
        $pdo = $this->database->getMysqlConnection();
        
        $stmt = $pdo->prepare(
            'SELECT utilisateur_id, nom, prenom, email, pseudo, photo_path, date_creation,
                    COALESCE(AVG(n.note), 0) as average_rating,
                    COUNT(DISTINCT c.covoiturage_id) as total_rides
             FROM Utilisateur u
             LEFT JOIN Note n ON u.utilisateur_id = n.utilisateur_note_id
             LEFT JOIN Voiture v ON u.utilisateur_id = v.utilisateur_id
             LEFT JOIN Covoiturage c ON v.voiture_id = c.voiture_id
             WHERE u.email = ?
             GROUP BY u.utilisateur_id'
        );
        
        $stmt->execute([$email]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$userData) {
            return null;
        }
        
        return $this->userMapper->fromDatabase($userData);
    }

    public function findByUsername(string $username): ?User
    {
        $pdo = $this->database->getMysqlConnection();
        
        $stmt = $pdo->prepare(
            'SELECT utilisateur_id, nom, prenom, email, pseudo, photo_path, date_creation,
                    COALESCE(AVG(n.note), 0) as average_rating,
                    COUNT(DISTINCT c.covoiturage_id) as total_rides
             FROM Utilisateur u
             LEFT JOIN Note n ON u.utilisateur_id = n.utilisateur_note_id
             LEFT JOIN Voiture v ON u.utilisateur_id = v.utilisateur_id
             LEFT JOIN Covoiturage c ON v.voiture_id = c.voiture_id
             WHERE u.pseudo = ?
             GROUP BY u.utilisateur_id'
        );
        
        $stmt->execute([$username]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$userData) {
            return null;
        }
        
        return $this->userMapper->fromDatabase($userData);
    }

    public function create(array $userData): int
    {
        $pdo = $this->database->getMysqlConnection();
        
        $stmt = $pdo->prepare(
            'INSERT INTO Utilisateur (nom, prenom, email, pseudo, mot_passe, photo_path, date_creation)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        
        $stmt->execute([
            $userData['nom'] ?? '',
            $userData['prenom'] ?? '',
            $userData['email'],
            $userData['pseudo'] ?? $userData['username'] ?? '',
            $userData['mot_passe'] ?? '',
            $userData['photo_path'] ?? null,
            $userData['date_creation'] ?? date('Y-m-d H:i:s')
        ]);
        
        return (int)$pdo->lastInsertId();
    }

    public function update(User $user): bool
    {
        $pdo = $this->database->getMysqlConnection();
        
        $stmt = $pdo->prepare(
            'UPDATE Utilisateur 
             SET nom = ?, prenom = ?, email = ?, pseudo = ?, photo_path = ?
             WHERE utilisateur_id = ?'
        );
        
        // Diviser le nom complet en nom et prénom si nécessaire
        $nameParts = explode(' ', $user->getName(), 2);
        $nom = $nameParts[0] ?? '';
        $prenom = $nameParts[1] ?? '';
        
        return $stmt->execute([
            $nom,
            $prenom,
            $user->getEmail()->getValue(),
            $user->getUsername(),
            $user->getPhotoPath(),
            $user->getId()
        ]);
    }

    public function delete(int $userId): bool
    {
        $pdo = $this->database->getMysqlConnection();
        $stmt = $pdo->prepare('DELETE FROM Utilisateur WHERE utilisateur_id = ?');
        return $stmt->execute([$userId]);
    }

    public function getUserRoles(int $userId): array
    {
        $pdo = $this->database->getMysqlConnection();
        
        $stmt = $pdo->prepare(
            'SELECT r.role_id as id, r.libelle as name
             FROM Role r
             JOIN Possede p ON r.role_id = p.role_id
             WHERE p.utilisateur_id = ?'
        );
        
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addRole(int $userId, string $roleName): bool
    {
        $pdo = $this->database->getMysqlConnection();
        
        // Vérifier si le rôle existe
        $roleStmt = $pdo->prepare('SELECT role_id FROM Role WHERE LOWER(libelle) = ?');
        $roleStmt->execute([strtolower($roleName)]);
        $roleId = $roleStmt->fetchColumn();
        
        if (!$roleId) {
            // Créer le rôle s'il n'existe pas
            $createRoleStmt = $pdo->prepare('INSERT INTO Role (libelle) VALUES (?)');
            $createRoleStmt->execute([ucfirst($roleName)]);
            $roleId = $pdo->lastInsertId();
        }
        
        // Vérifier si l'utilisateur a déjà ce rôle
        $checkStmt = $pdo->prepare(
            'SELECT COUNT(*) FROM Possede WHERE utilisateur_id = ? AND role_id = ?'
        );
        $checkStmt->execute([$userId, $roleId]);
        
        if ((int)$checkStmt->fetchColumn() > 0) {
            return true; // Déjà présent
        }
        
        // Ajouter le rôle
        $stmt = $pdo->prepare('INSERT INTO Possede (utilisateur_id, role_id) VALUES (?, ?)');
        return $stmt->execute([$userId, $roleId]);
    }

    public function removeRole(int $userId, string $roleName): bool
    {
        $pdo = $this->database->getMysqlConnection();
        
        $stmt = $pdo->prepare(
            'DELETE p FROM Possede p
             JOIN Role r ON p.role_id = r.role_id
             WHERE p.utilisateur_id = ? AND LOWER(r.libelle) = ?'
        );
        
        return $stmt->execute([$userId, strtolower($roleName)]);
    }

    public function hasRole(int $userId, string $roleName): bool
    {
        $pdo = $this->database->getMysqlConnection();
        
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM Possede p
             JOIN Role r ON p.role_id = r.role_id
             WHERE p.utilisateur_id = ? AND LOWER(r.libelle) = ?'
        );
        
        $stmt->execute([$userId, strtolower($roleName)]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function createRoleRequest(int $userId, int $roleId): bool
    {
        $pdo = $this->database->getMysqlConnection();
        
        $stmt = $pdo->prepare(
            'INSERT INTO RoleRequest (user_id, role_id, status, created_at)
             VALUES (?, ?, ?, ?)'
        );
        
        return $stmt->execute([$userId, $roleId, 'pending', date('Y-m-d H:i:s')]);
    }

    public function hasPendingRoleRequest(int $userId): bool
    {
        $pdo = $this->database->getMysqlConnection();
        
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM RoleRequest WHERE user_id = ? AND status = ?'
        );
        
        $stmt->execute([$userId, 'pending']);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function getCompletedRidesCount(int $userId): int
    {
        $pdo = $this->database->getMysqlConnection();
        
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM Participation p
             JOIN StatutParticipation sp ON p.statut_id = sp.statut_id
             WHERE p.utilisateur_id = ? AND sp.libelle = ?'
        );
        
        $stmt->execute([$userId, 'complété']);
        return (int)$stmt->fetchColumn();
    }

    public function getCancelledRidesCount(int $userId): int
    {
        $pdo = $this->database->getMysqlConnection();
        
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM Participation p
             JOIN StatutParticipation sp ON p.statut_id = sp.statut_id
             WHERE p.utilisateur_id = ? AND sp.libelle = ?'
        );
        
        $stmt->execute([$userId, 'annulé']);
        return (int)$stmt->fetchColumn();
    }

    public function updateRating(int $userId, float $rating): bool
    {
        // La note moyenne est calculée automatiquement via les JOINs
        // Cette méthode pourrait être utilisée pour mettre en cache la note
        return true;
    }

    public function updateTotalRides(int $userId, int $totalRides): bool
    {
        // Le nombre total de trajets est calculé automatiquement via les JOINs
        // Cette méthode pourrait être utilisée pour mettre en cache le nombre
        return true;
    }

    public function findAll(int $page = 1, int $limit = 20): array
    {
        $pdo = $this->database->getMysqlConnection();
        $offset = ($page - 1) * $limit;
        
        $stmt = $pdo->prepare(
            'SELECT utilisateur_id, nom, prenom, email, pseudo, photo_path, date_creation,
                    COALESCE(AVG(n.note), 0) as average_rating,
                    COUNT(DISTINCT c.covoiturage_id) as total_rides
             FROM Utilisateur u
             LEFT JOIN Note n ON u.utilisateur_id = n.utilisateur_note_id
             LEFT JOIN Voiture v ON u.utilisateur_id = v.utilisateur_id
             LEFT JOIN Covoiturage c ON v.voiture_id = c.voiture_id
             GROUP BY u.utilisateur_id
             ORDER BY u.date_creation DESC
             LIMIT ? OFFSET ?'
        );
        
        $stmt->execute([$limit, $offset]);
        $usersData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $users = [];
        foreach ($usersData as $userData) {
            $users[] = $this->userMapper->fromDatabase($userData);
        }
        
        return $users;
    }

    public function search(string $query, int $limit = 20): array
    {
        $pdo = $this->database->getMysqlConnection();
        
        $stmt = $pdo->prepare(
            'SELECT utilisateur_id, nom, prenom, email, pseudo, photo_path, date_creation,
                    COALESCE(AVG(n.note), 0) as average_rating,
                    COUNT(DISTINCT c.covoiturage_id) as total_rides
             FROM Utilisateur u
             LEFT JOIN Note n ON u.utilisateur_id = n.utilisateur_note_id
             LEFT JOIN Voiture v ON u.utilisateur_id = v.utilisateur_id
             LEFT JOIN Covoiturage c ON v.voiture_id = c.voiture_id
             WHERE u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ? OR u.pseudo LIKE ?
             GROUP BY u.utilisateur_id
             ORDER BY u.nom ASC, u.prenom ASC
             LIMIT ?'
        );
        
        $searchTerm = '%' . $query . '%';
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit]);
        $usersData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $users = [];
        foreach ($usersData as $userData) {
            $users[] = $this->userMapper->fromDatabase($userData);
        }
        
        return $users;
    }
} 