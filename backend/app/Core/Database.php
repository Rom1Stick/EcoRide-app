<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Classe Database
 *
 * Cette classe gère les connexions aux bases de données MySQL et SQLite
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
     * Connexion PDO à SQLite
     *
     * @var PDO|null
     */
    private ?PDO $sqliteConnection = null;

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
     * Obtient une connexion à la base de données SQLite
     *
     * @return PDO
     */
    public function getSqliteConnection(): PDO
    {
        if ($this->sqliteConnection === null) {
            $this->connectToSqlite();
        }

        return $this->sqliteConnection;
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
     * Établit une connexion à la base de données SQLite
     *
     * @return void
     * @throws PDOException Si la connexion échoue
     */
    private function connectToSqlite(): void
    {
        $sqlitePath = env('SQLITE_PATH', 'storage/data.sqlite');
        $fullPath = BASE_PATH . '/' . $sqlitePath;

        // Créer le répertoire parent s'il n'existe pas
        $directory = dirname($fullPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        try {
            // Vérifier si le fichier SQLite existe ou le créer s'il n'existe pas
            if (!file_exists($fullPath)) {
                $this->createSqliteFile($fullPath);
            }

            $this->sqliteConnection = new PDO(
                "sqlite:$fullPath",
                null,
                null,
                [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );

            // Activer les contraintes de clé étrangère pour SQLite
            $this->sqliteConnection->exec('PRAGMA foreign_keys = ON;');
        } catch (PDOException $e) {
            error_log("Erreur de connexion SQLite: " . $e->getMessage());
            throw new PDOException("Impossible de se connecter à la base de données SQLite");
        }
    }

    /**
     * Crée un nouveau fichier SQLite avec la structure initiale
     *
     * @param  string $path Chemin complet du fichier SQLite
     * @return void
     */
    private function createSqliteFile(string $path): void
    {
        try {
            // Créer une connexion temporaire pour initialiser le fichier
            $tempConnection = new PDO("sqlite:$path");
            $tempConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Créer des tables de base pour le stockage temporaire (logs, cache, etc.)
            $tempConnection->exec(
                '
                CREATE TABLE IF NOT EXISTS logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    level TEXT NOT NULL,
                    message TEXT NOT NULL,
                    context TEXT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );
                
                CREATE TABLE IF NOT EXISTS cache (
                    key TEXT PRIMARY KEY,
                    value TEXT NOT NULL,
                    expires_at TIMESTAMP DEFAULT NULL
                );
                
                CREATE TABLE IF NOT EXISTS stats (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    value NUMERIC NOT NULL,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );
            '
            );

            // Fermer la connexion temporaire
            $tempConnection = null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la création du fichier SQLite: " . $e->getMessage());
            throw new PDOException("Impossible de créer le fichier SQLite");
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
        $this->sqliteConnection = null;
    }
}
