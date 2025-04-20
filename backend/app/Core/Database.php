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
        $host = env('DB_HOST', 'mysql');
        $port = env('DB_PORT', '3306');
        $database = env('DB_DATABASE', 'ecoride');
        $username = env('DB_USERNAME', 'ecorider');
        $password = env('DB_PASSWORD', 'securepass');

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
            // Log l'erreur mais ne pas exposer les détails sensibles
            error_log("Erreur de connexion MySQL: " . $e->getMessage());
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
