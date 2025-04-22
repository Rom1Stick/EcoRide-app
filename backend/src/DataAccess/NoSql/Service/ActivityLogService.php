<?php

namespace App\DataAccess\NoSql\Service;

use App\DataAccess\NoSql\Model\ActivityLog;
use App\DataAccess\NoSql\MongoConnection;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoDB\Driver\Exception\Exception as MongoException;
use App\DataAccess\Exception\DataAccessException;

/**
 * Service de gestion des journaux d'activité
 */
class ActivityLogService extends AbstractMongoService implements MongoServiceInterface
{
    /**
     * Nom de la collection
     *
     * @var string
     */
    protected string $collectionName = 'activity_logs';

    /**
     * Constructeur
     *
     * @param MongoConnection $connection Connexion MongoDB
     */
    public function __construct(MongoConnection $connection)
    {
        parent::__construct($connection);
    }

    /**
     * Initialise le service
     * 
     * @return void
     */
    protected function initService(): void
    {
        // Création des indices recommandés pour optimiser les performances
        $this->createRecommendedIndexes();
    }

    /**
     * Crée un nouveau journal d'activité
     *
     * @param ActivityLog $log Journal d'activité
     * @return string ID du journal créé
     * @throws DataAccessException En cas d'erreur
     */
    public function create(ActivityLog $log): string
    {
        try {
            $data = $log->toArray();
            unset($data['_id']); // MongoDB génère automatiquement l'ID

            $result = $this->collection->insertOne($data);
            
            if (!$result->getInsertedId()) {
                throw new DataAccessException("Échec de la création du journal d'activité");
            }
            
            return (string)$result->getInsertedId();
        } catch (MongoException $e) {
            throw new DataAccessException("Erreur lors de la création du journal d'activité: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Récupère un journal d'activité par son ID
     *
     * @param string $id ID du journal
     * @return ActivityLog|null Journal d'activité ou null si non trouvé
     * @throws DataAccessException En cas d'erreur
     */
    public function getById(string $id): ?ActivityLog
    {
        try {
            $result = $this->collection->findOne(['_id' => new ObjectId($id)]);
            
            if (!$result) {
                return null;
            }
            
            return ActivityLog::fromArray((array)$result);
        } catch (MongoException $e) {
            throw new DataAccessException("Erreur lors de la récupération du journal d'activité: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Récupère les journaux d'activité par ID utilisateur
     *
     * @param int $userId ID utilisateur
     * @param int $limit Limite de résultats
     * @param int $offset Position de départ
     * @return array Journaux d'activité
     * @throws DataAccessException En cas d'erreur
     */
    public function getByUserId(int $userId, int $limit = 20, int $offset = 0): array
    {
        return $this->findWithCriteria(['userId' => $userId], ['timestamp' => -1], $limit, $offset);
    }

    /**
     * Récupère les journaux d'activité par ID trajet
     *
     * @param int $tripId ID trajet
     * @param int $limit Limite de résultats
     * @param int $offset Position de départ
     * @return array Journaux d'activité
     * @throws DataAccessException En cas d'erreur
     */
    public function getByTripId(int $tripId, int $limit = 20, int $offset = 0): array
    {
        return $this->findWithCriteria(['tripId' => $tripId], ['timestamp' => -1], $limit, $offset);
    }

    /**
     * Récupère les journaux d'activité par ID réservation
     *
     * @param int $bookingId ID réservation
     * @param int $limit Limite de résultats
     * @param int $offset Position de départ
     * @return array Journaux d'activité
     * @throws DataAccessException En cas d'erreur
     */
    public function getByBookingId(int $bookingId, int $limit = 20, int $offset = 0): array
    {
        return $this->findWithCriteria(['bookingId' => $bookingId], ['timestamp' => -1], $limit, $offset);
    }

    /**
     * Récupère les journaux d'activité par type d'événement
     *
     * @param string $eventType Type d'événement
     * @param int $limit Limite de résultats
     * @param int $offset Position de départ
     * @return array Journaux d'activité
     * @throws DataAccessException En cas d'erreur
     */
    public function getByEventType(string $eventType, int $limit = 20, int $offset = 0): array
    {
        return $this->findWithCriteria(['eventType' => $eventType], ['timestamp' => -1], $limit, $offset);
    }

    /**
     * Récupère les journaux d'activité par niveau de sévérité
     *
     * @param string $level Niveau de sévérité
     * @param int $limit Limite de résultats
     * @param int $offset Position de départ
     * @return array Journaux d'activité
     * @throws DataAccessException En cas d'erreur
     */
    public function getByLevel(string $level, int $limit = 20, int $offset = 0): array
    {
        return $this->findWithCriteria(['level' => $level], ['timestamp' => -1], $limit, $offset);
    }

    /**
     * Récupère les journaux d'activité par plage de dates
     *
     * @param \DateTime $startDate Date de début
     * @param \DateTime $endDate Date de fin
     * @param int $limit Limite de résultats
     * @param int $offset Position de départ
     * @return array Journaux d'activité
     * @throws DataAccessException En cas d'erreur
     */
    public function getByDateRange(\DateTime $startDate, \DateTime $endDate, int $limit = 100, int $offset = 0): array
    {
        try {
            $criteria = [
                'timestamp' => [
                    '$gte' => $startDate->format('c'),
                    '$lte' => $endDate->format('c')
                ]
            ];
            
            return $this->findWithCriteria($criteria, ['timestamp' => -1], $limit, $offset);
        } catch (MongoException $e) {
            throw new DataAccessException("Erreur lors de la récupération des journaux d'activité par plage de dates: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Récupère les journaux d'activité pour une source spécifique
     *
     * @param string $source Source
     * @param int $limit Limite de résultats
     * @param int $offset Position de départ
     * @return array Journaux d'activité
     * @throws DataAccessException En cas d'erreur
     */
    public function getBySource(string $source, int $limit = 20, int $offset = 0): array
    {
        return $this->findWithCriteria(['source' => $source], ['timestamp' => -1], $limit, $offset);
    }

    /**
     * Compte le nombre de journaux d'activité correspondant aux critères
     *
     * @param array $criteria Critères de recherche
     * @return int Nombre de journaux
     * @throws DataAccessException En cas d'erreur
     */
    public function countByCriteria(array $criteria): int
    {
        try {
            return $this->collection->countDocuments($criteria);
        } catch (MongoException $e) {
            throw new DataAccessException("Erreur lors du comptage des journaux d'activité: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Supprime les journaux d'activité antérieurs à une date
     *
     * @param \DateTime $date Date limite
     * @return int Nombre de journaux supprimés
     * @throws DataAccessException En cas d'erreur
     */
    public function deleteOlderThan(\DateTime $date): int
    {
        try {
            $result = $this->collection->deleteMany([
                'timestamp' => ['$lt' => $date->format('c')]
            ]);
            
            return $result->getDeletedCount();
        } catch (MongoException $e) {
            throw new DataAccessException("Erreur lors de la suppression des anciens journaux d'activité: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Trouve des journaux d'activité selon les critères
     *
     * @param array $criteria Critères de recherche
     * @param array $sort Tri
     * @param int $limit Limite
     * @param int $offset Offset
     * @return array Journaux d'activité
     * @throws DataAccessException En cas d'erreur
     */
    private function findWithCriteria(array $criteria, array $sort = [], int $limit = 20, int $offset = 0): array
    {
        try {
            $options = [
                'sort' => $sort,
                'limit' => $limit,
                'skip' => $offset
            ];
            
            $cursor = $this->collection->find($criteria, $options);
            $logs = [];
            
            foreach ($cursor as $document) {
                $logs[] = ActivityLog::fromArray((array)$document);
            }
            
            return $logs;
        } catch (MongoException $e) {
            throw new DataAccessException("Erreur lors de la recherche de journaux d'activité: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Recherche avancée de journaux d'activité
     *
     * @param array $criteria Critères de base
     * @param array $textSearch Texte à rechercher (dans le message)
     * @param array $sort Tri
     * @param int $limit Limite
     * @param int $offset Offset
     * @return array Journaux d'activité
     * @throws DataAccessException En cas d'erreur
     */
    public function advancedSearch(
        array $criteria = [],
        ?string $textSearch = null,
        array $sort = ['timestamp' => -1],
        int $limit = 50,
        int $offset = 0
    ): array {
        try {
            $searchCriteria = $criteria;
            
            // Ajout de la recherche textuelle si spécifiée
            if ($textSearch) {
                $searchCriteria['$text'] = ['$search' => $textSearch];
            }
            
            $options = [
                'sort' => $sort,
                'limit' => $limit,
                'skip' => $offset
            ];
            
            $cursor = $this->collection->find($searchCriteria, $options);
            $logs = [];
            
            foreach ($cursor as $document) {
                $logs[] = ActivityLog::fromArray((array)$document);
            }
            
            return $logs;
        } catch (MongoException $e) {
            throw new DataAccessException("Erreur lors de la recherche avancée de journaux d'activité: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Crée un index sur la collection
     *
     * @param array $keys Clés de l'index
     * @param array $options Options de l'index
     * @return string Nom de l'index créé
     * @throws DataAccessException En cas d'erreur
     */
    public function createIndex(array $keys, array $options = []): string
    {
        try {
            return $this->collection->createIndex($keys, $options);
        } catch (MongoException $e) {
            throw new DataAccessException("Erreur lors de la création de l'index: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Crée les indices recommandés pour la collection
     *
     * @return array Noms des indices créés
     * @throws DataAccessException En cas d'erreur
     */
    public function createRecommendedIndexes(): array
    {
        $indexes = [];
        
        // Index sur timestamp (pour les requêtes par plage de dates)
        $indexes[] = $this->createIndex(['timestamp' => -1]);
        
        // Index sur userId (pour les requêtes par utilisateur)
        $indexes[] = $this->createIndex(['userId' => 1, 'timestamp' => -1]);
        
        // Index sur tripId (pour les requêtes par trajet)
        $indexes[] = $this->createIndex(['tripId' => 1, 'timestamp' => -1]);
        
        // Index sur eventType (pour les requêtes par type d'événement)
        $indexes[] = $this->createIndex(['eventType' => 1, 'timestamp' => -1]);
        
        // Index sur level (pour les requêtes par niveau)
        $indexes[] = $this->createIndex(['level' => 1, 'timestamp' => -1]);
        
        // Index textuel sur le message pour la recherche
        $indexes[] = $this->createIndex(
            ['message' => 'text'],
            ['name' => 'message_text']
        );
        
        return $indexes;
    }
} 