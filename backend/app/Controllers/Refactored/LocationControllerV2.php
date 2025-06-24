<?php

namespace App\Controllers\Refactored;

use App\Core\Controller;
use App\Core\Http\Response;
use App\Domain\Services\LocationService;

/**
 * Contrôleur V2 orienté objet pour la gestion des lieux
 */
class LocationControllerV2 extends Controller
{
    public function __construct(
        private LocationService $locationService
    ) {}

    /**
     * Recherche de lieux
     */
    public function search(): Response
    {
        try {
            $query = $_GET['q'] ?? '';
            $locations = $this->locationService->searchLocations($query);
            
            return $this->json([
                'success' => true,
                'locations' => $locations
            ]);
            
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la recherche de lieux', $e);
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche'
            ], 500);
        }
    }

    /**
     * Récupération des lieux populaires
     */
    public function getPopular(): Response
    {
        try {
            $locations = $this->locationService->getPopularLocations();
            
            return $this->json([
                'success' => true,
                'locations' => $locations
            ]);
            
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la récupération des lieux populaires', $e);
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des lieux populaires'
            ], 500);
        }
    }

    /**
     * Détails d'un lieu spécifique
     */
    public function show(int $locationId): Response
    {
        try {
            $location = $this->locationService->getLocationDetails($locationId);
            
            if (!$location) {
                return $this->json([
                    'success' => false,
                    'message' => 'Lieu non trouvé'
                ], 404);
            }
            
            return $this->json([
                'success' => true,
                'location' => $location->toArray()
            ]);
            
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la récupération du lieu', $e);
            return $this->json([
                'success' => false,
                'message' => 'Erreur interne du serveur'
            ], 500);
        }
    }

    /**
     * Création ou récupération d'un lieu
     */
    public function store(): Response
    {
        try {
            $data = $this->getRequestData();
            
            // Validation
            if (empty($data['name'])) {
                return $this->json([
                    'success' => false,
                    'message' => 'Le nom du lieu est requis'
                ], 400);
            }
            
            $location = $this->locationService->getOrCreateLocation(
                $data['name'],
                $data['address'] ?? null
            );
            
            return $this->json([
                'success' => true,
                'location' => $location->toArray(),
                'message' => 'Lieu créé ou récupéré avec succès'
            ]);
            
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la création/récupération du lieu', $e);
            return $this->json([
                'success' => false,
                'message' => 'Erreur interne du serveur'
            ], 500);
        }
    }

    /**
     * Mise à jour des coordonnées d'un lieu
     */
    public function updateCoordinates(int $locationId): Response
    {
        try {
            $data = $this->getRequestData();
            
            // Validation
            if (!isset($data['latitude']) || !isset($data['longitude'])) {
                return $this->json([
                    'success' => false,
                    'message' => 'Latitude et longitude sont requises'
                ], 400);
            }
            
            $latitude = (float)$data['latitude'];
            $longitude = (float)$data['longitude'];
            
            // Validation des coordonnées
            if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                return $this->json([
                    'success' => false,
                    'message' => 'Coordonnées invalides'
                ], 400);
            }
            
            $success = $this->locationService->updateLocationCoordinates($locationId, $latitude, $longitude);
            
            if ($success) {
                return $this->json([
                    'success' => true,
                    'message' => 'Coordonnées mises à jour avec succès'
                ]);
            } else {
                return $this->json([
                    'success' => false,
                    'message' => 'Lieu non trouvé'
                ], 404);
            }
            
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la mise à jour des coordonnées', $e);
            return $this->json([
                'success' => false,
                'message' => 'Erreur interne du serveur'
            ], 500);
        }
    }

    /**
     * Calcul de distance entre deux lieux
     */
    public function calculateDistance(): Response
    {
        try {
            $fromLocationId = (int)($_GET['from'] ?? 0);
            $toLocationId = (int)($_GET['to'] ?? 0);
            
            if (!$fromLocationId || !$toLocationId) {
                return $this->json([
                    'success' => false,
                    'message' => 'IDs des lieux de départ et d\'arrivée requis'
                ], 400);
            }
            
            $distance = $this->locationService->calculateDistance($fromLocationId, $toLocationId);
            
            if ($distance === null) {
                return $this->json([
                    'success' => false,
                    'message' => 'Impossible de calculer la distance'
                ], 400);
            }
            
            return $this->json([
                'success' => true,
                'distance' => round($distance, 2),
                'unit' => 'km'
            ]);
            
        } catch (\Exception $e) {
            $this->logError('Erreur lors du calcul de distance', $e);
            return $this->json([
                'success' => false,
                'message' => 'Erreur interne du serveur'
            ], 500);
        }
    }

    /**
     * Lieux populaires comme points de départ
     */
    public function getPopularDepartures(): Response
    {
        try {
            $limit = min((int)($_GET['limit'] ?? 10), 20); // Max 20
            $locations = $this->locationService->getPopularDepartureLocations($limit);
            
            return $this->json([
                'success' => true,
                'locations' => $locations,
                'count' => count($locations)
            ]);
            
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la récupération des lieux de départ populaires', $e);
            return $this->json([
                'success' => false,
                'message' => 'Erreur interne du serveur'
            ], 500);
        }
    }

    /**
     * Lieux populaires comme destinations
     */
    public function getPopularDestinations(): Response
    {
        try {
            $limit = min((int)($_GET['limit'] ?? 10), 20); // Max 20
            $locations = $this->locationService->getPopularDestinationLocations($limit);
            
            return $this->json([
                'success' => true,
                'locations' => $locations,
                'count' => count($locations)
            ]);
            
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la récupération des destinations populaires', $e);
            return $this->json([
                'success' => false,
                'message' => 'Erreur interne du serveur'
            ], 500);
        }
    }

    /**
     * Récupère les données de la requête
     */
    private function getRequestData(): array
    {
        // Essayer JSON d'abord
        $json = file_get_contents('php://input');
        if ($json) {
            $data = json_decode($json, true);
            if ($data) {
                return $data;
            }
        }
        
        // Fallback sur POST/GET
        return array_merge($_GET, $_POST);
    }

    /**
     * Retourne une réponse JSON
     */
    private function json(array $data, int $status = 200): Response
    {
        return new Response(json_encode($data), $status, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * Log une erreur avec contexte
     */
    private function logError(string $message, \Exception $e): void
    {
        error_log(sprintf(
            '[%s] %s: %s in %s:%d',
            date('Y-m-d H:i:s'),
            $message,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));
    }
} 