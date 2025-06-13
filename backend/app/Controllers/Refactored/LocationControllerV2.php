<?php

namespace App\Controllers\Refactored;

use App\Controllers\Controller;
use App\Infrastructure\Repositories\MySQLLocationRepository;
use App\Infrastructure\Factories\RepositoryFactory;
use App\Core\Logger;
use Exception;

/**
 * Contrôleur de lieux refactorisé - Architecture Orientée Objet
 * 
 * Cette version utilise le LocationRepository pour une gestion optimisée
 * des lieux avec cache et logique métier encapsulée.
 */
class LocationControllerV2 extends Controller
{
    private MySQLLocationRepository $locationRepository;
    private Logger $logger;

    public function __construct()
    {
        parent::__construct();
        
        // Initialisation du logger
        $logPath = BASE_PATH . '/logs/locations_v2.log';
        $this->logger = new Logger($logPath);
        
        // Création du repository via la factory
        $repositoryFactory = RepositoryFactory::createFromLegacyDatabase(
            $this->app->getDatabase(),
            $this->logger
        );
        
        $this->locationRepository = $repositoryFactory->createLocationRepository();
        
        $this->logger->info('LocationControllerV2 initialisé avec architecture OO');
    }

    /**
     * Recherche de lieux avec intelligence artificielle de tri
     */
    public function search(): array
    {
        try {
            // Récupération et validation du terme de recherche
            $query = trim($_GET['q'] ?? '');
            $limit = min(20, max(1, (int) ($_GET['limit'] ?? 10)));
            
            if (empty($query) || strlen($query) < 2) {
                // Si pas de recherche, retourner les lieux populaires
                $popularLocations = $this->locationRepository->findMostPopular($limit);
                
                $this->logger->info('Lieux populaires retournés', [
                    'count' => count($popularLocations)
                ]);
                
                return $this->success([
                    'locations' => array_map([$this, 'formatLocationForApi'], $popularLocations),
                    'type' => 'popular'
                ]);
            }
            
            // Recherche avec le repository
            $locations = $this->locationRepository->searchByName($query, $limit);
            
            $this->logger->info('Recherche de lieux effectuée', [
                'query' => $query,
                'count' => count($locations),
                'limit' => $limit
            ]);
            
            return $this->success([
                'locations' => array_map([$this, 'formatLocationForApi'], $locations),
                'query' => $query,
                'type' => 'search'
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la recherche de lieux', [
                'query' => $query ?? '',
                'error' => $e->getMessage()
            ]);
            return $this->error('Erreur lors de la recherche des lieux', 500);
        }
    }

    /**
     * Récupération des lieux populaires
     */
    public function getPopular(): array
    {
        try {
            $limit = min(20, max(1, (int) ($_GET['limit'] ?? 8)));
            
            $popularLocations = $this->locationRepository->findMostPopular($limit);
            
            $this->logger->info('Lieux populaires récupérés', [
                'count' => count($popularLocations)
            ]);
            
            return $this->success([
                'locations' => array_map([$this, 'formatLocationForApi'], $popularLocations),
                'type' => 'popular'
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la récupération des lieux populaires', [
                'error' => $e->getMessage()
            ]);
            return $this->error('Erreur lors de la récupération des lieux populaires', 500);
        }
    }

