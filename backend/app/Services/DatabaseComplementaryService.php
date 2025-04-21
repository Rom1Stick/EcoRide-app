<?php

namespace App\Services;

use App\Repositories\SQL\UserRepository;
use App\Repositories\SQL\TripRepository;
use App\Repositories\NoSQL\ReviewRepository;
use App\Repositories\NoSQL\UserPreferencesRepository;
use MongoDB\Client as MongoClient;
use Illuminate\Database\Connection;

/**
 * Service gérant la complémentarité entre MySQL et MongoDB
 */
class DatabaseComplementaryService
{
    private UserRepository $userRepository;
    private TripRepository $tripRepository;
    private ReviewRepository $reviewRepository;
    private UserPreferencesRepository $preferencesRepository;
    private Connection $sqlConnection;
    private MongoClient $noSqlClient;
    
    /**
     * Constructeur
     *
     * @param UserRepository $userRepository Repository utilisateur SQL
     * @param TripRepository $tripRepository Repository trajet SQL
     * @param ReviewRepository $reviewRepository Repository avis NoSQL
     * @param UserPreferencesRepository $preferencesRepository Repository préférences NoSQL
     * @param Connection $sqlConnection Connexion SQL
     * @param string $mongoUri URI de connexion MongoDB
     */
    public function __construct(
        UserRepository $userRepository,
        TripRepository $tripRepository,
        ReviewRepository $reviewRepository,
        UserPreferencesRepository $preferencesRepository,
        Connection $sqlConnection,
        string $mongoUri
    ) {
        $this->userRepository = $userRepository;
        $this->tripRepository = $tripRepository;
        $this->reviewRepository = $reviewRepository;
        $this->preferencesRepository = $preferencesRepository;
        $this->sqlConnection = $sqlConnection;
        $this->noSqlClient = new MongoClient($mongoUri);
    }
    
    /**
     * Récupère les données complètes d'un utilisateur (SQL + NoSQL)
     *
     * @param int $userId ID de l'utilisateur
     * @return array Données utilisateur complètes
     */
    public function getCompleteUserData(int $userId): array
    {
        // Récupérer les données de base de l'utilisateur depuis MySQL
        $userData = $this->userRepository->findById($userId);
        
        if (!$userData) {
            return [];
        }
        
        // Convertir en tableau associatif si ce n'est pas déjà le cas
        if (is_object($userData) && method_exists($userData, 'toArray')) {
            $userData = $userData->toArray();
        }
        
        // Enrichir avec les préférences depuis MongoDB
        $preferences = $this->preferencesRepository->findByUserId($userId);
        if ($preferences) {
            $userData['preferences'] = $preferences->getAllPreferences();
        }
        
        // Enrichir avec la notation moyenne
        $rating = $this->reviewRepository->getAverageRatingForUser($userId);
        if ($rating) {
            $userData['rating'] = $rating;
        }
        
        return $userData;
    }
    
    /**
     * Récupère les données complètes d'un trajet (SQL + NoSQL)
     *
     * @param int $tripId ID du trajet
     * @return array Données trajet complètes
     */
    public function getCompleteTripData(int $tripId): array
    {
        // Récupérer les données de base du trajet depuis MySQL
        $tripData = $this->tripRepository->findById($tripId);
        
        if (!$tripData) {
            return [];
        }
        
        // Convertir en tableau associatif si ce n'est pas déjà le cas
        if (is_object($tripData) && method_exists($tripData, 'toArray')) {
            $tripData = $tripData->toArray();
        }
        
        // Enrichir avec les avis depuis MongoDB
        $tripData['reviews'] = $this->reviewRepository->findByTripId($tripId);
        
        return $tripData;
    }
    
    /**
     * Supprime complètement un utilisateur des deux bases de données
     *
     * @param int $userId ID de l'utilisateur
     * @return bool Succès de l'opération
     */
    public function deleteUserCompletely(int $userId): bool
    {
        try {
            // Commencer une transaction SQL
            $this->sqlConnection->beginTransaction();
            
            // Supprimer l'utilisateur dans MySQL
            $sqlSuccess = $this->userRepository->delete($userId);
            
            if (!$sqlSuccess) {
                $this->sqlConnection->rollBack();
                return false;
            }
            
            // Supprimer les préférences dans MongoDB
            $preferencesSuccess = $this->preferencesRepository->deleteByUserId($userId);
            
            // Valider la transaction SQL
            $this->sqlConnection->commit();
            
            return true;
        } catch (\Exception $e) {
            // Annuler la transaction SQL en cas d'erreur
            $this->sqlConnection->rollBack();
            return false;
        }
    }
    
