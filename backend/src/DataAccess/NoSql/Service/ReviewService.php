<?php

namespace App\DataAccess\NoSql\Service;

use App\DataAccess\DataAccessException;
use App\DataAccess\NoSql\Model\Review;
use App\DataAccess\NoSql\Service\MongoServiceInterface;
use Exception;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection;

/**
 * Service pour gérer les avis utilisateurs dans MongoDB
 */
class ReviewService extends AbstractMongoService
{
    /**
     * Nom de la collection
     */
    private const COLLECTION_NAME = 'reviews';
    
    /**
     * Types d'avis
     */
    public const TYPE_CHAUFFEUR = 'chauffeur';
    public const TYPE_PASSAGER = 'passager';
    
    /**
     * États des avis
     */
    public const STATUS_EN_ATTENTE = 'en_attente';
    public const STATUS_APPROUVE = 'approuve';
    public const STATUS_SIGNALE = 'signale';
    public const STATUS_REJETE = 'rejete';
    
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
        // Index sur l'utilisateur qui a laissé l'avis
        $this->collection->createIndex(['userId' => 1]);
        
        // Index sur le covoiturage concerné
        $this->collection->createIndex(['covoiturageId' => 1]);
        
        // Index sur l'utilisateur évalué
        $this->collection->createIndex(['targetUserId' => 1]);
        
        // Index sur le type d'avis
        $this->collection->createIndex(['type' => 1]);
        
        // Index sur l'état de l'avis
        $this->collection->createIndex(['status' => 1]);
        
