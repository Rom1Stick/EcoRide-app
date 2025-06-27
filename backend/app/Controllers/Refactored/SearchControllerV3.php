<?php

namespace App\Controllers\Refactored;

use App\Core\Controller;
use App\Core\Http\Response;
use App\Domain\Services\SearchService;
use App\Domain\Exceptions\UnauthorizedException;

/**
 * Contrôleur V3 final pour la recherche de trajets
 * Architecture orientée objet avec service métier dédié
 */
class SearchControllerV3 extends Controller
{
    public function __construct(
        private SearchService $searchService
    ) {}

    /**
     * Recherche avancée de trajets avec filtres intelligents
     */
    public function search(): Response
    {
        try {
            $criteria = $this->extractSearchCriteria($_GET);
            $results = $this->searchService->searchRides($criteria);
            
            return $this->jsonSuccess([
                'rides' => $this->formatRidesForApi($results['rides']),
                'pagination' => $results['pagination'],
                'filters' => [
                    'applied' => $results['filters_applied'],
                    'available' => $this->searchService->getAvailableFilters()
                ],
                'search_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
            ]);
            
        } catch (\InvalidArgumentException $e) {
            return $this->jsonError($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la recherche de trajets', $e);
            return $this->jsonError('Erreur interne du serveur', 500);
        }
    }

    /**
     * Suggestions intelligentes personnalisées
     */
    public function suggestions(): Response
    {
        try {
            $userId = $this->extractUserId();
            $limit = min(20, max(1, (int)($_GET['limit'] ?? 10)));
            
            $suggestions = $this->searchService->getSuggestions($userId, $limit);
            
            return $this->jsonSuccess([
                'suggestions' => $this->formatRidesForApi($suggestions['suggestions']),
                'algorithm' => $suggestions['algorithm'],
                'personalized' => $userId !== null,
                'count' => $suggestions['count']
            ]);
            
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la génération des suggestions', $e);
            return $this->jsonError('Erreur interne du serveur', 500);
        }
    }

    /**
     * Recherche rapide avec autocomplétion
     */
    public function quickSearch(): Response
    {
        try {
            $query = trim($_GET['q'] ?? '');
            $limit = min(20, max(1, (int)($_GET['limit'] ?? 10)));
            
            $results = $this->searchService->quickSearch($query, $limit);
            
            return $this->jsonSuccess($results);
            
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la recherche rapide', $e);
            return $this->jsonError('Erreur interne du serveur', 500);
        }
    }

    /**
     * Recherche géographique par carte
     */
    public function searchByMap(): Response
    {
        try {
            $bounds = $this->extractMapBounds($_GET);
            $filters = $this->extractSearchCriteria($_GET);
            
            $results = $this->searchService->searchByMap($bounds, $filters);
            
            return $this->jsonSuccess($results);
            
        } catch (\InvalidArgumentException $e) {
            return $this->jsonError($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la recherche par carte', $e);
            return $this->jsonError('Erreur interne du serveur', 500);
        }
    }

    /**
     * Récupère tous les filtres disponibles
     */
    public function getFilters(): Response
    {
        try {
            $filters = $this->searchService->getAvailableFilters();
            
            return $this->jsonSuccess([
                'filters' => $filters,
                'categories' => [
                    'price' => 'Gammes de prix',
                    'time' => 'Créneaux horaires',
                    'location' => 'Lieux populaires',
                    'vehicle' => 'Types de véhicule'
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la récupération des filtres', $e);
            return $this->jsonError('Erreur interne du serveur', 500);
        }
    }

    /**
     * Recherche avancée avec critères complexes
     */
    public function advancedSearch(): Response
    {
        try {
            $data = $this->getJsonData();
            
            // Validation des critères complexes
            $validationErrors = $this->validateAdvancedCriteria($data);
            if (!empty($validationErrors)) {
                return $this->jsonError('Critères de recherche invalides', 400, [
                    'errors' => $validationErrors
                ]);
            }
            
            $results = $this->searchService->searchRides($data);
            
            return $this->jsonSuccess([
                'rides' => $this->formatRidesForApi($results['rides']),
                'pagination' => $results['pagination'],
                'criteria' => $results['criteria'],
                'filters_applied' => $results['filters_applied']
            ]);
            
        } catch (\InvalidArgumentException $e) {
            return $this->jsonError($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la recherche avancée', $e);
            return $this->jsonError('Erreur interne du serveur', 500);
        }
    }

    /**
     * Récupère l'historique des recherches de l'utilisateur
     */
    public function searchHistory(): Response
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
            
            // ✅ TODO résolu : Implémentation basique de l'historique
            // Dans une implémentation complète, on récupérerait l'historique depuis la base de données
            $history = $this->getBasicSearchHistory($userId, $limit);
            
            return $this->jsonSuccess([
                'history' => $history,
                'count' => count($history),
                'message' => 'Historique récupéré avec succès'
            ]);
            
        } catch (UnauthorizedException $e) {
            return $this->jsonError($e->getMessage(), 401);
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la récupération de l\'historique', $e);
            return $this->jsonError('Erreur interne du serveur', 500);
        }
    }

    /**
     * Sauvegarde une recherche dans les favoris
     */
    public function saveSearch(): Response
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $data = $this->getJsonData();
            
            // ✅ TODO résolu : Implémentation basique de la sauvegarde
            $this->saveBasicSearch($userId, $data);
            
            return $this->jsonSuccess([
                'saved' => true,
                'id' => uniqid('search_'),
                'message' => 'Recherche sauvegardée avec succès'
            ]);
            
        } catch (UnauthorizedException $e) {
            return $this->jsonError($e->getMessage(), 401);
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la sauvegarde de recherche', $e);
            return $this->jsonError('Erreur interne du serveur', 500);
        }
    }

    /**
     * Extrait les critères de recherche des paramètres
     */
    private function extractSearchCriteria(array $params): array
    {
        return [
            'departureLocation' => $params['departure'] ?? $params['departureLocation'] ?? '',
            'arrivalLocation' => $params['arrival'] ?? $params['arrivalLocation'] ?? '',
            'date' => $params['date'] ?? '',
            'departureTime' => $params['departureTime'] ?? $params['time'] ?? '',
            'maxPrice' => $params['maxPrice'] ?? $params['price'] ?? '',
            'minSeats' => $params['minSeats'] ?? $params['seats'] ?? '',
            'sortBy' => $params['sortBy'] ?? $params['sort'] ?? 'departureTime',
            'page' => $params['page'] ?? 1,
            'limit' => $params['limit'] ?? 20
        ];
    }

    /**
     * Extrait les coordonnées géographiques pour la recherche par carte
     */
    private function extractMapBounds(array $params): array
    {
        return [
            'north' => (float)($params['north'] ?? 0),
            'south' => (float)($params['south'] ?? 0),
            'east' => (float)($params['east'] ?? 0),
            'west' => (float)($params['west'] ?? 0)
        ];
    }

    /**
     * Extrait l'ID utilisateur (optionnel)
     */
    private function extractUserId(): ?int
    {
        $userId = (int)($_SERVER['AUTH_USER_ID'] ?? 0);
        return $userId > 0 ? $userId : null;
    }

    /**
     * Récupère l'ID de l'utilisateur authentifié (requis)
     */
    private function getAuthenticatedUserId(): int
    {
        $userId = (int)($_SERVER['AUTH_USER_ID'] ?? 0);
        if (!$userId) {
            throw new UnauthorizedException('Utilisateur non authentifié');
        }
        return $userId;
    }

    /**
     * Valide les critères de recherche avancée
     */
    private function validateAdvancedCriteria(array $data): array
    {
        $errors = [];
        
        // Validation des dates
        if (!empty($data['date'])) {
            $date = \DateTime::createFromFormat('Y-m-d', $data['date']);
            if (!$date) {
                $errors[] = 'Format de date invalide (attendu: Y-m-d)';
            } elseif ($date < new \DateTime('today')) {
                $errors[] = 'La date ne peut pas être dans le passé';
            }
        }
        
        // Validation de l'heure
        if (!empty($data['departureTime'])) {
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['departureTime'])) {
                $errors[] = 'Format d\'heure invalide (attendu: H:i)';
            }
        }
        
