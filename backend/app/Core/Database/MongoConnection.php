<?php

namespace App\Core\Database;

use App\Core\Exceptions\ConnectionException;
use MongoDB\Client;
use MongoDB\Database;
use Exception;

/**
 * Gestionnaire de connexion à la base de données MongoDB
 * Implémente le pattern Singleton pour optimiser les ressources
 */
class MongoConnection
{
    private static ?MongoConnection $instance = null;
    private ?Client $client = null;
    private ?Database $database = null;
    
    /**
     * Configuration de la connexion à partir des variables d'environnement
     */
    private function __construct()
    {
        $host = getenv('MONGO_HOST') ?: 'localhost';
        $port = getenv('MONGO_PORT') ?: '27017';
        $username = getenv('MONGO_USER') ?: 'mongo';
        $password = getenv('MONGO_PASSWORD') ?: 'changeme';
        $dbname = getenv('MONGO_DBNAME') ?: 'ecoride_nosql';
        $authDb = getenv('MONGO_AUTH_DB') ?: 'admin';
        
        try {
            $uri = "mongodb://{$username}:{$password}@{$host}:{$port}/{$dbname}?authSource={$authDb}";
            $options = [
                'serverSelectionTimeoutMS' => 5000, // Timeout de 5 secondes
                'connectTimeoutMS' => 10000,        // Timeout de connexion
            ];
            
            $this->client = new Client($uri, $options);
            $this->database = $this->client->selectDatabase($dbname);
            
            // Vérification de la connexion
            $this->database->command(['ping' => 1]);
        } catch (Exception $e) {
            throw new ConnectionException('MONGODB', 'Cannot connect to MongoDB: ' . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Empêche le clonage de l'instance (pattern Singleton)
     */
    private function __clone() {}
    
    /**
     * Récupère l'instance unique de connexion
     *
     * @return MongoConnection
     */
    public static function getInstance(): MongoConnection
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Récupère le client MongoDB
     *
     * @return Client
     * @throws ConnectionException Si la connexion n'est pas établie
     */
    public function getClient(): Client
    {
        if ($this->client === null) {
            throw new ConnectionException('MONGODB', 'MongoDB client is not established');
        }
        
        return $this->client;
    }
    
    /**
     * Récupère la base de données MongoDB
     *
     * @return Database
     * @throws ConnectionException Si la connexion n'est pas établie
     */
    public function getDatabase(): Database
    {
        if ($this->database === null) {
            throw new ConnectionException('MONGODB', 'MongoDB database is not established');
        }
        
        return $this->database;
    }
    
    /**
     * Récupère une collection spécifique
     *
     * @param string $collectionName Nom de la collection
     * @return \MongoDB\Collection
     */
    public function getCollection(string $collectionName): \MongoDB\Collection
    {
        return $this->getDatabase()->selectCollection($collectionName);
    }
} 