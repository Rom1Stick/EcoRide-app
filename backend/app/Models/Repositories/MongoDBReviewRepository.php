<?php

namespace App\Models\Repositories;

use App\Models\Entities\Review;
use MongoDB\Client;
use MongoDB\Collection;

/**
 * Repository pour les avis stockés dans MongoDB
 */
class MongoDBReviewRepository implements ReviewRepositoryInterface
{
    private Collection $collection;
    
    /**
     * Constructeur
     *
     * @param Client $client Client MongoDB
     * @param string $database Nom de la base de données
     * @param string $collection Nom de la collection
     */
    public function __construct(
        Client $client,
        string $database = 'ecoride',
        string $collection = 'reviews'
    ) {
        $this->collection = $client->selectDatabase($database)->selectCollection($collection);
    }
    
    /**
     * Crée un index pour améliorer les performances des requêtes
     *
     * @return void
     */
    public function createIndexes(): void
    {
        // Index pour recherches par utilisateur
        $this->collection->createIndex(['user_id' => 1]);
        
        // Index pour recherches par entité
        $this->collection->createIndex(['entity_type' => 1, 'entity_id' => 1]);
        
        // Index pour recherches par date
        $this->collection->createIndex(['created_at' => 1]);
        
        // Index pour recherches par note
        $this->collection->createIndex(['rating' => 1]);
    }
    
    /**
     * Trouve un avis par son ID
     *
     * @param string $id ID de l'avis
     * @return Review|null Avis trouvé ou null
     */
    public function findById(string $id): ?Review
    {
        $document = $this->collection->findOne(['id' => $id]);
        
        if ($document === null) {
            return null;
        }
        
        return Review::fromArray((array) $document);
    }
    
    /**
     * Enregistre un avis (création ou mise à jour)
     *
     * @param Review $review Avis à enregistrer
     * @return Review Avis enregistré
     */
    public function save(Review $review): Review
    {
        $data = $review->toArray();
        
        $this->collection->updateOne(
            ['id' => $review->id],
            ['$set' => $data],
            ['upsert' => true]
        );
        
        return $review;
    }
    
    /**
     * Supprime un avis
     *
     * @param string $id ID de l'avis
     * @return bool Succès de l'opération
     */
    public function delete(string $id): bool
    {
        $result = $this->collection->deleteOne(['id' => $id]);
        
        return $result->getDeletedCount() > 0;
    }
    
    /**
     * Récupère tous les avis pour une entité
     *
     * @param string $entityType Type d'entité
     * @param int $entityId ID de l'entité
     * @return array Liste d'avis
     */
    public function findForEntity(string $entityType, int $entityId): array
    {
        $documents = $this->collection->find([
            'entity_type' => strtolower($entityType),
            'entity_id' => $entityId
        ]);
        
        $reviews = [];
        foreach ($documents as $document) {
            $reviews[] = Review::fromArray((array) $document);
        }
        
        return $reviews;
    }
    
    /**
     * Récupère tous les avis écrits par un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @return array Liste d'avis
     */
    public function findByUser(int $userId): array
    {
        $documents = $this->collection->find(['user_id' => $userId]);
        
        $reviews = [];
        foreach ($documents as $document) {
            $reviews[] = Review::fromArray((array) $document);
        }
        
        return $reviews;
    }
    
    /**
     * Calcule la note moyenne pour une entité
     *
     * @param string $entityType Type d'entité
     * @param int $entityId ID de l'entité
     * @return float Note moyenne
     */
    public function getAverageRating(string $entityType, int $entityId): float
    {
        $pipeline = [
            [
                '$match' => [
                    'entity_type' => strtolower($entityType),
                    'entity_id' => $entityId
                ]
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
            return 0.0;
        }
        
        return round($result[0]->average, 1);
    }
    
    /**
     * Récupère la distribution des notes pour une entité
     *
     * @param string $entityType Type d'entité
     * @param int $entityId ID de l'entité
     * @return array Distribution des notes (clé = note, valeur = nombre)
     */
    public function getRatingDistribution(string $entityType, int $entityId): array
    {
        $pipeline = [
            [
                '$match' => [
                    'entity_type' => strtolower($entityType),
                    'entity_id' => $entityId
                ]
            ],
            [
                '$group' => [
                    '_id' => '$rating',
                    'count' => ['$sum' => 1]
                ]
            ],
            [
                '$sort' => ['_id' => 1]
            ]
        ];
        
        $result = $this->collection->aggregate($pipeline)->toArray();
        
        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        
        foreach ($result as $item) {
            $rating = $item->_id;
            $distribution[$rating] = $item->count;
        }
        
        return $distribution;
    }
    
    /**
     * Récupère les avis les plus récents
     *
     * @param int $limit Nombre d'avis à récupérer
     * @return array Liste d'avis
     */
    public function findRecent(int $limit = 10): array
    {
        $documents = $this->collection->find(
            [],
            [
                'sort' => ['created_at' => -1],
                'limit' => $limit
            ]
        );
        
        $reviews = [];
        foreach ($documents as $document) {
            $reviews[] = Review::fromArray((array) $document);
        }
        
        return $reviews;
    }
    
    /**
     * Recherche des avis par mot-clé dans le commentaire
     *
     * @param string $keyword Mot-clé à rechercher
     * @param int $limit Nombre maximum de résultats
     * @return array Liste d'avis
     */
    public function searchByKeyword(string $keyword, int $limit = 20): array
    {
        $documents = $this->collection->find(
            [
                'comment' => ['$regex' => $keyword, '$options' => 'i']
            ],
            [
                'limit' => $limit
            ]
        );
        
        $reviews = [];
        foreach ($documents as $document) {
            $reviews[] = Review::fromArray((array) $document);
        }
        
        return $reviews;
    }
    
    /**
     * Vérifie si un utilisateur a déjà écrit un avis sur une entité
     *
     * @param int $userId ID de l'utilisateur
     * @param string $entityType Type d'entité
     * @param int $entityId ID de l'entité
     * @return bool Vrai si l'utilisateur a déjà écrit un avis
     */
    public function hasUserReviewed(int $userId, string $entityType, int $entityId): bool
    {
        $count = $this->collection->countDocuments([
            'user_id' => $userId,
            'entity_type' => strtolower($entityType),
            'entity_id' => $entityId
        ]);
        
        return $count > 0;
    }
    
    /**
     * Récupère l'avis d'un utilisateur sur une entité
     *
     * @param int $userId ID de l'utilisateur
     * @param string $entityType Type d'entité
     * @param int $entityId ID de l'entité
     * @return Review|null Avis trouvé ou null
     */
    public function findUserReview(int $userId, string $entityType, int $entityId): ?Review
    {
        $document = $this->collection->findOne([
            'user_id' => $userId,
            'entity_type' => strtolower($entityType),
            'entity_id' => $entityId
        ]);
        
        if ($document === null) {
            return null;
        }
        
        return Review::fromArray((array) $document);
    }
} 