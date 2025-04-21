<?php

namespace App\Services;

use App\Models\Entities\Review;
use Exception;
use MongoDB\Client;
use MongoDB\Collection;

/**
 * Service pour gérer les avis dans MongoDB
 */
class ReviewService
{
    private Collection $collection;
    private UserService $userService;
    
    /**
     * Constructeur
     *
     * @param string $mongoUri URI de connexion MongoDB
     * @param string $database Nom de la base de données
     * @param UserService $userService Service utilisateur pour la vérification
     */
    public function __construct(
        string $mongoUri = 'mongodb://localhost:27017',
        string $database = 'ecoride',
        UserService $userService
    ) {
        $client = new Client($mongoUri);
        $this->collection = $client->$database->reviews;
        $this->userService = $userService;
        
        // Créer des index pour optimiser les requêtes
        $this->ensureIndexes();
    }
    
    /**
     * Crée les index nécessaires pour cette collection
     */
    private function ensureIndexes(): void
    {
        $this->collection->createIndex(['user_id' => 1]);
        $this->collection->createIndex(['entity_type' => 1, 'entity_id' => 1]);
        $this->collection->createIndex(['is_deleted' => 1]);
        $this->collection->createIndex(['rating' => 1]);
    }
    
    /**
     * Crée un nouvel avis
     *
     * @param int $userId ID de l'utilisateur
     * @param string $entityType Type d'entité
     * @param int $entityId ID de l'entité
     * @param int $rating Note
     * @param string $comment Commentaire
     * @param array $metadata Métadonnées
     * @return Review Avis créé
     * @throws Exception Si l'utilisateur n'existe pas
     */
    public function createReview(
        int $userId,
        string $entityType,
        int $entityId,
        int $rating,
        string $comment,
        array $metadata = []
    ): Review {
        // Vérifier si l'utilisateur existe
        if (!$this->userService->exists($userId)) {
            throw new Exception("L'utilisateur avec l'ID $userId n'existe pas.");
        }
        
        // Vérifier si l'utilisateur a déjà laissé un avis pour cette entité
        $existingReview = $this->getUserReviewForEntity($userId, $entityType, $entityId);
        if ($existingReview) {
            throw new Exception("L'utilisateur a déjà laissé un avis pour cette entité.");
        }
        
        // Créer l'avis
        $review = new Review($userId, $entityType, $entityId, $rating, $comment, $metadata);
        
        // Enregistrer dans MongoDB
        $this->collection->insertOne($review->toArray());
        
        return $review;
    }
    
    /**
     * Récupère un avis par son ID
     *
     * @param string $reviewId ID de l'avis
     * @return Review|null Avis ou null si non trouvé
     */
    public function getReviewById(string $reviewId): ?Review
    {
        $data = $this->collection->findOne(['id' => $reviewId, 'is_deleted' => false]);
        
        if (!$data) {
            return null;
        }
        
        return Review::fromArray((array)$data);
    }
    
    /**
     * Met à jour un avis existant
     *
     * @param string $reviewId ID de l'avis
     * @param int $rating Nouvelle note
     * @param string $comment Nouveau commentaire
     * @param array|null $metadata Nouvelles métadonnées (null pour conserver les existantes)
     * @return Review Avis mis à jour
     * @throws Exception Si l'avis n'existe pas ou est supprimé
     */
    public function updateReview(
        string $reviewId,
        int $rating,
        string $comment,
        ?array $metadata = null
    ): Review {
        $review = $this->getReviewById($reviewId);
        
        if (!$review) {
            throw new Exception("L'avis avec l'ID $reviewId n'existe pas ou a été supprimé.");
        }
        
        $review->update($rating, $comment, $metadata);
        
        $updateData = [
            'rating' => $review->rating,
            'comment' => $review->comment,
            'updated_at' => $review->updated_at
        ];
        
        if ($metadata !== null) {
            $updateData['metadata'] = $review->metadata;
        }
        
        $this->collection->updateOne(
            ['id' => $reviewId],
            ['$set' => $updateData]
        );
        
        return $review;
    }
    
    /**
     * Supprime un avis (soft delete)
     *
     * @param string $reviewId ID de l'avis
     * @return bool Succès de la suppression
     */
    public function deleteReview(string $reviewId): bool
    {
        $review = $this->getReviewById($reviewId);
        
        if (!$review) {
            return false;
        }
        
        $review->markAsDeleted();
        
        $result = $this->collection->updateOne(
            ['id' => $reviewId],
            ['$set' => [
                'is_deleted' => true,
                'updated_at' => $review->updated_at
            ]]
        );
        
        return $result->getModifiedCount() > 0;
    }
    
