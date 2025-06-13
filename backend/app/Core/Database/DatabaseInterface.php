<?php

namespace App\Core\Database;

use PDO;
use PDOStatement;

/**
 * Interface pour l'abstraction de la base de données
 */
interface DatabaseInterface
{
    /**
     * Obtient une connexion à la base de données
     */
    public function getConnection(): PDO;

    /**
     * Prépare une requête SQL
     */
    public function prepare(string $sql): PDOStatement;

    /**
     * Exécute une requête SQL directement
     */
    public function execute(string $sql, array $params = []): PDOStatement;

    /**
     * Obtient l'ID du dernier enregistrement inséré
     */
    public function lastInsertId(): string;

    /**
     * Démarre une transaction
     */
    public function beginTransaction(): bool;

    /**
     * Valide une transaction
     */
    public function commit(): bool;

    /**
     * Annule une transaction
     */
    public function rollback(): bool;

    /**
     * Vérifie si une transaction est active
     */
    public function inTransaction(): bool;
} 