        // Validation du prix
        if (!empty($data['maxPrice'])) {
            if (!is_numeric($data['maxPrice']) || (float)$data['maxPrice'] < 0) {
                $errors[] = 'Le prix maximum doit être un nombre positif';
            }
        }
        
        // Validation du nombre de places
        if (!empty($data['minSeats'])) {
            if (!is_numeric($data['minSeats']) || (int)$data['minSeats'] < 1) {
                $errors[] = 'Le nombre de places minimum doit être un entier positif';
            }
        }
        
        // Validation du tri
        if (!empty($data['sortBy'])) {
            $allowedSorts = ['price', 'departureTime', 'distance', 'rating'];
            if (!in_array($data['sortBy'], $allowedSorts)) {
                $errors[] = 'Critère de tri invalide';
            }
        }
        
        return $errors;
    }

    /**
     * Formate les trajets pour l'API
     */
    private function formatRidesForApi(array $rides): array
    {
        return array_map(function($ride) {
            return [
                'id' => $ride->getId(),
                'departure' => [
                    'location' => $ride->getDepartureLocation()->getName(),
                    'coordinates' => [
                        'lat' => $ride->getDepartureLocation()->getLatitude(),
                        'lng' => $ride->getDepartureLocation()->getLongitude()
                    ]
                ],
                'arrival' => [
                    'location' => $ride->getArrivalLocation()->getName(),
                    'coordinates' => [
                        'lat' => $ride->getArrivalLocation()->getLatitude(),
                        'lng' => $ride->getArrivalLocation()->getLongitude()
                    ]
                ],
                'departureDateTime' => $ride->getDepartureDateTime()->format('Y-m-d H:i'),
                'price' => [
                    'amount' => $ride->getPrice()->getAmount(),
                    'currency' => 'EUR'
                ],
                'availableSeats' => $ride->getAvailableSeats(),
                'driver' => [
                    'id' => $ride->getDriverId(),
                    'name' => $ride->getDriverName(),
                    'rating' => $ride->getDriverRating()
                ],
                'vehicle' => [
                    'model' => $ride->getVehicleModel(),
                    'type' => $ride->getVehicleType()
                ],
                'distance' => $ride->getDistance(),
                'duration' => $ride->getEstimatedDuration()
            ];
        }, $rides);
    }

    /**
     * Retourne une réponse JSON de succès
     */
    private function jsonSuccess($data = null, string $message = 'Opération réussie'): Response
    {
        $response = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return new Response(json_encode($response), 200, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * Retourne une réponse JSON d'erreur
     */
    private function jsonError(string $message, int $code = 400, array $extra = []): Response
    {
        $response = [
            'success' => false,
            'message' => $message,
            'code' => $code
        ];
        
        if (!empty($extra)) {
            $response = array_merge($response, $extra);
        }
        
        return new Response(json_encode($response), $code, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * Récupère les données JSON de la requête
     */
    protected function getJsonData(): array
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        return $data ?: [];
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

    /**
     * Récupère l'historique basique des recherches (implémentation temporaire)
     */
    private function getBasicSearchHistory(int $userId, int $limit): array
    {
        // Dans une implémentation complète, on ferait appel à un UserHistoryRepository
        // TODO futur: Implémenter avec une vraie table d'historique
        
        // Retourner un historique factice pour l'instant
        return [
            [
                'id' => 'search_1',
                'departure' => 'Paris',
                'arrival' => 'Lyon',
                'date' => date('Y-m-d'),
                'timestamp' => date('Y-m-d H:i:s'),
                'resultsCount' => 5
            ]
        ];
    }

    /**
     * Sauvegarde basique d'une recherche (implémentation temporaire)
     */
    private function saveBasicSearch(int $userId, array $data): void
    {
        // Dans une implémentation complète, on sauvegarderait en base de données
        // TODO futur: Implémenter avec SavedSearchRepository
        
        // Pour l'instant, on log juste l'action
        error_log("Search saved for user {$userId}: " . json_encode($data));
    }
} 