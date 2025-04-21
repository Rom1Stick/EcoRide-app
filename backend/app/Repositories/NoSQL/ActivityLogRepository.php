<?php

namespace App\Repositories\NoSQL;

use MongoDB\Client;
use MongoDB\Collection;

/**
 * Repository pour la gestion des logs d'activité utilisateur dans MongoDB
 */
class ActivityLogRepository
{
    private Collection $collection;
    
    /**
     * Constructeur
     *
     * @param string $uri URI de connexion MongoDB
     * @param string $database Nom de la base de données
     * @param string $collection Nom de la collection
     */
    public function __construct(string $uri, string $database = 'ecoride_nosql', string $collection = 'activity_logs')
    {
        $client = new Client($uri);
        $this->collection = $client->selectDatabase($database)->selectCollection($collection);
    }
    
    /**
     * Enregistre une activité utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param string $activityType Type d'activité
     * @param array $details Détails de l'activité
     * @return string|null ID du log créé ou null si échec
     */
    public function logActivity(int $userId, string $activityType, array $details = []): ?string
    {
        $document = [
            'user_id' => $userId,
            'activity_type' => $activityType,
            'details' => $details,
            'timestamp' => new \MongoDB\BSON\UTCDateTime(time() * 1000),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ];
        
        try {
            $result = $this->collection->insertOne($document);
            return (string) $result->getInsertedId();
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Récupère les activités récentes d'un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param int $limit Nombre maximum d'activités à retourner
     * @return array Liste des activités
     */
    public function findRecentByUserId(int $userId, int $limit = 10): array
    {
        $cursor = $this->collection->find(
            ['user_id' => $userId],
            [
                'sort' => ['timestamp' => -1],
                'limit' => $limit
            ]
        );
        
        $activities = [];
        foreach ($cursor as $document) {
            $activities[] = $this->formatActivity($document);
        }
        
        return $activities;
    }
    
    /**
     * Récupère toutes les activités d'un type spécifique pour un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param string $activityType Type d'activité
     * @param int $limit Nombre maximum d'activités à retourner
     * @return array Liste des activités
     */
    public function findByUserIdAndType(int $userId, string $activityType, int $limit = 50): array
    {
        $cursor = $this->collection->find(
            [
                'user_id' => $userId,
                'activity_type' => $activityType
            ],
            [
                'sort' => ['timestamp' => -1],
                'limit' => $limit
            ]
        );
        
        $activities = [];
        foreach ($cursor as $document) {
            $activities[] = $this->formatActivity($document);
        }
        
        return $activities;
    }
    
    /**
     * Récupère les activités pour une entité spécifique (ex: un trajet)
     *
     * @param string $entityType Type d'entité (trip, review, etc.)
     * @param int $entityId ID de l'entité
     * @param int $limit Nombre maximum d'activités à retourner
     * @return array Liste des activités
     */
    public function findByEntity(string $entityType, int $entityId, int $limit = 50): array
    {
        $cursor = $this->collection->find(
            [
                'details.' . $entityType . '_id' => $entityId
            ],
            [
                'sort' => ['timestamp' => -1],
                'limit' => $limit
            ]
        );
        
        $activities = [];
        foreach ($cursor as $document) {
            $activities[] = $this->formatActivity($document);
        }
        
        return $activities;
    }
    
    /**
     * Supprime les logs d'activité d'un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @return bool Succès de l'opération
     */
    public function deleteByUserId(int $userId): bool
    {
        try {
            $result = $this->collection->deleteMany(['user_id' => $userId]);
            return $result->getDeletedCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Récupère les tendances d'activité sur une période 
     *
     * @param int $days Nombre de jours à analyser
     * @return array Statistiques d'activité
     */
    public function getActivityTrends(int $days = 7): array
    {
        $startDate = new \MongoDB\BSON\UTCDateTime((time() - ($days * 86400)) * 1000);
        
        $pipeline = [
            [
                '$match' => [
                    'timestamp' => ['$gte' => $startDate]
                ]
            ],
            [
                '$group' => [
                    '_id' => [
                        'day' => ['$dayOfMonth' => '$timestamp'],
                        'month' => ['$month' => '$timestamp'],
                        'year' => ['$year' => '$timestamp'],
                        'activity_type' => '$activity_type'
                    ],
                    'count' => ['$sum' => 1]
                ]
            ],
            [
                '$sort' => [
                    '_id.year' => 1,
                    '_id.month' => 1,
                    '_id.day' => 1
                ]
            ]
        ];
        
        $cursor = $this->collection->aggregate($pipeline);
        
        $trends = [];
        foreach ($cursor as $document) {
            $date = sprintf(
                '%d-%02d-%02d',
                $document->_id['year'],
                $document->_id['month'],
                $document->_id['day']
            );
            
            $activityType = $document->_id['activity_type'];
            
            if (!isset($trends[$date])) {
                $trends[$date] = [];
            }
            
            $trends[$date][$activityType] = $document->count;
        }
        
        return $trends;
    }
    
    /**
     * Formate un document MongoDB en tableau pour l'API
     *
     * @param array $document Document MongoDB
     * @return array Document formaté
     */
    private function formatActivity(array $document): array
    {
        $formatted = [
            'id' => (string) $document['_id'],
            'user_id' => $document['user_id'],
            'activity_type' => $document['activity_type'],
            'details' => $document['details'] ?? []
        ];
        
        // Convertir la date MongoDB en format lisible
        if (isset($document['timestamp']) && $document['timestamp'] instanceof \MongoDB\BSON\UTCDateTime) {
            $formatted['timestamp'] = $document['timestamp']->toDateTime()->format('Y-m-d H:i:s');
        }
        
        return $formatted;
    }
} 