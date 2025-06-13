<?php

namespace App\Infrastructure\Database;

use App\Core\Database\DatabaseInterface;
use App\Core\Database;
use PDO;
use PDOStatement;

/**
 * Adaptateur pour la classe Database existante
 * Implémente DatabaseInterface en utilisant la classe Database legacy
 */
class DatabaseAdapter implements DatabaseInterface
{
    private Database $database;
    private PDO $connection;

    public function __construct(Database $database)
    {
        $this->database = $database;
        $this->connection = $this->database->getMysqlConnection();
    }

    /**
     * Obtient une connexion à la base de données
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Prépare une requête SQL
     */
    public function prepare(string $sql): PDOStatement
    {
        return $this->connection->prepare($sql);
    }

    /**
     * Exécute une requête SQL directement
     */
    public function execute(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Obtient l'ID du dernier enregistrement inséré
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Démarre une transaction
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Valide une transaction
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * Annule une transaction
     */
    public function rollback(): bool
    {
        return $this->connection->rollback();
    }

    /**
     * Vérifie si une transaction est active
     */
    public function inTransaction(): bool
    {
        return $this->connection->inTransaction();
    }
} 