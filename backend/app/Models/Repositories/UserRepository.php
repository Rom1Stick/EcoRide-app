<?php

namespace App\Models\Repositories;

use App\Models\Entities\User;
use PDO;

/**
 * Repository pour la gestion des utilisateurs
 */
class UserRepository extends BaseRepository
{
    /**
     * Constructeur
     *
     * @param PDO $pdo Instance de PDO
     */
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo, 'Utilisateur', 'utilisateur_id');
    }
    
    /**
     * Récupère tous les utilisateurs
     * 
     * @param int $page Numéro de page (commence à 1)
     * @param int $limit Nombre d'éléments par page
     * @return array<User> Liste des utilisateurs
     */
    public function findAll(int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} ORDER BY {$this->primaryKey} DESC LIMIT :limit OFFSET :offset");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $users = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = User::fromArray($row);
        }
        
        return $users;
    }
    
    /**
     * Récupère un utilisateur par son ID
     * 
     * @param int $id ID de l'utilisateur
     * @return User|null Utilisateur trouvé ou null
     */
    public function findUserById(int $id): ?User
    {
        $result = parent::findById($id);
        
        return $result ? User::fromArray($result) : null;
    }
    
    /**
     * Trouve un utilisateur par son email
     *
     * @param string $email Email de l'utilisateur
     * @return User|null L'utilisateur trouvé ou null si non trouvé
     */
    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE email = :email LIMIT 1");
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? User::fromArray($result) : null;
    }
    
    /**
     * Vérifie si un email existe déjà
     *
     * @param string $email Email à vérifier
     * @param int|null $excludeId ID de l'utilisateur à exclure (pour les mises à jour)
     * @return bool True si l'email existe
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE email = :email";
        $params = [':email' => $email];
        
        if ($excludeId !== null) {
            $sql .= " AND {$this->primaryKey} != :excludeId";
            $params[':excludeId'] = $excludeId;
        }
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    }
    
    /**
     * Crée un utilisateur en base de données
     *
     * @param object $entity Utilisateur à créer
     * @return int ID de l'utilisateur créé
     */
    public function create($entity): int
    {
        if (!$entity instanceof User) {
            throw new \InvalidArgumentException('$entity doit être une instance de User');
        }
        
        $data = $entity->toArray();
        unset($data['utilisateur_id']); // Supprime l'ID pour l'insertion
        
        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$this->table} ($fields) VALUES ($placeholders)";
        
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        
        $stmt->execute();
        
        return (int)$this->pdo->lastInsertId();
    }
    
    /**
     * Met à jour un utilisateur en base de données
     *
     * @param object $entity Utilisateur à mettre à jour
     * @return bool Succès de l'opération
     */
    public function update($entity): bool
    {
        if (!$entity instanceof User) {
            throw new \InvalidArgumentException('$entity doit être une instance de User');
        }
        
        if (empty($entity->utilisateur_id)) {
            return false;
        }
        
        $data = $entity->toArray();
        
        $setClause = [];
        foreach (array_keys($data) as $field) {
            if ($field !== $this->primaryKey) {
                $setClause[] = "$field = :$field";
            }
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . " WHERE {$this->primaryKey} = :{$this->primaryKey}";
        
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Sauvegarde un utilisateur (crée ou met à jour)
     *
     * @param User $user Utilisateur à sauvegarder
     * @return bool|int ID en cas de création, true en cas de mise à jour, false en cas d'échec
     */
    public function save(User $user)
    {
        if (empty($user->utilisateur_id)) {
            return $this->create($user);
        } else {
            return $this->update($user);
        }
    }
    
    /**
     * Supprime un utilisateur
     *
     * @param int $id ID de l'utilisateur
     * @return bool Succès de l'opération
     */
    public function delete(int $id): bool
    {
        return parent::delete($id);
    }
} 