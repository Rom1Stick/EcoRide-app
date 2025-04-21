<?php

namespace App\Models\Repositories;

use App\Repositories\Interfaces\IRepository;
use PDO;

/**
 * Classe de base pour tous les repositories
 */
abstract class BaseRepository implements IRepository
{
    protected PDO $pdo;
    protected string $table;
    protected string $primaryKey;
    
    /**
     * Constructeur
     *
     * @param PDO $pdo Instance de PDO
     * @param string $table Nom de la table
     * @param string $primaryKey Nom de la clé primaire
     */
    public function __construct(PDO $pdo, string $table, string $primaryKey)
    {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->primaryKey = $primaryKey;
    }
    
    /**
     * Exécute une requête et retourne tous les résultats
     *
     * @param string $query Requête SQL
     * @param array $params Paramètres de la requête
     * @return array Résultats de la requête
     */
    protected function fetchAll(string $query, array $params = []): array
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Exécute une requête et retourne le premier résultat
     *
     * @param string $query Requête SQL
     * @param array $params Paramètres de la requête
     * @return array|false Résultat de la requête ou false si aucun résultat
     */
    protected function fetchOne(string $query, array $params = [])
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Exécute une requête et retourne l'ID de la dernière insertion
     *
     * @param string $query Requête SQL
     * @param array $params Paramètres de la requête
     * @return int|null ID de la dernière insertion ou null si échec
     */
    protected function executeInsert(string $query, array $params = []): ?int
    {
        $stmt = $this->pdo->prepare($query);
        $result = $stmt->execute($params);
        
        return $result ? (int)$this->pdo->lastInsertId() : null;
    }
    
    /**
     * Exécute une requête de mise à jour ou de suppression
     *
     * @param string $query Requête SQL
     * @param array $params Paramètres de la requête
     * @return bool Succès de l'opération
     */
    protected function executeUpdate(string $query, array $params = []): bool
    {
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }
    
    /**
     * Récupère tous les enregistrements avec pagination
     *
     * @param int $page Numéro de page
     * @param int $limit Nombre d'éléments par page
     * @return array Tableau d'enregistrements
     */
    public function findAll(int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} ORDER BY {$this->primaryKey} DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupère un enregistrement par son ID
     *
     * @param int $id ID de l'enregistrement
     * @return array|null Enregistrement trouvé ou null si inexistant
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result !== false ? $result : null;
    }
    
    /**
     * Compte le nombre total d'enregistrements
     *
     * @return int Nombre d'enregistrements
     */
    public function count(): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table}");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
    
    /**
     * Supprime un enregistrement par son ID
     *
     * @param int $id ID de l'enregistrement à supprimer
     * @return bool Succès de l'opération
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    /**
     * Vérifie si un enregistrement existe par son ID
     *
     * @param int $id ID de l'enregistrement
     * @return bool Existence de l'enregistrement
     */
    public function exists(int $id): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} WHERE {$this->primaryKey} = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    }
} 