<?php

namespace App\Models\Repositories;

use PDO;

/**
 * Classe de base pour tous les repositories SQL
 */
abstract class BaseRepository
{
    protected PDO $pdo;
    protected string $tableName;
    protected string $primaryKey;
    
    /**
     * Constructeur
     *
     * @param PDO $pdo Instance de PDO
     * @param string $tableName Nom de la table
     * @param string $primaryKey Nom de la clé primaire
     */
    public function __construct(PDO $pdo, string $tableName, string $primaryKey)
    {
        $this->pdo = $pdo;
        $this->tableName = $tableName;
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
     * Compte le nombre total d'enregistrements dans la table
     *
     * @return int Nombre total d'enregistrements
     */
    public function count(): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->tableName}");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Récupère tous les enregistrements de la table avec pagination
     *
     * @param int $page Numéro de page (commence à 1)
     * @param int $limit Nombre d'enregistrements par page
     * @return array Enregistrements de la page
     */
    public function findAll(int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->tableName} LIMIT :limit OFFSET :offset");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupère un enregistrement par sa clé primaire
     *
     * @param int $id Valeur de la clé primaire
     * @return array|null Enregistrement ou null si non trouvé
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->tableName} WHERE {$this->primaryKey} = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    /**
     * Supprime un enregistrement par sa clé primaire
     *
     * @param int $id Valeur de la clé primaire
     * @return bool Succès de l'opération
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->tableName} WHERE {$this->primaryKey} = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
} 