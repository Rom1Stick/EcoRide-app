<?php

namespace App\DataAccess\NoSql\Service;

use App\DataAccess\Exception\DataAccessException;
use App\DataAccess\NoSql\Model\Review;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Client;
use \DateTime;

/**
 * Service pour la gestion des avis utilisateurs dans MongoDB
 */
class ReviewService
{
    /**
     * @var Collection Collection MongoDB
     */
    protected $collection;

    /**
     * @var string Nom de la collection
     */
    protected $collectionName = 'reviews';

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->initService();
        $this->ensureIndexes();
    }

    /**
     * Initialise le service en se connectant à MongoDB
     */
    protected function initService(): void
    {
        try {
            $host = getenv('MONGO_HOST') ?: 'mongodb';
            $port = getenv('MONGO_PORT') ?: '27017';
            $username = getenv('MONGO_USERNAME') ?: 'mongo';
            $password = getenv('MONGO_PASSWORD') ?: 'changeme';
            $dbName = getenv('MONGO_DATABASE') ?: 'ecoride_nosql';

            $uri = "mongodb://{$username}:{$password}@{$host}:{$port}";
            $client = new Client($uri);
            $database = $client->selectDatabase($dbName);
            $this->collection = $database->selectCollection($this->collectionName);
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur de connexion à MongoDB: " . $e->getMessage());
        }
    }

    /**
     * Assure que les index nécessaires sont créés
     */
    protected function ensureIndexes(): void
    {
        try {
            // Index sur l'utilisateur cible (pour rechercher les avis reçus)
            $this->collection->createIndex(['targetUserId' => 1]);
            
            // Index sur l'utilisateur qui a laissé l'avis
            $this->collection->createIndex(['userId' => 1]);
            
            // Index sur le covoiturage
            $this->collection->createIndex(['covoiturageId' => 1]);
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la création des index: " . $e->getMessage());
        }
    }

    /**
     * Insère un nouvel avis
     * 
     * @param array $data Données de l'avis
     * @return ObjectId ID de l'avis inséré
     * @throws DataAccessException
     */
    public function insert(array $data): ObjectId
    {
        try {
            // Conversion de la date au format MongoDB
            if (isset($data['createdAt']) && !($data['createdAt'] instanceof UTCDateTime)) {
                try {
                    $date = new DateTime($data['createdAt']);
                    $data['createdAt'] = new UTCDateTime($date->getTimestamp() * 1000);
                } catch (\Exception $e) {
                    // Si la date est invalide, utiliser la date actuelle
                    $data['createdAt'] = new UTCDateTime();
                }
            } else {
                $data['createdAt'] = new UTCDateTime();
            }

            $result = $this->collection->insertOne($data);

            if ($result->getInsertedCount() > 0) {
                return $result->getInsertedId();
            }

            throw new DataAccessException("Erreur lors de l'insertion de l'avis");
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de l'insertion: " . $e->getMessage());
        }
    }

    /**
     * Trouve un avis par son ID
     * 
     * @param string $id
     * @return Review|null
     * @throws DataAccessException
     */
    public function findById(string $id): ?Review
    {
        try {
            $document = $this->collection->findOne(['_id' => new ObjectId($id)]);

            if (!$document) {
                return null;
            }

            return Review::fromDocument($document);
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la recherche: " . $e->getMessage());
        }
    }

    /**
     * Met à jour un avis
     * 
     * @param string $id
     * @param array $data
     * @return bool
     * @throws DataAccessException
     */
    public function update(string $id, array $data): bool
    {
        try {
            // Ajouter la date de mise à jour
            $data['updatedAt'] = new UTCDateTime();

            $result = $this->collection->updateOne(
                ['_id' => new ObjectId($id)],
                ['$set' => $data]
            );

            return $result->getModifiedCount() > 0;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la mise à jour: " . $e->getMessage());
        }
    }

    /**
     * Supprime un avis
     * 
     * @param string $id
     * @return bool
     * @throws DataAccessException
     */
    public function delete(string $id): bool
    {
        try {
            $result = $this->collection->deleteOne(['_id' => new ObjectId($id)]);
            return $result->getDeletedCount() > 0;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la suppression: " . $e->getMessage());
        }
    }

    /**
     * Trouve tous les avis pour un utilisateur cible
     * 
     * @param int $targetUserId
     * @return array
     * @throws DataAccessException
     */
    public function findByTargetUserId(int $targetUserId): array
    {
        try {
            $cursor = $this->collection->find(['targetUserId' => $targetUserId]);
            $reviews = [];

            foreach ($cursor as $document) {
                $reviews[] = Review::fromDocument($document);
            }

            return $reviews;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la recherche des avis: " . $e->getMessage());
        }
    }

    /**
     * Calcule la note moyenne d'un utilisateur
     * 
     * @param int $targetUserId
     * @return float|null
     * @throws DataAccessException
     */
    public function calculateAverageRating(int $targetUserId): ?float
    {
        try {
            $pipeline = [
                ['$match' => ['targetUserId' => $targetUserId]],
                ['$group' => [
                    '_id' => null,
                    'averageRating' => ['$avg' => '$rating'],
                    'count' => ['$sum' => 1]
                ]]
            ];

            $result = $this->collection->aggregate($pipeline)->toArray();

            if (empty($result)) {
                return null;
            }

            return $result[0]->averageRating;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors du calcul de la note moyenne: " . $e->getMessage());
        }
    }
} 