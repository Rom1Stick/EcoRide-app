<?php

namespace App\Repositories\NoSQL;

use App\Models\Entities\UserPreferences;
use MongoDB\Client;
use MongoDB\Collection;

/**
 * Repository pour accéder aux préférences utilisateur dans MongoDB
 */
class UserPreferencesRepository
{
    private Collection $collection;
    
    /**
     * Constructeur
     *
     * @param Client $mongoClient Client MongoDB
     */
    public function __construct(Client $mongoClient)
    {
        $this->collection = $mongoClient->ecoride->user_preferences;
    }
    
    /**
     * Trouve les préférences par ID utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @return UserPreferences|null Préférences de l'utilisateur ou null
     */
    public function findByUserId(int $userId): ?UserPreferences
    {
        $document = $this->collection->findOne(['user_id' => $userId]);
        
        if (!$document) {
            return null;
        }
        
        return new UserPreferences(
            $userId,
            (array) $document['preferences'],
            $document['updated_at'] ?? null
        );
    }
    
    /**
     * Enregistre les préférences d'un utilisateur
     *
     * @param UserPreferences $preferences Préférences à enregistrer
     * @return bool Succès de l'opération
     */
    public function save(UserPreferences $preferences): bool
    {
        $data = [
            'user_id' => $preferences->user_id,
            'preferences' => $preferences->preferences,
            'updated_at' => $preferences->updated_at
        ];
        
        $result = $this->collection->updateOne(
            ['user_id' => $preferences->user_id],
            ['$set' => $data],
            ['upsert' => true]
        );
        
        return $result->getModifiedCount() > 0 || $result->getUpsertedCount() > 0;
    }
    
    /**
     * Supprime les préférences d'un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @return bool Succès de l'opération
     */
    public function deleteByUserId(int $userId): bool
    {
        $result = $this->collection->deleteOne(['user_id' => $userId]);
        
        return $result->getDeletedCount() > 0;
    }
    
    /**
     * Trouve des utilisateurs avec des préférences similaires
     *
     * @param int $userId ID de l'utilisateur
     * @param int $limit Nombre maximum d'utilisateurs à retourner
     * @return array IDs des utilisateurs similaires
     */
    public function findSimilarUsers(int $userId, int $limit = 5): array
    {
        $userPreferences = $this->findByUserId($userId);
        
        if (!$userPreferences) {
            return [];
        }
        
        // Récupérer tous les utilisateurs sauf celui en question
        $cursor = $this->collection->find(['user_id' => ['$ne' => $userId]]);
        
        $similarUsers = [];
        
        foreach ($cursor as $document) {
            $otherPreferences = new UserPreferences(
                $document['user_id'],
                (array) $document['preferences'],
                $document['updated_at'] ?? null
            );
            
            $similarityScore = $userPreferences->getSimilarityWith($otherPreferences);
            
            if ($similarityScore > 0) {
                $similarUsers[$document['user_id']] = $similarityScore;
            }
        }
        
        // Trier par similarité décroissante
        arsort($similarUsers);
        
        // Limiter le nombre de résultats
        return array_keys(array_slice($similarUsers, 0, $limit, true));
    }
    
    /**
     * Récupère les préférences populaires
     *
     * @param int $limit Nombre maximum de préférences à retourner
     * @return array Préférences populaires
     */
    public function getPopularPreferences(int $limit = 10): array
    {
        $pipeline = [
            ['$project' => ['_id' => 0, 'preferences' => 1]],
            ['$unwind' => '$preferences'],
            ['$group' => [
                '_id' => '$preferences.key',
                'value' => ['$first' => '$preferences.value'],
                'count' => ['$sum' => 1]
            ]],
            ['$sort' => ['count' => -1]],
            ['$limit' => $limit]
        ];
        
        $result = $this->collection->aggregate($pipeline);
        
        $popularPreferences = [];
        
        foreach ($result as $document) {
            $popularPreferences[$document['_id']] = [
                'value' => $document['value'],
                'count' => $document['count']
            ];
        }
        
        return $popularPreferences;
    }
    
    /**
     * Met à jour une préférence pour tous les utilisateurs
     *
     * @param string $oldKey Ancienne clé
     * @param string $newKey Nouvelle clé
     * @return int Nombre de documents mis à jour
     */
    public function updatePreferenceKeyForAllUsers(string $oldKey, string $newKey): int
    {
        // Suppression du pipeline non utilisé qui utilisait une variable non définie
        $result = $this->collection->updateMany(
            ["preferences.$oldKey" => ['$exists' => true]],
            ['$rename' => ["preferences.$oldKey" => "preferences.$newKey"]]
        );
        
        return $result->getModifiedCount();
    }
    
    /**
     * Compte le nombre d'utilisateurs ayant une préférence spécifique
     *
     * @param string $key Clé de la préférence
     * @param mixed $value Valeur de la préférence (optionnel)
     * @return int Nombre d'utilisateurs
     */
    public function countUsersWithPreference(string $key, $value = null): int
    {
        $filter = ["preferences.$key" => ['$exists' => true]];
        
        if ($value !== null) {
            $filter["preferences.$key"] = $value;
        }
        
        return $this->collection->countDocuments($filter);
    }
    
    /**
     * Recherche des utilisateurs par préférence
     *
     * @param string $key Clé de la préférence
     * @param mixed $value Valeur de la préférence
     * @return array IDs des utilisateurs
     */
    public function findUserIdsByPreference(string $key, $value): array
    {
        $filter = ["preferences.$key" => $value];
        $options = ['projection' => ['_id' => 0, 'user_id' => 1]];
        
        $cursor = $this->collection->find($filter, $options);
        
        $userIds = [];
        
        foreach ($cursor as $document) {
            $userIds[] = $document['user_id'];
        }
        
        return $userIds;
    }
    
    /**
     * Supprime une préférence pour tous les utilisateurs
     *
     * @param string $key Clé de la préférence
     * @return int Nombre de documents mis à jour
     */
    public function removePreferenceForAllUsers(string $key): int
    {
        $result = $this->collection->updateMany(
            ["preferences.$key" => ['$exists' => true]],
            ['$unset' => ["preferences.$key" => ""]]
        );
        
        return $result->getModifiedCount();
    }
    
    /**
     * Récupère les préférences de plusieurs utilisateurs
     *
     * @param array $userIds IDs des utilisateurs
     * @return array Tableau d'objets UserPreferences
     */
    public function findByUserIds(array $userIds): array
    {
        $cursor = $this->collection->find(['user_id' => ['$in' => $userIds]]);
        
        $preferences = [];
        
        foreach ($cursor as $document) {
            $preferences[] = new UserPreferences(
                $document['user_id'],
                (array) $document['preferences'],
                $document['updated_at'] ?? null
            );
        }
        
        return $preferences;
    }
} 