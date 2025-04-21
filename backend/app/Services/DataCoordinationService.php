<?php

namespace App\Services;

use App\Repositories\SQL\UserRepository;
use App\Repositories\SQL\TripRepository;
use App\Repositories\NoSQL\ReviewRepository;
use App\Repositories\NoSQL\UserPreferencesRepository;
use App\Repositories\NoSQL\ActivityLogRepository;
use App\Models\Entities\User;
use App\Models\Entities\Trip;
use App\Models\Entities\Review;

/**
 * Service responsable de coordonner les données entre MySQL et MongoDB
 * Assure la complémentarité entre les deux bases de données
 */
class DataCoordinationService
{
    private UserRepository $userRepository;
    private TripRepository $tripRepository;
    private ReviewRepository $reviewRepository;
    private UserPreferencesRepository $userPreferencesRepository;
    private ActivityLogRepository $activityLogRepository;
    
    /**
     * Constructeur
     */
    public function __construct(
        UserRepository $userRepository,
        TripRepository $tripRepository,
        ReviewRepository $reviewRepository,
        UserPreferencesRepository $userPreferencesRepository,
        ActivityLogRepository $activityLogRepository
    ) {
        $this->userRepository = $userRepository;
        $this->tripRepository = $tripRepository;
        $this->reviewRepository = $reviewRepository;
        $this->userPreferencesRepository = $userPreferencesRepository;
        $this->activityLogRepository = $activityLogRepository;
    }
    
    /**
     * Récupère un utilisateur avec ses préférences NoSQL
     * 
     * @param int $userId ID de l'utilisateur
     * @return array Données utilisateur combinées
     */
    public function getUserWithPreferences(int $userId): array
    {
        // Récupérer les données utilisateur de MySQL
        $user = $this->userRepository->findById($userId);
        
        if (!$user) {
            return [];
        }
        
        // Convertir l'utilisateur en tableau
        $userData = $user->toArray();
        
        // Récupérer les préférences utilisateur de MongoDB
        $preferences = $this->userPreferencesRepository->findByUserId($userId);
        
        // Ajouter les préférences aux données utilisateur
        $userData['preferences'] = $preferences ? $preferences->preferences : null;
        
        return $userData;
    }
    
    /**
     * Récupère un trajet avec les avis MongoDB associés
     * 
     * @param int $tripId ID du trajet
     * @return array Données du trajet avec avis
     */
    public function getTripWithReviews(int $tripId): array
    {
        // Récupérer les données du trajet depuis MySQL
        $trip = $this->tripRepository->findById($tripId);
        
        if (!$trip) {
            return [];
        }
        
        // Convertir le trajet en tableau
        $tripData = $trip->toArray();
        
        // Récupérer les avis sur ce trajet depuis MongoDB
        $reviews = $this->reviewRepository->findByTripId($tripId);
        
        // Convertir les objets Review en tableaux
        $reviewsArray = array_map(function($review) {
            return $review->toArray();
        }, $reviews);
        
        // Ajouter les avis aux données du trajet
        $tripData['reviews'] = $reviewsArray;
        
        // Calculer et ajouter la note moyenne
        $averageRating = $this->reviewRepository->calculateAverageRatingForTrip($tripId);
        $tripData['average_rating'] = $averageRating;
        
        return $tripData;
    }
    
    /**
     * Enregistre un nouvel avis et met à jour les logs d'activité
     * 
     * @param Review $review Avis à enregistrer
     * @return string|null ID de l'avis créé ou null si échec
     */
    public function saveReviewAndLogActivity(Review $review): ?string
    {
        // Vérifier que l'utilisateur existe dans MySQL
        $user = $this->userRepository->findById($review->user_id);
        if (!$user) {
            return null;
        }
        
        // Vérifier que le trajet existe dans MySQL
        $trip = $this->tripRepository->findById($review->trip_id);
        if (!$trip) {
            return null;
        }
        
        // Enregistrer l'avis dans MongoDB
        $reviewId = $this->reviewRepository->create($review);
        
        if ($reviewId) {
            // Logger l'activité dans MongoDB
            $this->activityLogRepository->logActivity(
                $review->user_id,
                'add_review',
                [
                    'trip_id' => $review->trip_id,
                    'rating' => $review->rating,
                    'review_id' => $reviewId
                ]
            );
        }
        
        return $reviewId;
    }
    
