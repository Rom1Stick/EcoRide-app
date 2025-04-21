<?php

namespace App\Repositories\NoSQL;

use App\Core\Database\MongoConnection;
use App\Core\Exceptions\ConnectionException;
use App\Core\Exceptions\PersistenceException;
use App\Core\Exceptions\ValidationException;
use App\Models\Documents\ReviewDocument;
use App\Repositories\Interfaces\IReviewRepository;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection;
use Exception;

/**
 * Implémentation du repository pour les avis avec MongoDB
 */
class ReviewRepository implements IReviewRepository
{
    private MongoConnection $dbConnection;
    private Collection $collection;
    private string $collectionName = 'reviews';
    
    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->dbConnection = MongoConnection::getInstance();
        $this->collection = $this->dbConnection->getCollection($this->collectionName);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findById($id): ?ReviewDocument
    {
        try {
            $objectId = is_string($id) ? new ObjectId($id) : $id;
            
            $document = $this->collection->findOne(['_id' => $objectId]);
            
            if ($document === null) {
                return null;
            }
            
            $data = $this->convertDocumentToArray($document);
            
            return ReviewDocument::fromArray($data);
        } catch (Exception $e) {
            throw new PersistenceException('REVIEW', 'READ', 'Failed to find review by ID: ' . $e->getMessage(), null, 0, $e);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function findAll(int $page = 1, int $limit = 20): array
    {
        try {
            $skip = ($page - 1) * $limit;
            
            $cursor = $this->collection->find(
                [],
                [
                    'limit' => $limit,
                    'skip' => $skip,
                    'sort' => ['createdAt' => -1]
                ]
            );
            
            $reviews = [];
            
            foreach ($cursor as $document) {
                $data = $this->convertDocumentToArray($document);
                $reviews[] = ReviewDocument::fromArray($data);
            }
            
            return $reviews;
        } catch (Exception $e) {
            throw new PersistenceException('REVIEW', 'READ', 'Failed to find all reviews: ' . $e->getMessage(), null, 0, $e);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByUserId(int $userId, int $page = 1, int $limit = 20): array
    {
        try {
            $skip = ($page - 1) * $limit;
            
            $cursor = $this->collection->find(
                ['userId' => $userId],
                [
                    'limit' => $limit,
                    'skip' => $skip,
                    'sort' => ['createdAt' => -1]
                ]
            );
            
            $reviews = [];
            
            foreach ($cursor as $document) {
                $data = $this->convertDocumentToArray($document);
                $reviews[] = ReviewDocument::fromArray($data);
            }
            
            return $reviews;
        } catch (Exception $e) {
            throw new PersistenceException('REVIEW', 'READ', 'Failed to find reviews by user ID: ' . $e->getMessage(), null, 0, $e);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByTripId(int $tripId, int $page = 1, int $limit = 20): array
    {
        try {
            $skip = ($page - 1) * $limit;
            
            $cursor = $this->collection->find(
                ['tripId' => $tripId],
                [
                    'limit' => $limit,
                    'skip' => $skip,
                    'sort' => ['createdAt' => -1]
                ]
            );
            
            $reviews = [];
            
            foreach ($cursor as $document) {
                $data = $this->convertDocumentToArray($document);
                $reviews[] = ReviewDocument::fromArray($data);
            }
            
            return $reviews;
        } catch (Exception $e) {
            throw new PersistenceException('REVIEW', 'READ', 'Failed to find reviews by trip ID: ' . $e->getMessage(), null, 0, $e);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByUserAndTrip(int $userId, int $tripId): ?ReviewDocument
    {
        try {
            $document = $this->collection->findOne([
                'userId' => $userId,
                'tripId' => $tripId
            ]);
            
            if ($document === null) {
                return null;
            }
            
            $data = $this->convertDocumentToArray($document);
            
            return ReviewDocument::fromArray($data);
        } catch (Exception $e) {
            throw new PersistenceException('REVIEW', 'READ', 'Failed to find review by user and trip: ' . $e->getMessage(), null, 0, $e);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getAverageRatingForTrip(int $tripId): float
    {
        try {
            $pipeline = [
                [
                    '$match' => [
                        'tripId' => $tripId,
                        'status' => 'approved' // Ne considérer que les avis approuvés
                    ]
                ],
                [
                    '$group' => [
                        '_id' => null,
                        'averageRating' => ['$avg' => '$rating']
                    ]
                ]
            ];
            
            $result = $this->collection->aggregate($pipeline)->toArray();
            
            if (empty($result)) {
                return 0.0;
            }
            
            return (float)$result[0]['averageRating'];
        } catch (Exception $e) {
            throw new PersistenceException('REVIEW', 'AGGREGATE', 'Failed to get average rating: ' . $e->getMessage(), null, 0, $e);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function create(ReviewDocument $review): ?string
    {
        $errors = $review->validate();
        if (!empty($errors)) {
            throw new ValidationException('REVIEW', $errors);
        }
        
        try {
            // Vérifier qu'il n'existe pas déjà un avis pour ce trajet et cet utilisateur
            $existingReview = $this->findByUserAndTrip($review->getUserId(), $review->getTripId());
            if ($existingReview !== null) {
                throw new ValidationException('REVIEW', ['uniqueConstraint' => 'Un avis existe déjà pour cet utilisateur et ce trajet']);
            }
            
            $data = $review->toArray();
            
            $result = $this->collection->insertOne($data);
            
            if (!$result->isAcknowledged() || !$result->getInsertedId()) {
                return null;
            }
            
            return (string)$result->getInsertedId();
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new PersistenceException('REVIEW', 'CREATE', 'Failed to create review: ' . $e->getMessage(), null, 0, $e);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function update(ReviewDocument $review): bool
    {
        if ($review->getId() === null) {
            throw new ValidationException('REVIEW', ['id' => 'ID is required for update']);
        }
        
        $errors = $review->validate();
        if (!empty($errors)) {
            throw new ValidationException('REVIEW', $errors);
        }
        
        try {
            $objectId = new ObjectId($review->getId());
            
            $data = $review->toArray();
            $data['updatedAt'] = date('Y-m-d\TH:i:s.v\Z');
            
            $result = $this->collection->updateOne(
                ['_id' => $objectId],
                ['$set' => $data]
            );
            
            return $result->isAcknowledged() && $result->getModifiedCount() > 0;
        } catch (Exception $e) {
            throw new PersistenceException('REVIEW', 'UPDATE', 'Failed to update review: ' . $e->getMessage(), null, 0, $e);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete($id): bool
    {
        try {
            $objectId = is_string($id) ? new ObjectId($id) : $id;
            
            $result = $this->collection->deleteOne(['_id' => $objectId]);
            
            return $result->isAcknowledged() && $result->getDeletedCount() > 0;
        } catch (Exception $e) {
            throw new PersistenceException('REVIEW', 'DELETE', 'Failed to delete review: ' . $e->getMessage(), null, 0, $e);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function updateStatus($id, string $status): bool
    {
        try {
            $objectId = is_string($id) ? new ObjectId($id) : $id;
            
            $validStatuses = ['pending', 'approved', 'rejected'];
            if (!in_array($status, $validStatuses)) {
                throw new ValidationException('REVIEW', ['status' => 'Status must be one of: ' . implode(', ', $validStatuses)]);
            }
            
            $result = $this->collection->updateOne(
                ['_id' => $objectId],
                [
                    '$set' => [
                        'status' => $status,
                        'updatedAt' => date('Y-m-d\TH:i:s.v\Z')
                    ]
                ]
            );
            
            return $result->isAcknowledged() && $result->getModifiedCount() > 0;
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new PersistenceException('REVIEW', 'UPDATE_STATUS', 'Failed to update review status: ' . $e->getMessage(), null, 0, $e);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        try {
            return $this->collection->countDocuments([]);
        } catch (Exception $e) {
            throw new PersistenceException('REVIEW', 'COUNT', 'Failed to count reviews: ' . $e->getMessage(), null, 0, $e);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function countByStatus(string $status): int
    {
        try {
            $validStatuses = ['pending', 'approved', 'rejected'];
            if (!in_array($status, $validStatuses)) {
                throw new ValidationException('REVIEW', ['status' => 'Status must be one of: ' . implode(', ', $validStatuses)]);
            }
            
            return $this->collection->countDocuments(['status' => $status]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new PersistenceException('REVIEW', 'COUNT', 'Failed to count reviews by status: ' . $e->getMessage(), null, 0, $e);
        }
    }
    
    /**
     * Convertit un document MongoDB en tableau associatif
     *
     * @param object $document Document MongoDB
     * @return array
     */
    private function convertDocumentToArray(object $document): array
    {
        $data = (array)$document;
        
        // Convertir l'ObjectId en string
        if (isset($data['_id']) && $data['_id'] instanceof ObjectId) {
            $data['_id'] = (string)$data['_id'];
        }
        
        return $data;
    }
} 