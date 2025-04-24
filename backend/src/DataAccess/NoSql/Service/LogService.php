<?php

namespace App\DataAccess\NoSql\Service;

use App\DataAccess\Exception\DataAccessException;
use App\DataAccess\NoSql\Model\Log;
use App\DataAccess\NoSql\MongoServiceInterface;
use DateTime;
use Exception;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;

/**
 * Service pour gérer les logs d'application dans MongoDB
 */
class LogService extends AbstractMongoService
{
    /**
     * Nom de la collection
     */
    private const COLLECTION_NAME = 'logs';
    
    /**
     * Niveaux de log valides
     */
    public const LEVEL_INFO = 'info';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';
    public const LEVEL_DEBUG = 'debug';
    
    /**
     * Collection MongoDB
     *
     * @var Collection
     */
    protected Collection $collection;
    
    /**
     * Constructeur
     *
     * @param MongoServiceInterface $mongoConnection
     */
    public function __construct(MongoServiceInterface $mongoConnection)
    {
        parent::__construct($mongoConnection);
    }
    
    /**
     * Initialiser le service
     */
    protected function initService(): void
    {
        $this->collection = $this->getCollection(self::COLLECTION_NAME);
        $this->ensureIndexes();
    }
    
    /**
     * Assurer la création des index nécessaires
     */
    private function ensureIndexes(): void
    {
        // Index TTL pour supprimer automatiquement les logs après 30 jours
        $this->collection->createIndex(
            ['timestamp' => 1],
            [
                'expireAfterSeconds' => 30 * 24 * 60 * 60, // 30 jours
                'background' => true
            ]
        );
        
        // Index sur le niveau pour filtrer par niveau de sévérité
        $this->collection->createIndex(['niveau' => 1]);
        
        // Index sur le service pour filtrer par service
        $this->collection->createIndex(['service' => 1]);
    }
    
