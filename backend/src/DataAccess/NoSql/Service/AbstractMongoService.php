<?php

namespace App\DataAccess\NoSql\Service;

use MongoDB\BSON\ObjectId;
use MongoDB\Collection;
use MongoDB\Driver\Exception\Exception as MongoException;
use App\DataAccess\NoSql\MongoConnection;
use App\DataAccess\Exception\DataAccessException;

/**
 * Classe AbstractMongoService
 * 
 * Implémente les fonctionnalités communes à tous les services MongoDB
 */
abstract class AbstractMongoService implements MongoServiceInterface
{
    /**
     * Connexion MongoDB
     * 
     * @var MongoConnection
     */
    protected MongoConnection $connection;

    /**
     * Collection MongoDB
     * 
     * @var Collection
     */
    protected Collection $collection;

    /**
     * Nom de la collection
     * 
     * @var string
     */
    protected string $collectionName;

    /**
     * Constructeur
     * 
     * @param MongoConnection $connection Connexion MongoDB
     */
    public function __construct(MongoConnection $connection)
    {
        $this->connection = $connection;
        $this->collection = $this->connection->getCollection($this->collectionName);
        $this->initService();
    }

    /**
     * Initialise les propriétés du service
     * 
     * @return void
     */
    abstract protected function initService(): void;

    /**
     * {@inheritDoc}
     */
    public function findById($id)
    {
        try {
            $objectId = $this->toObjectId($id);
            $result = $this->collection->findOne(['_id' => $objectId]);
            
            return $result ? $this->formatDocument($result) : null;
        } catch (MongoException $e) {
            throw new DataAccessException(
                "Erreur lors de la récupération du document avec l'ID $id : " . $e->getMessage(),
                0,
                $e,
                "NoSQL"
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function insert(array $data): ObjectId
    {
        try {
            // Préparer les données pour l'insertion
            $data = $this->prepareData($data);
            
            // Ajout des métadonnées de timestamp
            if (!isset($data['createdAt'])) {
                $data['createdAt'] = new \MongoDB\BSON\UTCDateTime(time() * 1000);
            }
            
            $result = $this->collection->insertOne($data);
            
            if (!$result->getInsertedCount()) {
                throw new DataAccessException("Échec de l'insertion du document", 0, null, "NoSQL");
            }
            
            return $result->getInsertedId();
        } catch (MongoException $e) {
            throw new DataAccessException(
                "Erreur lors de l'insertion du document : " . $e->getMessage(),
                0,
                $e,
                "NoSQL"
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function update($id, array $data): bool
    {
        try {
            $objectId = $this->toObjectId($id);
            
            // Préparer les données pour la mise à jour
            $data = $this->prepareData($data);
            
            // Ajout des métadonnées de timestamp
            $data['updatedAt'] = new \MongoDB\BSON\UTCDateTime(time() * 1000);
            
            $result = $this->collection->updateOne(
                ['_id' => $objectId],
                ['$set' => $data]
            );
            
            return $result->getModifiedCount() > 0;
        } catch (MongoException $e) {
            throw new DataAccessException(
                "Erreur lors de la mise à jour du document avec l'ID $id : " . $e->getMessage(),
                0,
                $e,
                "NoSQL"
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function delete($id): bool
    {
        try {
            $objectId = $this->toObjectId($id);
            $result = $this->collection->deleteOne(['_id' => $objectId]);
            
            return $result->getDeletedCount() > 0;
        } catch (MongoException $e) {
            throw new DataAccessException(
                "Erreur lors de la suppression du document avec l'ID $id : " . $e->getMessage(),
                0,
                $e,
                "NoSQL"
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function find(array $criteria = [], array $options = []): array
    {
        try {
            $cursor = $this->collection->find($criteria, $options);
            
            $results = [];
            foreach ($cursor as $document) {
                $results[] = $this->formatDocument($document);
            }
            
            return $results;
        } catch (MongoException $e) {
            throw new DataAccessException(
                "Erreur lors de la recherche de documents : " . $e->getMessage(),
                0,
                $e,
                "NoSQL"
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function count(array $criteria = []): int
    {
        try {
            return $this->collection->countDocuments($criteria);
        } catch (MongoException $e) {
            throw new DataAccessException(
                "Erreur lors du comptage de documents : " . $e->getMessage(),
                0,
                $e,
                "NoSQL"
            );
        }
    }

    /**
     * Convertit un ID en ObjectId MongoDB
     * 
     * @param string|ObjectId $id ID à convertir
     * @return ObjectId ObjectId MongoDB
     */
    protected function toObjectId($id): ObjectId
    {
        if ($id instanceof ObjectId) {
            return $id;
        }
        
        if (is_string($id) && strlen($id) === 24 && ctype_xdigit($id)) {
            return new ObjectId($id);
        }
        
        throw new \InvalidArgumentException("L'ID n'est pas un ObjectId MongoDB valide");
    }

    /**
     * Prépare les données pour l'insertion ou la mise à jour
     * Peut être surchargée par les classes enfants pour ajouter une logique spécifique
     * 
     * @param array $data Données à préparer
     * @return array Données préparées
     */
    protected function prepareData(array $data): array
    {
        return $data;
    }

    /**
     * Formate un document pour la sortie
     * 
     * @param array|\MongoDB\Model\BSONDocument $document Document à formater
     * @return array Document formaté
     */
    protected function formatDocument($document): array
    {
        $result = [];
        foreach ($document as $key => $value) {
            if ($value instanceof ObjectId) {
                $result[$key] = (string)$value;
            } elseif ($value instanceof \MongoDB\BSON\UTCDateTime) {
                $result[$key] = $value->toDateTime()->format('Y-m-d H:i:s');
            } else {
                $result[$key] = $value;
            }
        }
        
        return $result;
    }
} 