    /**
     * Récupère l'avis d'un utilisateur pour une entité spécifique
     *
     * @param int $userId ID de l'utilisateur
     * @param string $entityType Type d'entité
     * @param int $entityId ID de l'entité
     * @return Review|null Avis ou null si non trouvé
     */
    public function getUserReviewForEntity(int $userId, string $entityType, int $entityId): ?Review
    {
        $data = $this->collection->findOne([
            'user_id' => $userId,
            'entity_type' => strtolower($entityType),
            'entity_id' => $entityId,
            'is_deleted' => false
        ]);
        
        if (!$data) {
            return null;
        }
        
        return Review::fromArray((array)$data);
    }
    
    /**
     * Récupère tous les avis pour une entité
     *
     * @param string $entityType Type d'entité
     * @param int $entityId ID de l'entité
     * @param int $limit Limite de résultats
     * @param int $offset Offset pour la pagination
     * @return array Tableau d'avis
     */
    public function getReviewsForEntity(
        string $entityType,
        int $entityId,
        int $limit = 10,
        int $offset = 0
    ): array {
        $cursor = $this->collection->find(
            [
                'entity_type' => strtolower($entityType),
                'entity_id' => $entityId,
                'is_deleted' => false
            ],
            [
                'sort' => ['created_at' => -1],
                'limit' => $limit,
                'skip' => $offset
            ]
        );
        
        $reviews = [];
        foreach ($cursor as $data) {
            $reviews[] = Review::fromArray((array)$data);
        }
        
        return $reviews;
    }
    
    /**
     * Compte le nombre d'avis pour une entité
     *
     * @param string $entityType Type d'entité
     * @param int $entityId ID de l'entité
     * @return int Nombre d'avis
     */
    public function countReviewsForEntity(string $entityType, int $entityId): int
    {
        return $this->collection->countDocuments([
            'entity_type' => strtolower($entityType),
            'entity_id' => $entityId,
            'is_deleted' => false
        ]);
    }
    
    /**
     * Calcule la note moyenne pour une entité
     *
     * @param string $entityType Type d'entité
     * @param int $entityId ID de l'entité
     * @return float Note moyenne
     */
    public function getAverageRatingForEntity(string $entityType, int $entityId): float
    {
        $pipeline = [
            [
                '$match' => [
                    'entity_type' => strtolower($entityType),
                    'entity_id' => $entityId,
                    'is_deleted' => false
                ]
            ],
            [
                '$group' => [
                    '_id' => null,
                    'averageRating' => ['$avg' => '$rating'],
                    'count' => ['$sum' => 1]
                ]
            ]
        ];
        
        $result = $this->collection->aggregate($pipeline)->toArray();
        
        if (empty($result)) {
            return 0.0;
        }
        
        return round($result[0]->averageRating, 1);
    }
    
    /**
     * Récupère des statistiques sur les avis pour une entité
     *
     * @param string $entityType Type d'entité
     * @param int $entityId ID de l'entité
     * @return array Statistiques (note moyenne, nombre d'avis, distribution des notes)
     */
    public function getReviewStatsForEntity(string $entityType, int $entityId): array
    {
        $pipeline = [
            [
                '$match' => [
                    'entity_type' => strtolower($entityType),
                    'entity_id' => $entityId,
                    'is_deleted' => false
                ]
            ],
            [
                '$facet' => [
                    'average' => [
                        ['$group' => [
                            '_id' => null,
                            'average' => ['$avg' => '$rating'],
                            'count' => ['$sum' => 1]
                        ]]
                    ],
                    'distribution' => [
                        ['$group' => [
                            '_id' => '$rating',
                            'count' => ['$sum' => 1]
                        ]],
                        ['$sort' => ['_id' => 1]]
                    ]
                ]
            ]
        ];
        
        $result = $this->collection->aggregate($pipeline)->toArray();
        
        if (empty($result) || empty($result[0]->average)) {
            return [
                'average' => 0,
                'count' => 0,
                'distribution' => [
                    1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0
                ]
            ];
        }
        
        $stats = [
            'average' => round($result[0]->average[0]->average, 1),
            'count' => $result[0]->average[0]->count,
            'distribution' => [
                1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0
            ]
        ];
        
        foreach ($result[0]->distribution as $item) {
            $stats['distribution'][$item->_id] = $item->count;
        }
        
        return $stats;
    }
    
    /**
     * Récupère tous les avis laissés par un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param int $limit Limite de résultats
     * @param int $offset Offset pour la pagination
     * @return array Tableau d'avis
     */
    public function getUserReviews(int $userId, int $limit = 10, int $offset = 0): array
    {
        $cursor = $this->collection->find(
            [
                'user_id' => $userId,
                'is_deleted' => false
            ],
            [
                'sort' => ['created_at' => -1],
                'limit' => $limit,
                'skip' => $offset
            ]
        );
        
        $reviews = [];
        foreach ($cursor as $data) {
            $reviews[] = Review::fromArray((array)$data);
        }
        
        return $reviews;
    }
} 