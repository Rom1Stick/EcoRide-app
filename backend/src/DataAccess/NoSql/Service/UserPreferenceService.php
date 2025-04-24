<?php

namespace App\DataAccess\NoSql\Service;

use MongoDB\BSON\ObjectId;
use App\DataAccess\NoSql\Model\UserPreference;
use App\DataAccess\Exception\DataAccessException;

/**
 * Service pour les préférences utilisateur dans MongoDB
 */
class UserPreferenceService extends AbstractMongoService
{
    /**
     * {@inheritDoc}
     */
    protected function initService(): void
    {
        $this->collectionName = 'preferences';
    }

    /**
     * Trouve les préférences d'un utilisateur par son ID
     * 
     * @param int $userId ID de l'utilisateur
     * @return UserPreference|null Préférences de l'utilisateur ou null si non trouvées
     * @throws DataAccessException En cas d'erreur d'accès à la base de données
     */
    public function findByUserId(int $userId): ?UserPreference
    {
        try {
            $result = $this->collection->findOne(['userId' => $userId]);
            
            if ($result) {
                return UserPreference::fromArray($this->formatDocument($result));
            }
            
            return null;
        } catch (\Exception $e) {
            throw new DataAccessException(
                "Erreur lors de la récupération des préférences de l'utilisateur avec l'ID $userId : " . $e->getMessage(),
                0,
                $e,
                "NoSQL"
            );
        }
    }

    /**
     * Crée ou met à jour les préférences d'un utilisateur
     * 
     * @param UserPreference $preference Préférences de l'utilisateur
     * @return ObjectId|bool ObjectId si création, bool si mise à jour
     * @throws DataAccessException En cas d'erreur d'accès à la base de données
     */
    public function savePreference(UserPreference $preference)
    {
        $existingPreference = $this->findByUserId($preference->getUserId());
        
        if ($existingPreference) {
            // Mise à jour d'une préférence existante
            return $this->update(
                $existingPreference->getId(),
                $preference->toArray()
            );
        } else {
            // Création d'une nouvelle préférence
            return $this->insert($preference->toArray());
        }
    }

    /**
     * Met à jour une préférence standard spécifique pour un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $key Clé de la préférence
     * @param mixed $value Valeur de la préférence
     * @return bool True si la mise à jour a réussi, False sinon
     * @throws DataAccessException En cas d'erreur d'accès à la base de données
     */
    public function updateStandardPreference(int $userId, string $key, $value): bool
    {
        try {
            $result = $this->collection->updateOne(
                ['userId' => $userId],
                [
                    '$set' => [
                        "standard.$key" => $value,
                        'updatedAt' => new \MongoDB\BSON\UTCDateTime(time() * 1000)
                    ]
                ],
                ['upsert' => true]
            );
            
            return $result->getModifiedCount() > 0 || $result->getUpsertedCount() > 0;
        } catch (\Exception $e) {
            throw new DataAccessException(
                "Erreur lors de la mise à jour de la préférence standard '$key' pour l'utilisateur avec l'ID $userId : " . $e->getMessage(),
                0,
                $e,
                "NoSQL"
            );
        }
    }

