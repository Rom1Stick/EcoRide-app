<?php

namespace App\Repositories\SQL;

use App\Core\Database\SqlConnection;
use App\Core\Exceptions\ConnectionException;
use App\Core\Exceptions\PersistenceException;
use App\Core\Exceptions\ValidationException;
use App\Models\Entities\User;
use App\Repositories\Interfaces\IUserRepository;
use PDO;
use PDOException;

/**
 * Implémentation du repository pour les utilisateurs
 */
class UserRepository implements IUserRepository
{
    private SqlConnection $dbConnection;
    
    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->dbConnection = SqlConnection::getInstance();
    }
    
    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?User
    {
        try {
            $pdo = $this->dbConnection->getPdo();
            
            $stmt = $pdo->prepare('
                SELECT * FROM Utilisateur 
                WHERE utilisateur_id = :id
            ');
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $data = $stmt->fetch();
            
            if (!$data) {
                return null;
            }
            
            $user = User::fromArray($data);
            $user->setRoles($this->getUserRoles($id));
            
            return $user;
        } catch (PDOException $e) {
            throw new PersistenceException('USER', 'READ', 'Failed to find user by ID: ' . $e->getMessage(), $id, 0, $e);
        } catch (ConnectionException $e) {
            throw $e;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function findAll(int $page = 1, int $limit = 20): array
    {
        try {
            $pdo = $this->dbConnection->getPdo();
            
            $offset = ($page - 1) * $limit;
            
            $stmt = $pdo->prepare('
                SELECT * FROM Utilisateur 
                ORDER BY nom, prenom
                LIMIT :limit OFFSET :offset
            ');
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $users = [];
            
            while ($data = $stmt->fetch()) {
                $user = User::fromArray($data);
                $user->setRoles($this->getUserRoles($user->getId()));
                $users[] = $user;
            }
            
            return $users;
        } catch (PDOException $e) {
            throw new PersistenceException('USER', 'READ', 'Failed to find all users: ' . $e->getMessage(), null, 0, $e);
        } catch (ConnectionException $e) {
            throw $e;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function create($entity): int
    {
        if (!($entity instanceof User)) {
            throw new ValidationException('USER', ['entity' => 'Entity must be an instance of User'], 'Invalid entity type');
        }
        
        $errors = $entity->validate();
        if (!empty($errors)) {
            throw new ValidationException('USER', $errors);
        }
        
        try {
            $pdo = $this->dbConnection->getPdo();
            $pdo->beginTransaction();
            
            $data = $entity->toArray();
            
            $stmt = $pdo->prepare('
                INSERT INTO Utilisateur (
                    nom, prenom, email, mot_passe, telephone, 
                    adresse_id, date_naissance, photo_path, pseudo, 
                    date_creation, derniere_connexion
                ) VALUES (
                    :nom, :prenom, :email, :mot_passe, :telephone, 
                    :adresse_id, :date_naissance, :photo_path, :pseudo, 
                    :date_creation, :derniere_connexion
                )
            ');
            
            $stmt->bindValue(':nom', $data['nom']);
            $stmt->bindValue(':prenom', $data['prenom']);
            $stmt->bindValue(':email', $data['email']);
            $stmt->bindValue(':mot_passe', $data['mot_passe']);
            $stmt->bindValue(':telephone', $data['telephone'] ?? null);
            $stmt->bindValue(':adresse_id', $data['adresse_id'] ?? null, $data['adresse_id'] ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':date_naissance', $data['date_naissance'] ?? null);
            $stmt->bindValue(':photo_path', $data['photo_path'] ?? null);
            $stmt->bindValue(':pseudo', $data['pseudo'] ?? null);
            $stmt->bindValue(':date_creation', $data['date_creation']);
            $stmt->bindValue(':derniere_connexion', $data['derniere_connexion'] ?? null);
            
            $stmt->execute();
            
            $userId = (int)$pdo->lastInsertId();
            
            // Ajouter les rôles
            foreach ($entity->getRoles() as $roleId) {
                $this->addUserRole($userId, $roleId);
            }
            
            $pdo->commit();
            
            return $userId;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollback();
            }
            
            throw new PersistenceException('USER', 'CREATE', 'Failed to create user: ' . $e->getMessage(), null, 0, $e);
        } catch (ConnectionException $e) {
            throw $e;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function update($entity): bool
    {
        if (!($entity instanceof User)) {
            throw new ValidationException('USER', ['entity' => 'Entity must be an instance of User'], 'Invalid entity type');
        }
        
        if ($entity->getId() === null) {
            throw new ValidationException('USER', ['id' => 'User ID is required for update']);
        }
        
        $errors = $entity->validate();
        if (!empty($errors)) {
            throw new ValidationException('USER', $errors);
        }
        
        try {
            $pdo = $this->dbConnection->getPdo();
            $pdo->beginTransaction();
            
            $data = $entity->toArray();
            
            $stmt = $pdo->prepare('
                UPDATE Utilisateur SET 
                    nom = :nom,
                    prenom = :prenom,
                    email = :email,
                    mot_passe = :mot_passe,
                    telephone = :telephone,
                    adresse_id = :adresse_id,
                    date_naissance = :date_naissance,
                    photo_path = :photo_path,
                    pseudo = :pseudo,
                    derniere_connexion = :derniere_connexion
                WHERE utilisateur_id = :id
            ');
            
            $stmt->bindValue(':nom', $data['nom']);
            $stmt->bindValue(':prenom', $data['prenom']);
            $stmt->bindValue(':email', $data['email']);
            $stmt->bindValue(':mot_passe', $data['mot_passe']);
            $stmt->bindValue(':telephone', $data['telephone'] ?? null);
            $stmt->bindValue(':adresse_id', $data['adresse_id'] ?? null, $data['adresse_id'] ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':date_naissance', $data['date_naissance'] ?? null);
            $stmt->bindValue(':photo_path', $data['photo_path'] ?? null);
            $stmt->bindValue(':pseudo', $data['pseudo'] ?? null);
            $stmt->bindValue(':derniere_connexion', $data['derniere_connexion'] ?? null);
            $stmt->bindValue(':id', $entity->getId(), PDO::PARAM_INT);
            
            $stmt->execute();
            
            // Mettre à jour les rôles si nécessaire
            $currentRoles = $this->getUserRoles($entity->getId());
            $newRoles = $entity->getRoles();
            
            // Supprimer les rôles qui ne sont plus présents
            foreach ($currentRoles as $roleId) {
                if (!in_array($roleId, $newRoles)) {
                    $this->removeUserRole($entity->getId(), $roleId);
                }
            }
            
            // Ajouter les nouveaux rôles
            foreach ($newRoles as $roleId) {
                if (!in_array($roleId, $currentRoles)) {
                    $this->addUserRole($entity->getId(), $roleId);
                }
            }
            
            $pdo->commit();
            
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollback();
            }
            
            throw new PersistenceException(
                'USER', 
                'UPDATE', 
                'Failed to update user: ' . $e->getMessage(), 
                $entity->getId(), 
                0, 
                $e
            );
        } catch (ConnectionException $e) {
            throw $e;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        try {
            $pdo = $this->dbConnection->getPdo();
            $pdo->beginTransaction();
            
            // Supprimer d'abord les rôles (sera fait automatiquement par la contrainte ON DELETE CASCADE)
            
            $stmt = $pdo->prepare('DELETE FROM Utilisateur WHERE utilisateur_id = :id');
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $rowCount = $stmt->rowCount();
            
            $pdo->commit();
            
            return $rowCount > 0;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollback();
            }
            
            throw new PersistenceException('USER', 'DELETE', 'Failed to delete user: ' . $e->getMessage(), $id, 0, $e);
        } catch (ConnectionException $e) {
            throw $e;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        try {
            $pdo = $this->dbConnection->getPdo();
            
            $stmt = $pdo->query('SELECT COUNT(*) FROM Utilisateur');
            
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new PersistenceException('USER', 'COUNT', 'Failed to count users: ' . $e->getMessage(), null, 0, $e);
        } catch (ConnectionException $e) {
            throw $e;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByEmail(string $email): ?User
    {
        try {
            $pdo = $this->dbConnection->getPdo();
            
            $stmt = $pdo->prepare('SELECT * FROM Utilisateur WHERE email = :email');
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            $data = $stmt->fetch();
            
            if (!$data) {
                return null;
            }
            
            $user = User::fromArray($data);
            $user->setRoles($this->getUserRoles($user->getId()));
            
            return $user;
        } catch (PDOException $e) {
            throw new PersistenceException('USER', 'READ', 'Failed to find user by email: ' . $e->getMessage(), null, 0, $e);
        } catch (ConnectionException $e) {
            throw $e;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByNickname(string $nickname): ?User
    {
        try {
            $pdo = $this->dbConnection->getPdo();
            
            $stmt = $pdo->prepare('SELECT * FROM Utilisateur WHERE pseudo = :pseudo');
            $stmt->bindParam(':pseudo', $nickname);
            $stmt->execute();
            
            $data = $stmt->fetch();
            
            if (!$data) {
                return null;
            }
            
            $user = User::fromArray($data);
            $user->setRoles($this->getUserRoles($user->getId()));
            
            return $user;
        } catch (PDOException $e) {
            throw new PersistenceException('USER', 'READ', 'Failed to find user by nickname: ' . $e->getMessage(), null, 0, $e);
        } catch (ConnectionException $e) {
            throw $e;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function searchByName(string $query, int $page = 1, int $limit = 20): array
    {
        try {
            $pdo = $this->dbConnection->getPdo();
            
            $offset = ($page - 1) * $limit;
            $searchTerm = '%' . $query . '%';
            
            $stmt = $pdo->prepare('
                SELECT * FROM Utilisateur 
                WHERE nom LIKE :search OR prenom LIKE :search
                ORDER BY nom, prenom
                LIMIT :limit OFFSET :offset
            ');
            $stmt->bindParam(':search', $searchTerm);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $users = [];
            
            while ($data = $stmt->fetch()) {
                $user = User::fromArray($data);
                $user->setRoles($this->getUserRoles($user->getId()));
                $users[] = $user;
            }
            
            return $users;
        } catch (PDOException $e) {
            throw new PersistenceException('USER', 'SEARCH', 'Failed to search users by name: ' . $e->getMessage(), null, 0, $e);
        } catch (ConnectionException $e) {
            throw $e;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getUserRoles(int $userId): array
    {
        try {
            $pdo = $this->dbConnection->getPdo();
            
            $stmt = $pdo->prepare('
                SELECT role_id FROM Possede 
                WHERE utilisateur_id = :userId
            ');
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $roles = [];
            
            while ($data = $stmt->fetch()) {
                $roles[] = (int)$data['role_id'];
            }
            
            return $roles;
        } catch (PDOException $e) {
            throw new PersistenceException('USER', 'READ_ROLES', 'Failed to get user roles: ' . $e->getMessage(), $userId, 0, $e);
        } catch (ConnectionException $e) {
            throw $e;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function addUserRole(int $userId, int $roleId): bool
    {
        try {
            $pdo = $this->dbConnection->getPdo();
            
            // Vérifier si l'association existe déjà
            $stmt = $pdo->prepare('
                SELECT COUNT(*) FROM Possede 
                WHERE utilisateur_id = :userId AND role_id = :roleId
            ');
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':roleId', $roleId, PDO::PARAM_INT);
            $stmt->execute();
            
            if ((int)$stmt->fetchColumn() > 0) {
                return true; // L'association existe déjà
            }
            
            $stmt = $pdo->prepare('
                INSERT INTO Possede (utilisateur_id, role_id) 
                VALUES (:userId, :roleId)
            ');
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':roleId', $roleId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new PersistenceException('USER', 'ADD_ROLE', 'Failed to add user role: ' . $e->getMessage(), $userId, 0, $e);
        } catch (ConnectionException $e) {
            throw $e;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function removeUserRole(int $userId, int $roleId): bool
    {
        try {
            $pdo = $this->dbConnection->getPdo();
            
            $stmt = $pdo->prepare('
                DELETE FROM Possede 
                WHERE utilisateur_id = :userId AND role_id = :roleId
            ');
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':roleId', $roleId, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new PersistenceException('USER', 'REMOVE_ROLE', 'Failed to remove user role: ' . $e->getMessage(), $userId, 0, $e);
        } catch (ConnectionException $e) {
            throw $e;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function userHasRole(int $userId, int $roleId): bool
    {
        try {
            $pdo = $this->dbConnection->getPdo();
            
            $stmt = $pdo->prepare('
                SELECT COUNT(*) FROM Possede 
                WHERE utilisateur_id = :userId AND role_id = :roleId
            ');
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':roleId', $roleId, PDO::PARAM_INT);
            $stmt->execute();
            
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            throw new PersistenceException('USER', 'CHECK_ROLE', 'Failed to check user role: ' . $e->getMessage(), $userId, 0, $e);
        } catch (ConnectionException $e) {
            throw $e;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function updateLastConnection(int $userId): bool
    {
        try {
            $pdo = $this->dbConnection->getPdo();
            
            $now = date('Y-m-d H:i:s');
            
            $stmt = $pdo->prepare('
                UPDATE Utilisateur 
                SET derniere_connexion = :lastConnection 
                WHERE utilisateur_id = :userId
            ');
            $stmt->bindParam(':lastConnection', $now);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new PersistenceException('USER', 'UPDATE_CONNECTION', 'Failed to update last connection: ' . $e->getMessage(), $userId, 0, $e);
        } catch (ConnectionException $e) {
            throw $e;
        }
    }
} 