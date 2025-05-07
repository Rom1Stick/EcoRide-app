<?php

namespace Tests\Mocks;

use App\DataAccess\Exception\DataAccessException;
use App\DataAccess\NoSql\Model\Review;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;

/**
 * Mock du service de revues utilisateur pour les tests
 */
class ReviewServiceMock extends MockMongoService
{
    /**
     * Insère un nouvel avis
     * 
     * @param array $data Données de l'avis
     * @return ObjectId ID de l'avis inséré
     * @throws DataAccessException
     */
    public function insert(array $data)
    {
        try {
            // Conversion de la date au format MongoDB
            if (isset($data['createdAt']) && !($data['createdAt'] instanceof UTCDateTime)) {
                try {
                    $date = new \DateTime($data['createdAt']);
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
     * @param string|ObjectId $id
     * @return Review|null
     * @throws DataAccessException
     */
    public function findById($id)
    {
        try {
            $document = $this->collection->findOne(['_id' => $this->toObjectId($id)]);

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
     * @param string|ObjectId $id
     * @param array $data
     * @return bool
     * @throws DataAccessException
     */
    public function update($id, array $data): bool
    {
        try {
            // Ajouter la date de mise à jour
            $data['updatedAt'] = new UTCDateTime();

            $result = $this->collection->updateOne(
                ['_id' => $this->toObjectId($id)],
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
     * @param string|ObjectId $id
     * @return bool
     * @throws DataAccessException
     */
    public function delete($id): bool
    {
        try {
            $result = $this->collection->deleteOne(['_id' => $this->toObjectId($id)]);
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

            // Dans un test, nous pourrions simuler le résultat d'un agrégat
            $mockAggregateResults = [
                (object)[
                    'averageRating' => 4.5,
                    'count' => 2
                ]
            ];

            return empty($mockAggregateResults) ? null : $mockAggregateResults[0]->averageRating;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors du calcul de la note moyenne: " . $e->getMessage());
        }
    }
} 