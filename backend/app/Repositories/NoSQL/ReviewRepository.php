<?php

namespace App\Repositories\NoSQL;

use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\BSON\ObjectId;
use App\Models\Entities\Review;

/**
 * Repository pour la gestion des avis dans MongoDB
 */
class ReviewRepository
{
    private Collection $collection;
    
    /**
     * Constructeur
     *
     * @param string $uri URI de connexion MongoDB
     * @param string $database Nom de la base de données
     * @param string $collection Nom de la collection
     */
    public function __construct(string $uri, string $database = 'ecoride_nosql', string $collection = 'reviews')
    {
        $client = new Client($uri);
        $this->collection = $client->selectDatabase($database)->selectCollection($collection);
    }
    
    /**
     * Crée un nouvel avis
     *
     * @param array $reviewData Données de l'avis
     * @return string|null ID de l'avis créé ou null en cas d'échec
     */
    public function create(array $reviewData): ?string
    {
        try {
            // Valider les données minimales requises
            if (!isset($reviewData['user_id']) || !isset($reviewData['rating'])) {
                return null;
            }
            
            // Préparer le document à insérer
            $document = [
                'user_id' => $reviewData['user_id'],
                'rating' => (float) $reviewData['rating'],
                'comment' => $reviewData['comment'] ?? '',
                'created_at' => new \MongoDB\BSON\UTCDateTime(time() * 1000)
            ];
            
            // Ajouter les champs optionnels s'ils existent
            if (isset($reviewData['trip_id'])) {
                $document['trip_id'] = $reviewData['trip_id'];
            }
            
            if (isset($reviewData['target_user_id'])) {
                $document['target_user_id'] = $reviewData['target_user_id'];
            }
            
            $result = $this->collection->insertOne($document);
            
            if ($result->getInsertedCount() > 0) {
                return (string) $result->getInsertedId();
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Récupère un avis par son ID
     *
     * @param string $reviewId ID de l'avis
     * @return array|null Données de l'avis ou null si non trouvé
     */
    public function find(string $reviewId): ?array
    {
        try {
            $document = $this->collection->findOne(['_id' => new ObjectId($reviewId)]);
            
            if (!$document) {
                return null;
            }
            
            return $this->formatReviewDocument($document);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Met à jour un avis existant
     *
     * @param string $reviewId ID de l'avis
     * @param array $reviewData Nouvelles données de l'avis
     * @return bool Succès de l'opération
     */
    public function update(string $reviewId, array $reviewData): bool
    {
        try {
            $updateData = [
                'updated_at' => new \MongoDB\BSON\UTCDateTime(time() * 1000)
            ];
            
            // Ajouter seulement les champs à mettre à jour
            if (isset($reviewData['rating'])) {
                $updateData['rating'] = (float) $reviewData['rating'];
            }
            
            if (isset($reviewData['comment'])) {
                $updateData['comment'] = $reviewData['comment'];
            }
            
            $result = $this->collection->updateOne(
                ['_id' => new ObjectId($reviewId)],
                ['$set' => $updateData]
            );
            
            return $result->getModifiedCount() > 0 || $result->getMatchedCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Supprime un avis
     *
     * @param string $reviewId ID de l'avis
     * @return bool Succès de l'opération
     */
    public function delete(string $reviewId): bool
    {
        try {
            $result = $this->collection->deleteOne(['_id' => new ObjectId($reviewId)]);
            return $result->getDeletedCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Récupère les avis pour un utilisateur donné
     *
     * @param int $userId ID de l'utilisateur
     * @param int $page Numéro de page
     * @param int $limit Nombre d'avis par page
     * @return array Tableau d'avis pour l'utilisateur
     */
    public function findByUserId(int $userId, int $page = 1, int $limit = 10): array
    {
        try {
            $skip = ($page - 1) * $limit;
            
            $cursor = $this->collection->find(
                ['user_id' => $userId],
                [
                    'sort' => ['created_at' => -1],
                    'skip' => $skip,
                    'limit' => $limit
                ]
            );
            
            $reviews = [];
            foreach ($cursor as $document) {
                $reviews[] = $this->formatReviewDocument($document);
            }
            
            $totalCount = $this->collection->countDocuments(['user_id' => $userId]);
            
            return [
                'reviews' => $reviews,
                'total' => $totalCount,
                'pages' => ceil($totalCount / $limit),
                'current_page' => $page
            ];
        } catch (\Exception $e) {
            return [
                'reviews' => [],
                'total' => 0,
                'pages' => 0,
                'current_page' => $page
            ];
        }
    }
    
    /**
     * Récupère les avis pour un utilisateur cible
     *
     * @param int $targetUserId ID de l'utilisateur cible
     * @param int $page Numéro de page
     * @param int $limit Nombre d'avis par page
     * @return array Tableau d'avis pour l'utilisateur cible
     */
    public function findByTargetUserId(int $targetUserId, int $page = 1, int $limit = 10): array
    {
        try {
            $skip = ($page - 1) * $limit;
            
            $cursor = $this->collection->find(
                ['target_user_id' => $targetUserId],
                [
                    'sort' => ['created_at' => -1],
                    'skip' => $skip,
                    'limit' => $limit
                ]
            );
            
            $reviews = [];
            foreach ($cursor as $document) {
                $reviews[] = $this->formatReviewDocument($document);
            }
            
            $totalCount = $this->collection->countDocuments(['target_user_id' => $targetUserId]);
            
            return [
                'reviews' => $reviews,
                'total' => $totalCount,
                'pages' => ceil($totalCount / $limit),
                'current_page' => $page
            ];
        } catch (\Exception $e) {
            return [
                'reviews' => [],
                'total' => 0,
                'pages' => 0,
                'current_page' => $page
            ];
        }
    }
    
    /**
     * Récupère les avis pour un trajet
     *
     * @param int $tripId ID du trajet
     * @return array Tableau d'avis pour le trajet
     */
    public function findByTripId(int $tripId): array
    {
        try {
            $cursor = $this->collection->find(['trip_id' => $tripId]);
            
            $reviews = [];
            foreach ($cursor as $document) {
                $reviews[] = $this->formatReviewDocument($document);
            }
            
            return $reviews;
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Calcule la note moyenne pour un utilisateur
     *
     * @param int $userId ID de l'utilisateur cible
     * @return array|null Statistiques de notation ou null si aucun avis
     */
    public function getAverageRatingForUser(int $userId): ?array
    {
        try {
            $pipeline = [
                [
                    '$match' => ['target_user_id' => $userId]
                ],
                [
                    '$group' => [
                        '_id' => null,
                        'average' => ['$avg' => '$rating'],
                        'count' => ['$sum' => 1]
                    ]
                ]
            ];
            
            $result = $this->collection->aggregate($pipeline)->toArray();
            
            if (empty($result)) {
                return null;
            }
            
            return [
                'average' => round($result[0]['average'], 1),
                'count' => $result[0]['count']
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Récupère les avis récents avec pagination
     *
     * @param int $page Numéro de page
     * @param int $limit Nombre d'avis par page
     * @return array Tableau d'avis récents
     */
    public function getRecentWithPagination(int $page = 1, int $limit = 10): array
    {
        try {
            $skip = ($page - 1) * $limit;
            
            $cursor = $this->collection->find(
                [],
                [
                    'sort' => ['created_at' => -1],
                    'skip' => $skip,
                    'limit' => $limit
                ]
            );
            
            $reviews = [];
            foreach ($cursor as $document) {
                $reviews[] = $this->formatReviewDocument($document);
            }
            
            $totalCount = $this->collection->countDocuments([]);
            
            return [
                'reviews' => $reviews,
                'total' => $totalCount,
                'pages' => ceil($totalCount / $limit),
                'current_page' => $page
            ];
        } catch (\Exception $e) {
            return [
                'reviews' => [],
                'total' => 0,
                'pages' => 0,
                'current_page' => $page
            ];
        }
    }
    
    /**
     * Formate un document MongoDB en tableau associatif
     *
     * @param mixed $document Document MongoDB
     * @return array Document formaté
     */
    private function formatReviewDocument($document): array
    {
        $formattedDocument = [];
        
        foreach ($document as $key => $value) {
            if ($key === '_id') {
                $formattedDocument['id'] = (string) $value;
            } elseif ($value instanceof \MongoDB\BSON\UTCDateTime) {
                $formattedDocument[$key] = $value->toDateTime()->format('Y-m-d H:i:s');
            } else {
                $formattedDocument[$key] = $value;
            }
        }
        
        return $formattedDocument;
    }
} 