    /**
     * Met à jour une préférence personnalisée spécifique pour un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $key Clé de la préférence
     * @param mixed $value Valeur de la préférence
     * @return bool True si la mise à jour a réussi, False sinon
     * @throws DataAccessException En cas d'erreur d'accès à la base de données
     */
    public function updateCustomPreference(int $userId, string $key, $value): bool
    {
        $preference = $this->findByUserId($userId);
        
        if ($preference) {
            // Vérifier si la préférence personnalisée existe déjà
            $exists = false;
            foreach ($preference->getCustom() as $index => $item) {
                if ($item['key'] === $key) {
                    $exists = true;
                    break;
                }
            }
            
            try {
                if ($exists) {
                    // Mettre à jour la préférence existante
                    $result = $this->collection->updateOne(
                        [
                            'userId' => $userId,
                            'custom.key' => $key
                        ],
                        [
                            '$set' => [
                                'custom.$.value' => $value,
                                'updatedAt' => new \MongoDB\BSON\UTCDateTime(time() * 1000)
                            ]
                        ]
                    );
                } else {
                    // Ajouter une nouvelle préférence
                    $result = $this->collection->updateOne(
                        ['userId' => $userId],
                        [
                            '$push' => [
                                'custom' => [
                                    'key' => $key,
                                    'value' => $value
                                ]
                            ],
                            '$set' => [
                                'updatedAt' => new \MongoDB\BSON\UTCDateTime(time() * 1000)
                            ]
                        ],
                        ['upsert' => true]
                    );
                }
                
                return $result->getModifiedCount() > 0 || $result->getUpsertedCount() > 0;
            } catch (\Exception $e) {
                throw new DataAccessException(
                    "Erreur lors de la mise à jour de la préférence personnalisée '$key' pour l'utilisateur avec l'ID $userId : " . $e->getMessage(),
                    0,
                    $e,
                    "NoSQL"
                );
            }
        } else {
            // Créer un nouvel objet de préférence
            $newPreference = new UserPreference();
            $newPreference->setUserId($userId);
            $newPreference->setCustomPreference($key, $value);
            
            $this->insert($newPreference->toArray());
            return true;
        }
    }

    /**
     * Supprime une préférence standard spécifique pour un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $key Clé de la préférence
     * @return bool True si la suppression a réussi, False sinon
     * @throws DataAccessException En cas d'erreur d'accès à la base de données
     */
    public function deleteStandardPreference(int $userId, string $key): bool
    {
        try {
            $result = $this->collection->updateOne(
                ['userId' => $userId],
                [
                    '$unset' => ["standard.$key" => ""],
                    '$set' => ['updatedAt' => new \MongoDB\BSON\UTCDateTime(time() * 1000)]
                ]
            );
            
            return $result->getModifiedCount() > 0;
        } catch (\Exception $e) {
            throw new DataAccessException(
                "Erreur lors de la suppression de la préférence standard '$key' pour l'utilisateur avec l'ID $userId : " . $e->getMessage(),
                0,
                $e,
                "NoSQL"
            );
        }
    }

    /**
     * Supprime une préférence personnalisée spécifique pour un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $key Clé de la préférence
     * @return bool True si la suppression a réussi, False sinon
     * @throws DataAccessException En cas d'erreur d'accès à la base de données
     */
    public function deleteCustomPreference(int $userId, string $key): bool
    {
        try {
            $result = $this->collection->updateOne(
                ['userId' => $userId],
                [
                    '$pull' => ['custom' => ['key' => $key]],
                    '$set' => ['updatedAt' => new \MongoDB\BSON\UTCDateTime(time() * 1000)]
                ]
            );
            
            return $result->getModifiedCount() > 0;
        } catch (\Exception $e) {
            throw new DataAccessException(
                "Erreur lors de la suppression de la préférence personnalisée '$key' pour l'utilisateur avec l'ID $userId : " . $e->getMessage(),
                0,
                $e,
                "NoSQL"
            );
        }
    }

    /**
     * Récupère toutes les préférences des utilisateurs ayant une valeur spécifique
     * 
     * @param string $key Clé de la préférence
     * @param mixed $value Valeur de la préférence
     * @param bool $isStandard True pour chercher dans les préférences standard, False pour les personnalisées
     * @return array Liste des préférences utilisateur correspondant aux critères
     * @throws DataAccessException En cas d'erreur d'accès à la base de données
     */
    public function findByPreferenceValue(string $key, $value, bool $isStandard = true): array
    {
        $field = $isStandard ? "standard.$key" : "custom";
        $criteria = $isStandard 
            ? [$field => $value] 
            : ['custom' => ['$elemMatch' => ['key' => $key, 'value' => $value]]];
        
        try {
            $results = $this->collection->find($criteria);
            
            $preferences = [];
            foreach ($results as $document) {
                $preferences[] = UserPreference::fromArray($this->formatDocument($document));
            }
            
            return $preferences;
        } catch (\Exception $e) {
            throw new DataAccessException(
                "Erreur lors de la recherche des préférences avec la valeur spécifique : " . $e->getMessage(),
                0,
                $e,
                "NoSQL"
            );
        }
    }
} 