    /**
     * Récupère le tableau de bord complet d'un utilisateur avec données des deux bases
     * 
     * @param int $userId ID de l'utilisateur
     * @return array Données du tableau de bord
     */
    public function getUserDashboard(int $userId): array
    {
        // Vérifier que l'utilisateur existe
        $user = $this->userRepository->findById($userId);
        
        if (!$user) {
            return [];
        }
        
        $dashboard = [];
        
        // Données utilisateur de base (MySQL)
        $dashboard['user'] = $user->toArray();
        
        // Préférences utilisateur (MongoDB)
        $preferences = $this->userPreferencesRepository->findByUserId($userId);
        $dashboard['preferences'] = $preferences ? $preferences->preferences : null;
        
        // Trajets récents (MySQL)
        $recentTrips = $this->tripRepository->findRecentByUserId($userId, 5);
        $dashboard['recent_trips'] = array_map(function($trip) {
            return $trip->toArray();
        }, $recentTrips);
        
        // Avis donnés récemment (MongoDB)
        $recentReviews = $this->reviewRepository->findByUserId($userId, 5);
        $dashboard['recent_reviews'] = array_map(function($review) {
            return $review->toArray();
        }, $recentReviews);
        
        // Activités récentes (MongoDB)
        $recentActivities = $this->activityLogRepository->findRecentByUserId($userId, 10);
        $dashboard['activity_logs'] = $recentActivities;
        
        // Statistiques diverses (combinant les deux sources)
        $dashboard['stats'] = [
            'total_trips' => $this->tripRepository->countByUserId($userId),
            'average_rating' => $this->reviewRepository->calculateAverageRatingForUser($userId),
            'completed_trips_count' => $this->tripRepository->countCompletedByUserId($userId),
            'canceled_trips_count' => $this->tripRepository->countCanceledByUserId($userId),
        ];
        
        return $dashboard;
    }
    
    /**
     * Récupère les recommandations de trajets pour un utilisateur basées sur ses préférences
     * 
     * @param int $userId ID de l'utilisateur
     * @return array Liste des trajets recommandés
     */
    public function getRecommendedTrips(int $userId): array
    {
        // Récupérer les préférences utilisateur depuis MongoDB
        $preferences = $this->userPreferencesRepository->findByUserId($userId);
        
        if (!$preferences) {
            // Si pas de préférences, retourner des recommandations génériques
            return $this->tripRepository->findAvailableTrips(10);
        }
        
        // Utiliser les préférences pour filtrer les trajets (par exemple distance, horaire)
        $userPrefs = $preferences->preferences;
        
        // Récupérer l'historique des trajets depuis MySQL
        $tripHistory = $this->tripRepository->findHistoryByUserId($userId);
        
        // Analyser l'historique pour identifier les tendances
        $commonCities = $this->analyzeCommonDestinations($tripHistory);
        
        // Récupérer les trajets filtrés selon préférences et historique
        $recommendedTrips = $this->tripRepository->findRecommended($userId, $commonCities, $userPrefs);
        
        return $recommendedTrips;
    }
    
    /**
     * Analyse les destinations fréquentes d'un utilisateur
     * 
     * @param array $tripHistory Historique des trajets
     * @return array Villes les plus fréquentes
     */
    private function analyzeCommonDestinations(array $tripHistory): array
    {
        $cities = [];
        
        foreach ($tripHistory as $trip) {
            if (isset($cities[$trip->arrival_city])) {
                $cities[$trip->arrival_city]++;
            } else {
                $cities[$trip->arrival_city] = 1;
            }
        }
        
        // Trier par fréquence décroissante
        arsort($cities);
        
        // Retourner les 3 villes les plus fréquentes
        return array_slice(array_keys($cities), 0, 3);
    }
} 