    /**
     * Trouve des trajets recommandés pour un utilisateur basés sur ses préférences
     *
     * @param int $userId ID de l'utilisateur
     * @param int $limit Nombre de trajets à récupérer
     * @return array Trajets recommandés
     */
    public function getRecommendedTripsForUser(int $userId, int $limit = 5): array
    {
        // Récupérer les préférences de l'utilisateur
        $preferences = $this->preferencesRepository->findByUserId($userId);
        
        if (!$preferences || empty($preferences->preferences)) {
            // Aucune préférence, retourner des trajets populaires
            return $this->tripRepository->findPopularTrips($limit);
        }
        
        // Récupérer les préférences de trajets
        $tripPreferences = [];
        foreach ($preferences->preferences as $key => $value) {
            if (strpos($key, 'trip_') === 0) {
                $tripPreferences[substr($key, 5)] = $value;
            }
        }
        
        if (empty($tripPreferences)) {
            // Aucune préférence de trajet, retourner des trajets populaires
            return $this->tripRepository->findPopularTrips($limit);
        }
        
        // Trouver des trajets correspondant aux préférences
        $recommendedTrips = $this->tripRepository->findByPreferences($tripPreferences, $limit);
        
        // Si pas assez de trajets recommandés, compléter avec des trajets populaires
        if (count($recommendedTrips) < $limit) {
            $additionalTrips = $this->tripRepository->findPopularTrips($limit - count($recommendedTrips));
            
            // Éviter les doublons
            $recommendedTripIds = array_column($recommendedTrips, 'id');
            foreach ($additionalTrips as $trip) {
                if (!in_array($trip['id'], $recommendedTripIds)) {
                    $recommendedTrips[] = $trip;
                }
            }
        }
        
        return $recommendedTrips;
    }
    
    /**
     * Trouve des utilisateurs recommandés pour un utilisateur basés sur ses préférences
     *
     * @param int $userId ID de l'utilisateur
     * @param int $limit Nombre d'utilisateurs à récupérer
     * @return array Utilisateurs recommandés
     */
    public function getRecommendedUsersForUser(int $userId, int $limit = 5): array
    {
        // Trouver des utilisateurs avec des préférences similaires
        $similarUserIds = $this->preferencesRepository->findSimilarUsers($userId, $limit);
        
        if (empty($similarUserIds)) {
            return [];
        }
        
        // Récupérer les détails complets des utilisateurs similaires
        $recommendedUsers = [];
        foreach ($similarUserIds as $similarUserId) {
            $userData = $this->getCompleteUserData($similarUserId);
            if (!empty($userData)) {
                $recommendedUsers[] = $userData;
            }
            
            if (count($recommendedUsers) >= $limit) {
                break;
            }
        }
        
        return $recommendedUsers;
    }
    
    /**
     * Synchronise les données entre MySQL et MongoDB
     *
     * @return bool Succès de l'opération
     */
    public function syncDatabases(): bool
    {
        try {
            // 1. Synchroniser les IDs d'utilisateurs
            $this->syncUserIds();
            
            // 2. Synchroniser les IDs de trajets
            $this->syncTripIds();
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Synchronise les IDs d'utilisateurs entre MySQL et MongoDB
     *
     * @return void
     */
    private function syncUserIds(): void
    {
        // Récupérer tous les utilisateurs de MySQL
        $sqlUsers = $this->userRepository->findAll();
        $sqlUserIds = [];
        
        foreach ($sqlUsers as $user) {
            $sqlUserIds[] = $user['id'];
        }
        
        // Récupérer tous les IDs d'utilisateurs dans les préférences MongoDB
        $collection = $this->noSqlClient->selectDatabase('ecoride_nosql')->selectCollection('user_preferences');
        $cursor = $collection->find([], ['projection' => ['user_id' => 1]]);
        
        $mongoUserIds = [];
        foreach ($cursor as $document) {
            $mongoUserIds[] = $document['user_id'];
        }
        
        // Supprimer les préférences des utilisateurs qui n'existent plus dans MySQL
        foreach ($mongoUserIds as $mongoUserId) {
            if (!in_array($mongoUserId, $sqlUserIds)) {
                $this->preferencesRepository->deleteByUserId($mongoUserId);
            }
        }
    }
    
    /**
     * Synchronise les IDs de trajets entre MySQL et MongoDB
     *
     * @return void
     */
    private function syncTripIds(): void
    {
        // Récupérer tous les trajets de MySQL
        $sqlTrips = $this->tripRepository->findAll();
        $sqlTripIds = [];
        
        foreach ($sqlTrips as $trip) {
            $sqlTripIds[] = $trip['id'];
        }
        
        // Récupérer tous les IDs de trajets dans les avis MongoDB
        $collection = $this->noSqlClient->selectDatabase('ecoride_nosql')->selectCollection('reviews');
        $cursor = $collection->find(['trip_id' => ['$exists' => true]], ['projection' => ['trip_id' => 1]]);
        
        $documentsToUpdate = [];
        foreach ($cursor as $document) {
            if (!in_array($document['trip_id'], $sqlTripIds)) {
                $documentsToUpdate[] = $document['_id'];
            }
        }
        
        // Mettre à jour les documents avec des IDs de trajets non valides
        foreach ($documentsToUpdate as $documentId) {
            $collection->updateOne(
                ['_id' => $documentId],
                ['$unset' => ['trip_id' => '']]
            );
        }
    }
} 