        // Index composé pour recherche rapide
        $this->collection->createIndex([
            'covoiturageId' => 1,
            'type' => 1,
            'status' => 1
        ]);
    }
    
    /**
     * Sauvegarder un avis
     *
     * @param Review $review
     * @return Review
     * @throws DataAccessException
     */
    public function save(Review $review): Review
    {
        try {
            if ($review->getId() === null) {
                // Nouvel avis
                $result = $this->collection->insertOne($review->jsonSerialize());
                
                if ($result->getInsertedCount() === 0) {
                    throw new DataAccessException("Échec de l'insertion de l'avis");
                }
                
                $review->setId((string)$result->getInsertedId());
            } else {
                // Mise à jour
                $data = $review->jsonSerialize();
                unset($data['_id']); // Ne pas mettre à jour l'ID
                
                $result = $this->collection->updateOne(
                    ['_id' => new ObjectId($review->getId())],
                    ['$set' => $data]
                );
                
                if ($result->getModifiedCount() === 0 && $result->getMatchedCount() === 0) {
                    throw new DataAccessException("Avis non trouvé ou non modifié");
                }
            }
            
            return $review;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la sauvegarde de l'avis: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Trouver un avis par son ID
     *
     * @param mixed $id
     * @return Review|null
     * @throws DataAccessException
     */
    public function findById($id)
    {
        try {
            $result = $this->collection->findOne(['_id' => new ObjectId($id)]);
            
            if ($result === null) {
                return null;
            }
            
            return Review::fromArray((array)$result);
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la recherche de l'avis: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Trouver les avis laissés par un utilisateur
     *
     * @param int $userId
     * @param array $options Options (limit, skip, type, status)
     * @return array
     * @throws DataAccessException
     */
    public function findByUserId(int $userId, array $options = []): array
    {
        try {
            $query = ['userId' => $userId];
            
            if (isset($options['type']) && !empty($options['type'])) {
                $query['type'] = $options['type'];
            }
            
            if (isset($options['status']) && !empty($options['status'])) {
                $query['status'] = $options['status'];
            }
            
            $findOptions = [
                'sort' => ['createdAt' => -1]
            ];
            
            if (isset($options['limit']) && $options['limit'] > 0) {
                $findOptions['limit'] = (int)$options['limit'];
            }
            
            if (isset($options['skip']) && $options['skip'] > 0) {
                $findOptions['skip'] = (int)$options['skip'];
            }
            
            $cursor = $this->collection->find($query, $findOptions);
            
            $reviews = [];
            foreach ($cursor as $document) {
                $reviews[] = Review::fromArray((array)$document);
            }
            
            return $reviews;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la recherche des avis par utilisateur: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Trouver les avis reçus par un utilisateur
     *
     * @param int $targetUserId
     * @param array $options Options (limit, skip, type, status)
     * @return array
     * @throws DataAccessException
     */
    public function findByTargetUserId(int $targetUserId, array $options = []): array
    {
        try {
            $query = ['targetUserId' => $targetUserId];
            
            if (isset($options['type']) && !empty($options['type'])) {
                $query['type'] = $options['type'];
            }
            
            if (isset($options['status']) && !empty($options['status'])) {
                $query['status'] = $options['status'];
            }
            
            $findOptions = [
                'sort' => ['createdAt' => -1]
            ];
            
            if (isset($options['limit']) && $options['limit'] > 0) {
                $findOptions['limit'] = (int)$options['limit'];
            }
            
            if (isset($options['skip']) && $options['skip'] > 0) {
                $findOptions['skip'] = (int)$options['skip'];
            }
            
            $cursor = $this->collection->find($query, $findOptions);
            
            $reviews = [];
            foreach ($cursor as $document) {
                $reviews[] = Review::fromArray((array)$document);
            }
            
            return $reviews;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la recherche des avis reçus: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Trouver les avis pour un covoiturage
     *
     * @param int $covoiturageId
     * @param array $options Options (limit, skip, type, status)
     * @return array
     * @throws DataAccessException
     */
    public function findByCovoiturageId(int $covoiturageId, array $options = []): array
    {
        try {
            $query = ['covoiturageId' => $covoiturageId];
            
            if (isset($options['type']) && !empty($options['type'])) {
                $query['type'] = $options['type'];
            }
            
            if (isset($options['status']) && !empty($options['status'])) {
                $query['status'] = $options['status'];
            }
            
            $findOptions = [
                'sort' => ['createdAt' => -1]
            ];
            
            if (isset($options['limit']) && $options['limit'] > 0) {
                $findOptions['limit'] = (int)$options['limit'];
            }
            
            if (isset($options['skip']) && $options['skip'] > 0) {
                $findOptions['skip'] = (int)$options['skip'];
            }
            
            $cursor = $this->collection->find($query, $findOptions);
            
            $reviews = [];
            foreach ($cursor as $document) {
                $reviews[] = Review::fromArray((array)$document);
            }
            
            return $reviews;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la recherche des avis pour un covoiturage: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Calculer la note moyenne d'un utilisateur
     *
     * @param int $targetUserId
     * @param string|null $type Type d'avis (chauffeur, passager)
     * @return array Tableau avec 'average' (note moyenne) et 'count' (nombre d'avis)
     * @throws DataAccessException
     */
    public function calculateAverageRating(int $targetUserId, ?string $type = null): array
    {
        try {
            $pipeline = [
                [
                    '$match' => [
                        'targetUserId' => $targetUserId,
                        'status' => self::STATUS_APPROUVE
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
            
            if ($type !== null) {
                $pipeline[0]['$match']['type'] = $type;
            }
            
            $result = $this->collection->aggregate($pipeline)->toArray();
            
            if (empty($result)) {
                return [
                    'average' => 0,
                    'count' => 0
                ];
            }
            
            $data = (array)$result[0];
            
            return [
                'average' => round($data['average'], 1),
                'count' => $data['count']
            ];
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors du calcul de la note moyenne: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Calculer les statistiques détaillées des critères pour un utilisateur
     *
     * @param int $targetUserId
     * @param string|null $type Type d'avis (chauffeur, passager)
     * @return array
     * @throws DataAccessException
     */
    public function calculateCriteriaStats(int $targetUserId, ?string $type = null): array
    {
        try {
            $query = [
                'targetUserId' => $targetUserId,
                'status' => self::STATUS_APPROUVE
            ];
            
            if ($type !== null) {
                $query['type'] = $type;
            }
            
            $cursor = $this->collection->find($query);
            
            $criteria = [];
            $count = 0;
            
            foreach ($cursor as $document) {
                $review = Review::fromArray((array)$document);
                $reviewCriteria = $review->getCriteria();
                
                foreach ($reviewCriteria as $key => $value) {
                    if (!isset($criteria[$key])) {
                        $criteria[$key] = [
                            'sum' => 0,
                            'count' => 0
                        ];
                    }
                    
                    $criteria[$key]['sum'] += $value;
                    $criteria[$key]['count']++;
                }
                
                $count++;
            }
            
            $result = [];
            
            foreach ($criteria as $key => $data) {
                $result[$key] = [
                    'average' => round($data['sum'] / $data['count'], 1),
                    'count' => $data['count']
                ];
            }
            
            return [
                'criteria' => $result,
                'totalReviews' => $count
            ];
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors du calcul des statistiques de critères: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Rechercher les avis avec des options avancées
     *
     * @param array $filters Filtres (userId, targetUserId, covoiturageId, type, status, minRating, maxRating)
     * @param int $limit
     * @param int $skip
     * @param array $sort Tri (field => direction)
     * @return array
     * @throws DataAccessException
     */
    public function search(array $filters = [], int $limit = 50, int $skip = 0, array $sort = ['createdAt' => -1]): array
    {
        try {
            $query = [];
            
            if (isset($filters['userId'])) {
                $query['userId'] = (int)$filters['userId'];
            }
            
            if (isset($filters['targetUserId'])) {
                $query['targetUserId'] = (int)$filters['targetUserId'];
            }
            
            if (isset($filters['covoiturageId'])) {
                $query['covoiturageId'] = (int)$filters['covoiturageId'];
            }
            
            if (isset($filters['type'])) {
                $query['type'] = $filters['type'];
            }
            
            if (isset($filters['status'])) {
                $query['status'] = $filters['status'];
            }
            
            if (isset($filters['minRating']) || isset($filters['maxRating'])) {
                $query['rating'] = [];
                
                if (isset($filters['minRating'])) {
                    $query['rating']['$gte'] = (int)$filters['minRating'];
                }
                
                if (isset($filters['maxRating'])) {
                    $query['rating']['$lte'] = (int)$filters['maxRating'];
                }
            }
            
            $cursor = $this->collection->find(
                $query,
                [
                    'sort' => $sort,
                    'limit' => $limit,
                    'skip' => $skip
                ]
            );
            
            $reviews = [];
            foreach ($cursor as $document) {
                $reviews[] = Review::fromArray((array)$document);
            }
            
            return $reviews;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la recherche des avis: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Compter les avis selon des filtres
     *
     * @param array $filters Filtres (userId, targetUserId, covoiturageId, type, status, minRating, maxRating)
     * @return int
     * @throws DataAccessException
     */
    public function count(array $filters = []): int
    {
        try {
            $query = [];
            
            if (isset($filters['userId'])) {
                $query['userId'] = (int)$filters['userId'];
            }
            
            if (isset($filters['targetUserId'])) {
                $query['targetUserId'] = (int)$filters['targetUserId'];
            }
            
            if (isset($filters['covoiturageId'])) {
                $query['covoiturageId'] = (int)$filters['covoiturageId'];
            }
            
            if (isset($filters['type'])) {
                $query['type'] = $filters['type'];
            }
            
            if (isset($filters['status'])) {
                $query['status'] = $filters['status'];
            }
            
            if (isset($filters['minRating']) || isset($filters['maxRating'])) {
                $query['rating'] = [];
                
                if (isset($filters['minRating'])) {
                    $query['rating']['$gte'] = (int)$filters['minRating'];
                }
                
                if (isset($filters['maxRating'])) {
                    $query['rating']['$lte'] = (int)$filters['maxRating'];
                }
            }
            
            return $this->collection->countDocuments($query);
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors du comptage des avis: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Approuver un avis
     *
     * @param string $id
     * @return bool
     * @throws DataAccessException
     */
    public function approveReview(string $id): bool
    {
        try {
            $review = $this->findById($id);
            
            if ($review === null) {
                throw new DataAccessException("Avis non trouvé");
            }
            
            $review->approve();
            $this->save($review);
            
            return true;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de l'approbation de l'avis: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Signaler un avis
     *
     * @param string $id
     * @return bool
     * @throws DataAccessException
     */
    public function flagReview(string $id): bool
    {
        try {
            $review = $this->findById($id);
            
            if ($review === null) {
                throw new DataAccessException("Avis non trouvé");
            }
            
            $review->flag();
            $this->save($review);
            
            return true;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors du signalement de l'avis: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Rejeter un avis
     *
     * @param string $id
     * @return bool
     * @throws DataAccessException
     */
    public function rejectReview(string $id): bool
    {
        try {
            $review = $this->findById($id);
            
            if ($review === null) {
                throw new DataAccessException("Avis non trouvé");
            }
            
            $review->setStatus(self::STATUS_REJETE);
            $review->touch();
            $this->save($review);
            
            return true;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors du rejet de l'avis: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Supprimer un avis
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
            throw new DataAccessException("Erreur lors de la suppression de l'avis: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Supprimer tous les avis d'un utilisateur
     *
     * @param int $userId
     * @return int Nombre d'avis supprimés
     * @throws DataAccessException
     */
    public function deleteByUserId(int $userId): int
    {
        try {
            $result = $this->collection->deleteMany(['userId' => $userId]);
            return $result->getDeletedCount();
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la suppression des avis de l'utilisateur: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Supprimer tous les avis concernant un covoiturage
     *
     * @param int $covoiturageId
     * @return int Nombre d'avis supprimés
     * @throws DataAccessException
     */
    public function deleteByCovoiturageId(int $covoiturageId): int
    {
        try {
            $result = $this->collection->deleteMany(['covoiturageId' => $covoiturageId]);
            return $result->getDeletedCount();
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la suppression des avis du covoiturage: " . $e->getMessage(), 0, $e);
        }
    }
} 