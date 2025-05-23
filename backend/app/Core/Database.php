<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Classe Database
 *
 * Cette classe gère les connexions à la base de données MySQL
 */
class Database
{
    /**
     * Connexion PDO à MySQL
     *
     * @var PDO|null
     */
    private ?PDO $mysqlConnection = null;

    /**
     * Obtient une connexion à la base de données MySQL
     *
     * @return PDO
     */
    public function getMysqlConnection(): PDO
    {
        if ($this->mysqlConnection === null) {
            $this->connectToMysql();
        }

        return $this->mysqlConnection;
    }

    /**
     * Établit une connexion à la base de données MySQL
     *
     * @return void
     * @throws PDOException Si la connexion échoue
     */
    private function connectToMysql(): void
    {
        // Essayer d'abord les variables d'environnement classiques
        $host = getenv('DB_HOST') ?: $_ENV['DB_HOST'] ?? null;
        $port = getenv('DB_PORT') ?: $_ENV['DB_PORT'] ?? '3306';
        $database = getenv('DB_DATABASE') ?: $_ENV['DB_DATABASE'] ?? null;
        $username = getenv('DB_USERNAME') ?: $_ENV['DB_USERNAME'] ?? null;
        $password = getenv('DB_PASSWORD') ?: $_ENV['DB_PASSWORD'] ?? null;
        
        // Si les variables ne sont pas définies, parser JAWSDB_URL directement
        if (!$host || !$database || !$username || !$password) {
            $jawsdbUrl = getenv('JAWSDB_URL') ?: $_ENV['JAWSDB_URL'] ?? null;
            $databaseUrl = getenv('DATABASE_URL') ?: $_ENV['DATABASE_URL'] ?? null;
            $dbUrl = $jawsdbUrl ?: $databaseUrl;
            
            if ($dbUrl) {
                $urlParts = parse_url($dbUrl);
                if ($urlParts) {
                    $host = $urlParts['host'];
                    $port = $urlParts['port'] ?? 3306;
                    $database = ltrim($urlParts['path'], '/');
                    $username = $urlParts['user'];
                    $password = $urlParts['pass'];
                } else {
                    error_log("Impossible de parser l'URL de base de données: $dbUrl");
                }
            }
        }
        
        // Valeurs par défaut si rien n'est trouvé
        $host = $host ?: 'mysql';
        $port = $port ?: '3306';
        $database = $database ?: 'ecoride';
        $username = $username ?: 'ecorider';
        $password = $password ?: 'securepass';

        $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";

        try {
            $this->mysqlConnection = new PDO(
                $dsn,
                $username,
                $password,
                [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            // Log l'erreur avec plus de détails pour le débogage
            error_log("Erreur de connexion MySQL: " . $e->getMessage());
            error_log("DSN utilisé: mysql:host=$host;port=$port;dbname=$database");
            error_log("Variables disponibles - JAWSDB_URL: " . (getenv('JAWSDB_URL') ? 'définie' : 'non définie'));
            throw new PDOException("Impossible de se connecter à la base de données MySQL");
        }
    }

    /**
     * Ferme les connexions aux bases de données
     *
     * @return void
     */
    public function closeConnections(): void
    {
        $this->mysqlConnection = null;
    }
}