    /**
     * Enregistrer un log
     *
     * @param Log $log
     * @return Log
     * @throws DataAccessException
     */
    public function save(Log $log): Log
    {
        try {
            $result = $this->collection->insertOne($log->jsonSerialize());
            
            if ($result->getInsertedCount() === 0) {
                throw new DataAccessException("Échec de l'insertion du log");
            }
            
            $log->setId((string)$result->getInsertedId());
            return $log;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de l'enregistrement du log: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Créer et enregistrer un log rapidement
     *
     * @param string $message Message à logger
     * @param string $level Niveau de log
     * @param string $service Service concerné
     * @param array $meta Métadonnées supplémentaires
     * @return Log
     * @throws DataAccessException
     */
    public function log(string $message, string $level = self::LEVEL_INFO, string $service = '', array $meta = []): Log
    {
        $log = new Log($message, $level, $service, $meta);
        return $this->save($log);
    }
    
    /**
     * Enregistrer un log de niveau info
     *
     * @param string $message
     * @param string $service
     * @param array $meta
     * @return Log
     * @throws DataAccessException
     */
    public function info(string $message, string $service = '', array $meta = []): Log
    {
        return $this->log($message, self::LEVEL_INFO, $service, $meta);
    }
    
    /**
     * Enregistrer un log de niveau warning
     *
     * @param string $message
     * @param string $service
     * @param array $meta
     * @return Log
     * @throws DataAccessException
     */
    public function warning(string $message, string $service = '', array $meta = []): Log
    {
        return $this->log($message, self::LEVEL_WARNING, $service, $meta);
    }
    
    /**
     * Enregistrer un log de niveau error
     *
     * @param string $message
     * @param string $service
     * @param array $meta
     * @return Log
     * @throws DataAccessException
     */
    public function error(string $message, string $service = '', array $meta = []): Log
    {
        return $this->log($message, self::LEVEL_ERROR, $service, $meta);
    }
    
    /**
     * Enregistrer un log de niveau debug
     *
     * @param string $message
     * @param string $service
     * @param array $meta
     * @return Log
     * @throws DataAccessException
     */
    public function debug(string $message, string $service = '', array $meta = []): Log
    {
        return $this->log($message, self::LEVEL_DEBUG, $service, $meta);
    }
    
    /**
     * Trouver un log par son ID
     *
     * @param mixed $id
     * @return Log|null
     * @throws DataAccessException
     */
    public function findById($id)
    {
        try {
            $result = $this->collection->findOne(['_id' => new ObjectId($id)]);
            
            if ($result === null) {
                return null;
            }
            
            return Log::fromArray((array)$result);
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la recherche du log: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Rechercher des logs par niveau
     *
     * @param string $level
     * @param int $limit
     * @param int $skip
     * @return array
     * @throws DataAccessException
     */
    public function findByLevel(string $level, int $limit = 50, int $skip = 0): array
    {
        try {
            $cursor = $this->collection->find(
                ['niveau' => $level],
                [
                    'sort' => ['timestamp' => -1],
                    'limit' => $limit,
                    'skip' => $skip
                ]
            );
            
            $logs = [];
            foreach ($cursor as $document) {
                $logs[] = Log::fromArray((array)$document);
            }
            
            return $logs;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la recherche des logs par niveau: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Rechercher des logs par service
     *
     * @param string $service
     * @param int $limit
     * @param int $skip
     * @return array
     * @throws DataAccessException
     */
    public function findByService(string $service, int $limit = 50, int $skip = 0): array
    {
        try {
            $cursor = $this->collection->find(
                ['service' => $service],
                [
                    'sort' => ['timestamp' => -1],
                    'limit' => $limit,
                    'skip' => $skip
                ]
            );
            
            $logs = [];
            foreach ($cursor as $document) {
                $logs[] = Log::fromArray((array)$document);
            }
            
            return $logs;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la recherche des logs par service: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Rechercher des logs par période
     *
     * @param DateTime $from
     * @param DateTime $to
     * @param int $limit
     * @param int $skip
     * @return array
     * @throws DataAccessException
     */
    public function findByPeriod(DateTime $from, DateTime $to, int $limit = 50, int $skip = 0): array
    {
        try {
            $cursor = $this->collection->find(
                [
                    'timestamp' => [
                        '$gte' => $from->format('Y-m-d H:i:s'),
                        '$lte' => $to->format('Y-m-d H:i:s')
                    ]
                ],
                [
                    'sort' => ['timestamp' => -1],
                    'limit' => $limit,
                    'skip' => $skip
                ]
            );
            
            $logs = [];
            foreach ($cursor as $document) {
                $logs[] = Log::fromArray((array)$document);
            }
            
            return $logs;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la recherche des logs par période: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Recherche avancée de logs avec filtres
     *
     * @param array $filters Filtres (level, service, dateFrom, dateTo, message, userId)
     * @param int $limit
     * @param int $skip
     * @return array
     * @throws DataAccessException
     */
    public function search(array $filters = [], int $limit = 50, int $skip = 0): array
    {
        try {
            $query = [];
            
            if (isset($filters['level']) && !empty($filters['level'])) {
                $query['niveau'] = $filters['level'];
            }
            
            if (isset($filters['service']) && !empty($filters['service'])) {
                $query['service'] = $filters['service'];
            }
            
            if (isset($filters['message']) && !empty($filters['message'])) {
                $query['message'] = ['$regex' => $filters['message'], '$options' => 'i'];
            }
            
            if (isset($filters['userId']) && !empty($filters['userId'])) {
                $query['meta.userId'] = intval($filters['userId']);
            }
            
            if (isset($filters['dateFrom']) || isset($filters['dateTo'])) {
                $query['timestamp'] = [];
                
                if (isset($filters['dateFrom']) && !empty($filters['dateFrom'])) {
                    $query['timestamp']['$gte'] = $filters['dateFrom'];
                }
                
                if (isset($filters['dateTo']) && !empty($filters['dateTo'])) {
                    $query['timestamp']['$lte'] = $filters['dateTo'];
                }
            }
            
            $cursor = $this->collection->find(
                $query,
                [
                    'sort' => ['timestamp' => -1],
                    'limit' => $limit,
                    'skip' => $skip
                ]
            );
            
            $logs = [];
            foreach ($cursor as $document) {
                $logs[] = Log::fromArray((array)$document);
            }
            
            return $logs;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la recherche des logs: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Compter le nombre de logs par niveau
     *
     * @return array
     * @throws DataAccessException
     */
    public function countByLevel(): array
    {
        try {
            $result = $this->collection->aggregate([
                [
                    '$group' => [
                        '_id' => '$niveau',
                        'count' => ['$sum' => 1]
                    ]
                ]
            ])->toArray();
            
            $counts = [];
            foreach ($result as $item) {
                $counts[$item->_id] = $item->count;
            }
            
            return $counts;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors du comptage des logs: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Obtenir les derniers logs pour un tableau de bord
     *
     * @param int $limit
     * @return array
     * @throws DataAccessException
     */
    public function getLatestLogs(int $limit = 10): array
    {
        try {
            $cursor = $this->collection->find(
                [],
                [
                    'sort' => ['timestamp' => -1],
                    'limit' => $limit
                ]
            );
            
            $logs = [];
            foreach ($cursor as $document) {
                $logs[] = Log::fromArray((array)$document);
            }
            
            return $logs;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la récupération des derniers logs: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Supprimer un log par son ID
     *
     * @param mixed $id
     * @return bool
     * @throws DataAccessException
     */
    public function delete($id): bool
    {
        try {
            $result = $this->collection->deleteOne(['_id' => new ObjectId($id)]);
            return $result->getDeletedCount() > 0;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la suppression du log: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Vider les logs plus anciens qu'une date donnée
     *
     * @param DateTime $date
     * @return int Nombre de logs supprimés
     * @throws DataAccessException
     */
    public function purgeOlderThan(DateTime $date): int
    {
        try {
            $result = $this->collection->deleteMany([
                'timestamp' => ['$lt' => $date->format('Y-m-d H:i:s')]
            ]);
            
            return $result->getDeletedCount();
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors du nettoyage des logs: " . $e->getMessage(), 0, $e);
        }
    }
} 