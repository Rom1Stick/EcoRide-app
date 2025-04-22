<?php

namespace App\DataAccess\NoSql;

use MongoDB\Client;
use MongoDB\Database;
use MongoDB\Collection;
use App\DataAccess\Exception\DataAccessException;

/**
 * Classe MongoConnection
 * 
 * Gère la connexion à la base de données MongoDB
 */
class MongoConnection
{
    /**
     * Client MongoDB
     * 
     * @var Client|null
     */
    private ?Client $client = null;

    /**
     * Nom de la base de données
     * 
     * @var string
     */
    private string $databaseName;

    /**
     * URI de connexion MongoDB
     * 
     * @var string
     */
    private string $uri;

    /**
     * Options de connexion
     * 
     * @var array
     */
    private array $options;

    /**
     * Constructeur
     * 
     * @param string|null $uri URI de connexion MongoDB (si null, utilise la variable d'environnement)
     * @param string|null $databaseName Nom de la base de données (si null, utilise la variable d'environnement)
     * @param array $options Options de connexion
     */
    public function __construct(?string $uri = null, ?string $databaseName = null, array $options = [])
    {
        $this->uri = $uri ?? $this->getDefaultUri();
        $this->databaseName = $databaseName ?? $this->getDefaultDatabaseName();
        $this->options = $options;
    }

    /**
     * Récupère l'URI par défaut à partir des variables d'environnement
     * 
     * @return string URI de connexion MongoDB
     */
    private function getDefaultUri(): string
    {
        $uri = getenv('NOSQL_URI');
        if (!$uri) {
            $mongoUsername = getenv('MONGO_USERNAME') ?: 'mongo';
            $mongoPassword = getenv('MONGO_PASSWORD') ?: 'changeme';
            $mongoHost = getenv('MONGO_HOST') ?: 'mongodb';
            $mongoPort = getenv('MONGO_PORT') ?: '27017';
            
            $uri = "mongodb://{$mongoUsername}:{$mongoPassword}@{$mongoHost}:{$mongoPort}";
        }
        
        return $uri;
    }

    /**
     * Récupère le nom de la base de données par défaut à partir des variables d'environnement
     * 
     * @return string Nom de la base de données
     */
    private function getDefaultDatabaseName(): string
    {
        return getenv('MONGO_DATABASE') ?: 'ecoride_nosql';
    }

    /**
     * Obtient le client MongoDB
     * 
     * @return Client Client MongoDB
     * @throws DataAccessException En cas d'erreur de connexion
     */
    public function getClient(): Client
    {
        if ($this->client === null) {
            try {
                $this->client = new Client($this->uri, $this->options);
            } catch (\Exception $e) {
                throw new DataAccessException(
                    "Erreur de connexion à MongoDB : " . $e->getMessage(),
                    0,
                    $e,
                    "NoSQL"
                );
            }
        }
        
        return $this->client;
    }

    /**
     * Obtient une instance de la base de données MongoDB
     * 
     * @return Database Instance de la base de données
     * @throws DataAccessException En cas d'erreur de connexion
     */
    public function getDatabase(): Database
    {
        try {
            return $this->getClient()->selectDatabase($this->databaseName);
        } catch (\Exception $e) {
            throw new DataAccessException(
                "Erreur d'accès à la base de données MongoDB '{$this->databaseName}' : " . $e->getMessage(),
                0,
                $e,
                "NoSQL"
            );
        }
    }

    /**
     * Obtient une collection MongoDB
     * 
     * @param string $collectionName Nom de la collection
     * @return Collection Collection MongoDB
     * @throws DataAccessException En cas d'erreur d'accès à la collection
     */
    public function getCollection(string $collectionName): Collection
    {
        try {
            return $this->getDatabase()->selectCollection($collectionName);
        } catch (\Exception $e) {
            throw new DataAccessException(
                "Erreur d'accès à la collection MongoDB '{$collectionName}' : " . $e->getMessage(),
                0,
                $e,
                "NoSQL"
            );
        }
    }

    /**
     * Vérifie si la connexion à MongoDB est active
     * 
     * @return bool True si la connexion est active, False sinon
     */
    public function isConnected(): bool
    {
        if ($this->client === null) {
            return false;
        }
        
        try {
            // Exécute une commande ping pour vérifier la connexion
            $this->client->selectDatabase('admin')->command(['ping' => 1]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Ferme la connexion à MongoDB
     * 
     * @return void
     */
    public function close(): void
    {
        $this->client = null;
    }
} 