    /**
     * Détails d'un lieu spécifique
     */
    public function show(int $id): array
    {
        try {
            $location = $this->locationRepository->findById($id);
            
            if (!$location) {
                return $this->error('Lieu non trouvé', 404);
            }
            
            $this->logger->info('Détails lieu récupérés', ['location_id' => $id]);
            
            return $this->success($this->formatLocationDetailsForApi($location));
            
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la récupération du lieu', [
                'location_id' => $id,
                'error' => $e->getMessage()
            ]);
            return $this->error('Erreur lors de la récupération du lieu', 500);
        }
    }

    /**
     * Création ou récupération d'un lieu (utilisé par l'autocomplétion)
     */
    public function findOrCreate(): array
    {
        try {
            $data = $this->getJsonData();
            
            if (empty($data['name'])) {
                return $this->error('Le nom du lieu est requis', 400);
            }
            
            $locationName = trim($data['name']);
            
            // Vérifier si le lieu existe déjà
            $existingLocation = $this->locationRepository->findByName($locationName);
            if ($existingLocation) {
                return $this->success([
                    'location' => $this->formatLocationForApi($existingLocation),
                    'created' => false
                ]);
            }
            
            // Créer un nouveau lieu
            $newLocation = $this->locationRepository->findOrCreate($locationName);
            
            $this->logger->info('Nouveau lieu créé', [
                'location_id' => $newLocation->getId(),
                'name' => $locationName
            ]);
            
            return $this->success([
                'location' => $this->formatLocationForApi($newLocation),
                'created' => true
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la création/recherche du lieu', [
                'error' => $e->getMessage(),
                'data' => $data ?? []
            ]);
            return $this->error('Erreur lors du traitement du lieu', 500);
        }
    }

    /**
     * Mise à jour des coordonnées d'un lieu
     */
    public function updateCoordinates(int $id): array
    {
        try {
            $data = $this->getJsonData();
            
            if (!isset($data['latitude']) || !isset($data['longitude'])) {
                return $this->error('Les coordonnées latitude et longitude sont requises', 400);
            }
            
            $latitude = (float) $data['latitude'];
            $longitude = (float) $data['longitude'];
            
            // Validation des coordonnées
            if ($latitude < -90 || $latitude > 90) {
                return $this->error('La latitude doit être comprise entre -90 et 90', 400);
            }
            
            if ($longitude < -180 || $longitude > 180) {
                return $this->error('La longitude doit être comprise entre -180 et 180', 400);
            }
            
            // Vérifier que le lieu existe
            $location = $this->locationRepository->findById($id);
            if (!$location) {
                return $this->error('Lieu non trouvé', 404);
            }
            
            // Mise à jour des coordonnées
            $this->locationRepository->updateCoordinates($id, $latitude, $longitude);
            
            // Récupération du lieu mis à jour
            $updatedLocation = $this->locationRepository->findById($id);
            
            $this->logger->info('Coordonnées lieu mises à jour', [
                'location_id' => $id,
                'latitude' => $latitude,
                'longitude' => $longitude
            ]);
            
            return $this->success([
                'location' => $this->formatLocationDetailsForApi($updatedLocation)
            ], 'Coordonnées mises à jour avec succès');
            
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la mise à jour des coordonnées', [
                'location_id' => $id,
                'error' => $e->getMessage()
            ]);
            return $this->error('Erreur lors de la mise à jour des coordonnées', 500);
        }
    }

    /**
     * Liste paginée de tous les lieux
     */
    public function index(): array
    {
        try {
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = min(100, max(1, (int) ($_GET['limit'] ?? 20)));
            
            $locations = $this->locationRepository->findAll($page, $limit);
            $total = $this->locationRepository->count();
            $pages = ceil($total / $limit);
            
            $this->logger->info('Liste lieux récupérée', [
                'count' => count($locations),
                'page' => $page,
                'total' => $total
            ]);
            
            return $this->success([
                'locations' => array_map([$this, 'formatLocationForApi'], $locations),
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => $pages
                ]
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la récupération de la liste des lieux', [
                'error' => $e->getMessage()
            ]);
            return $this->error('Erreur lors de la récupération de la liste des lieux', 500);
        }
    }

    /**
     * Suggestions intelligentes basées sur l'historique
     */
    public function suggestions(): array
    {
        try {
            $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
            $limit = min(10, max(1, (int) ($_GET['limit'] ?? 5)));
            
            // Pour l'instant, on retourne les lieux populaires
            // Dans une version avancée, on analyserait l'historique de l'utilisateur
            $suggestedLocations = $this->locationRepository->findMostPopular($limit);
            
            $this->logger->info('Suggestions lieux générées', [
                'user_id' => $userId ?: null,
                'count' => count($suggestedLocations)
            ]);
            
            return $this->success([
                'suggestions' => array_map([$this, 'formatLocationForApi'], $suggestedLocations),
                'type' => 'popularity_based'
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la génération des suggestions', [
                'error' => $e->getMessage()
            ]);
            return $this->error('Erreur lors de la génération des suggestions', 500);
        }
    }

    // =============================================================================
    // MÉTHODES PRIVÉES - FORMATAGE
    // =============================================================================

    /**
     * Formate une Location pour l'API
     */
    private function formatLocationForApi($location): array
    {
        return [
            'id' => $location->getId(),
            'name' => $location->getName(),
            'coordinates' => [
                'latitude' => $location->getLatitude(),
                'longitude' => $location->getLongitude()
            ],
            'hasCoordinates' => $location->getLatitude() !== null && $location->getLongitude() !== null
        ];
    }

    /**
     * Formate les détails complets d'une Location pour l'API
     */
    private function formatLocationDetailsForApi($location): array
    {
        $basic = $this->formatLocationForApi($location);
        
        // Ajouter des statistiques d'utilisation si nécessaire
        // $basic['usage_stats'] = $this->getLocationUsageStats($location->getId());
        
        return $basic;
    }
} 