<?php

namespace App\Core\Database;

use App\Core\Exceptions\ConnectionException;
use PDO;
use PDOException;

/**
 * Gestionnaire de connexion à la base de données MySQL
 * Implémente le pattern Singleton pour optimiser les ressources
 */
class SqlConnection
{
    private static ?SqlConnection $instance = null;
    private ?PDO $pdo = null;
    
    /**
     * Configuration de la connexion à partir des variables d'environnement
     */
    private function __construct()
    {
        $host = getenv('DB_HOST') ?: 'localhost';
        $dbname = getenv('DB_NAME') ?: 'ecoride';
        $username = getenv('DB_USER') ?: 'ecoride_user';
        $password = getenv('DB_PASSWORD') ?: 'password';
        $charset = getenv('DB_CHARSET') ?: 'utf8mb4';
        
        try {
            $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                // Activer le pooling de connexions pour l'éco-conception
                PDO::ATTR_PERSISTENT => true,
            ];
            
            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new ConnectionException('DATABASE', 'Cannot connect to MySQL: ' . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Empêche le clonage de l'instance (pattern Singleton)
     */
    private function __clone() {}
    
    /**
     * Récupère l'instance unique de connexion
     *
     * @return SqlConnection
     */
    public static function getInstance(): SqlConnection
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Récupère l'objet PDO
     *
     * @return PDO
     * @throws ConnectionException Si la connexion n'est pas établie
     */
    public function getPdo(): PDO
    {
        if ($this->pdo === null) {
            throw new ConnectionException('DATABASE', 'PDO connection is not established');
        }
        
        return $this->pdo;
    }
    
    /**
     * Commence une transaction
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->getPdo()->beginTransaction();
    }
    
    /**
     * Valide une transaction
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->getPdo()->commit();
    }
    
    /**
     * Annule une transaction
     *
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->getPdo()->rollBack();
    }
    
    /**
     * Vérifie si une transaction est active
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->getPdo()->inTransaction();
    }
} 