<?php

namespace App\Controllers\Refactored;

use App\Controllers\Controller;
use App\Domain\Repositories\RideRepositoryInterface;
use App\Domain\Services\RideManagementService;
use App\Infrastructure\Repositories\MySQLLocationRepository;
use App\Infrastructure\Factories\RepositoryFactory;
use App\Core\Validator;
use App\Core\Logger;
use DateTime;
use Exception;

/**
 * Contrôleur de recherche refactorisé - Architecture Orientée Objet
 * 
 * Cette version utilise les repositories et services métier pour une recherche
 * avancée avec filtres intelligents et recommandations personnalisées.
 */
class SearchControllerV2 extends Controller
{
    private RideRepositoryInterface $rideRepository;
    private MySQLLocationRepository $locationRepository;
    private RideManagementService $rideManagementService;
    private Logger $logger;

    public function __construct()
    {
        parent::__construct();
        
        // Initialisation du logger
        $logPath = BASE_PATH . '/logs/search_v2.log';
        $this->logger = new Logger($logPath);
        
        // Création des repositories via la factory
        $repositoryFactory = RepositoryFactory::createFromLegacyDatabase(
            $this->app->getDatabase(),
            $this->logger
        );
        
        $this->rideRepository = $repositoryFactory->createRideRepository();
        $this->locationRepository = $repositoryFactory->createLocationRepository();
        
        // Initialisation du service métier
        $this->rideManagementService = new RideManagementService($this->rideRepository);
        
        $this->logger->info('SearchControllerV2 initialisé avec architecture OO');
    }

    /**
     * Recherche avancée de trajets avec filtres intelligents
     */
    public function search(): array
    {
        try {
            // Validation et extraction des paramètres
            $params = $this->validateSearchParams($_GET);
            if (isset($params['errors'])) {
                return $this->error([
                    'message' => 'Paramètres de recherche invalides',
                    'errors' => $params['errors']
                ], 400);
            }
            
            // Conversion des noms de lieux en entités Location
            $departure = null;
            $arrival = null;
            
            if (!empty($params['departureLocation'])) {
                $departure = $this->locationRepository->findByName($params['departureLocation']);
                if (!$departure) {
                    return $this->error('Lieu de départ non trouvé', 404);
                }
            }
            
            if (!empty($params['arrivalLocation'])) {
                $arrival = $this->locationRepository->findByName($params['arrivalLocation']);
                if (!$arrival) {
                    return $this->error('Lieu d\'arrivée non trouvé', 404);
                }
            }
            
            // Conversion de la date
            $date = !empty($params['date']) ? new DateTime($params['date']) : null;
            
            // Recherche via le service métier avec logique avancée
            $rides = $this->rideManagementService->searchRides(
                $departure,
                $arrival,
                $date,
                $params['sortBy'],
                $params['page'],
                $params['limit']
            );
            
            // Filtrage supplémentaire côté application si nécessaire
            $filteredRides = $this->applyAdvancedFilters($rides, $params);
            
            // Comptage pour pagination
            $total = $this->rideRepository->countSearchResults($departure, $arrival, $date);
            $pages = ceil($total / $params['limit']);
            
            $this->logger->info('Recherche avancée effectuée', [
                'criteria' => [
                    'departure' => $params['departureLocation'],
                    'arrival' => $params['arrivalLocation'],
                    'date' => $params['date'],
                    'maxPrice' => $params['maxPrice'],
                    'sortBy' => $params['sortBy']
                ],
                'results' => count($filteredRides),
                'total' => $total
            ]);
            
                         return $this->success([
                 'rides' => $this->formatRidesForSearch($filteredRides),
                 'pagination' => [
                     'total' => $total,
                     'page' => $params['page'],
                     'limit' => $params['limit'],
                     'pages' => $pages
                 ],
                 'filters' => [
                     'applied' => $this->getAppliedFilters($params),
                     'available' => $this->getAvailableFilters($filteredRides)
                 ]
             ]);
            
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la recherche avancée', [
                'error' => $e->getMessage(),
                'params' => $_GET
            ]);
            return $this->error('Erreur lors de la recherche de trajets', 500);
        }
    }

    /**
     * Suggestions intelligentes basées sur l'historique et les préférences
     */
    public function suggestions(): array
    {
        try {
            $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
            $limit = min(10, max(1, (int) ($_GET['limit'] ?? 5)));
            
            // Pour l'instant, suggestions basées sur la popularité
            // Dans une version avancée : analyse ML des préférences utilisateur
            $suggestedRides = $this->rideRepository->findPopularRides($limit);
            
            $this->logger->info('Suggestions générées', [
                'user_id' => $userId ?: null,
                'count' => count($suggestedRides)
            ]);
            
                         return $this->success([
                 'suggestions' => $this->formatRidesForSearch($suggestedRides),
                 'algorithm' => 'popularity_based',
                 'personalized' => $userId > 0
             ]);
            
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la génération des suggestions', [
                'error' => $e->getMessage()
            ]);
            return $this->error('Erreur lors de la génération des suggestions', 500);
        }
    }

    /**
     * Recherche rapide avec autocomplétion
     */
    public function quickSearch(): array
    {
        try {
            $query = trim($_GET['q'] ?? '');
            
            if (strlen($query) < 2) {
                return $this->success([
                    'rides' => [],
                    'locations' => []
                ]);
            }
            
            // Recherche parallèle de trajets et de lieux
            $locations = $this->locationRepository->searchByName($query, 5);
            $rides = $this->rideRepository->findAvailableRides(5);
            
            return $this->success([
                'locations' => array_map(function($location) {
                    return [
                        'id' => $location->getId(),
                        'name' => $location->getName(),
                        'type' => 'location'
                    ];
                }, $locations),
                'rides' => array_map(function($ride) {
                    return [
                        'id' => $ride->getId(),
                        'departure' => $ride->getDeparture()->getName(),
                        'arrival' => $ride->getArrival()->getName(),
                        'departureTime' => $ride->getDepartureDateTime()->format('Y-m-d H:i'),
                        'type' => 'ride'
                    ];
                }, $rides),
                'query' => $query
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la recherche rapide', [
                'query' => $query ?? '',
                'error' => $e->getMessage()
            ]);
            return $this->error('Erreur lors de la recherche rapide', 500);
        }
    }

    /**
     * Filtres dynamiques basés sur les résultats de recherche
     */
    public function getFilters(): array
    {
        try {
            // Récupération des filtres disponibles dans la base de données
            $priceRanges = $this->getPriceRanges();
            $departureTimeRanges = $this->getDepartureTimeRanges();
            $popularDestinations = $this->locationRepository->findMostPopular(10);
            
            return $this->success([
                'priceRanges' => $priceRanges,
                'timeRanges' => $departureTimeRanges,
                'popularDestinations' => array_map(function($location) {
                    return [
                        'id' => $location->getId(),
                        'name' => $location->getName()
                    ];
                }, $popularDestinations)
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la récupération des filtres', [
                'error' => $e->getMessage()
            ]);
            return $this->error('Erreur lors de la récupération des filtres', 500);
        }
    }

    /**
     * Recherche par carte/zone géographique
     */
    public function searchByMap(): array
    {
        try {
            $data = $this->getJsonData();
            
            if (!isset($data['bounds'])) {
                return $this->error('Les limites de la carte sont requises', 400);
            }
            
            $bounds = $data['bounds'];
            
            // Validation des coordonnées
            if (!$this->validateBounds($bounds)) {
                return $this->error('Limites de carte invalides', 400);
            }
            
            // Pour l'instant, on retourne tous les trajets disponibles
            // Dans une version avancée, on filtrerait par zone géographique
            $rides = $this->rideRepository->findAvailableRides(20);
            
            $this->logger->info('Recherche par carte effectuée', [
                'bounds' => $bounds,
                'results' => count($rides)
            ]);
            
                         return $this->success([
                 'rides' => $this->formatRidesForMap($rides),
                 'bounds' => $bounds
             ]);
            
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la recherche par carte', [
                'error' => $e->getMessage()
            ]);
            return $this->error('Erreur lors de la recherche par carte', 500);
        }
    }

    // =============================================================================
    // MÉTHODES PRIVÉES - VALIDATION ET UTILITAIRES
    // =============================================================================

    /**
     * Validation des paramètres de recherche
     */
    private function validateSearchParams(array $params): array
    {
        $validator = new Validator($params);
        
        // Validation des lieux (optionnels pour recherche flexible)
        if (!empty($params['departureLocation'])) {
            $validator->string('departureLocation', 'Le lieu de départ doit être une chaîne de caractères');
        }
        
        if (!empty($params['arrivalLocation'])) {
            $validator->string('arrivalLocation', 'Le lieu d\'arrivée doit être une chaîne de caractères');
        }
        
        // Validation de la date (optionnelle)
        if (!empty($params['date'])) {
            $validator->date('date', 'Format de date invalide (YYYY-MM-DD attendu)');
            
            try {
                $searchDate = new DateTime($params['date']);
                $today = new DateTime();
                if ($searchDate < $today->setTime(0, 0, 0)) {
                    $validator->addError('date', 'La date ne peut pas être dans le passé');
                }
            } catch (Exception $e) {
                $validator->addError('date', 'Format de date invalide');
            }
        }
        
        // Validation du prix maximum
        if (!empty($params['maxPrice'])) {
            $validator->numeric('maxPrice', 'Le prix maximum doit être un nombre');
            $validator->min('maxPrice', 0, 'Le prix maximum doit être positif');
        }
        
        // Validation du tri
        if (!empty($params['sortBy'])) {
            $validator->in('sortBy', ['departureTime', 'price', 'duration'], 'Critère de tri invalide');
        }
        
        // Validation de la pagination
        if (!empty($params['page'])) {
            $validator->integer('page', 'Le numéro de page doit être un entier');
            $validator->min('page', 1, 'Le numéro de page doit être supérieur à 0');
        }
        
        if (!empty($params['limit'])) {
            $validator->integer('limit', 'La limite doit être un entier');
            $validator->min('limit', 1, 'La limite doit être supérieure à 0');
            $validator->max('limit', 50, 'La limite ne peut pas dépasser 50');
        }
        
        if (!$validator->isValid()) {
            return ['errors' => $validator->getErrors()];
        }
        
        return [
            'departureLocation' => $params['departureLocation'] ?? null,
            'arrivalLocation' => $params['arrivalLocation'] ?? null,
            'date' => $params['date'] ?? null,
            'maxPrice' => !empty($params['maxPrice']) ? (float) $params['maxPrice'] : null,
            'sortBy' => $params['sortBy'] ?? 'departureTime',
            'page' => max(1, (int) ($params['page'] ?? 1)),
            'limit' => min(50, max(1, (int) ($params['limit'] ?? 10)))
        ];
    }

    /**
     * Application de filtres avancés côté application
     */
    private function applyAdvancedFilters(array $rides, array $params): array
    {
        if (empty($params['maxPrice'])) {
            return $rides;
        }
        
        return array_filter($rides, function($ride) use ($params) {
            return $ride->getPricePerPerson()->getAmount() <= $params['maxPrice'];
        });
    }

    /**
     * Formate plusieurs trajets pour la recherche
     */
    private function formatRidesForSearch(array $rides): array
    {
        return array_map([$this, 'formatRideForSearch'], $rides);
    }

    /**
     * Formate plusieurs trajets pour la carte
     */
    private function formatRidesForMap(array $rides): array
    {
        return array_map([$this, 'formatRideForMap'], $rides);
    }

    /**
     * Formatage d'un trajet pour la recherche
     */
    private function formatRideForSearch($ride): array
    {
        return [
            'id' => $ride->getId(),
            'departure' => [
                'location' => $ride->getDeparture()->getName(),
                'coordinates' => [
                    'lat' => $ride->getDeparture()->getLatitude(),
                    'lng' => $ride->getDeparture()->getLongitude()
                ]
            ],
            'arrival' => [
                'location' => $ride->getArrival()->getName(),
                'coordinates' => [
                    'lat' => $ride->getArrival()->getLatitude(),
                    'lng' => $ride->getArrival()->getLongitude()
                ]
            ],
            'departureTime' => $ride->getDepartureDateTime()->format('Y-m-d H:i:s'),
            'price' => $ride->getPricePerPerson()->getAmount(),
            'availableSeats' => $ride->getAvailableSeats(),
            'driver' => [
                'id' => $ride->getDriver()->getId(),
                'username' => $ride->getDriver()->getUsername(),
                'rating' => $ride->getDriver()->getAverageRating()
            ],
            'carbonFootprint' => $ride->getCarbonFootprint(),
            'canBook' => $ride->canAcceptBooking()
        ];
    }

    /**
     * Formatage d'un trajet pour la vue carte
     */
    private function formatRideForMap($ride): array
    {
        $basic = $this->formatRideForSearch($ride);
        
        // Ajout de données spécifiques à la carte
        $basic['route'] = [
            'departure' => $basic['departure']['coordinates'],
            'arrival' => $basic['arrival']['coordinates']
        ];
        
        return $basic;
    }

    /**
     * Obtient les filtres appliqués
     */
    private function getAppliedFilters(array $params): array
    {
        $applied = [];
        
        if (!empty($params['maxPrice'])) {
            $applied['maxPrice'] = $params['maxPrice'];
        }
        
        if (!empty($params['date'])) {
            $applied['date'] = $params['date'];
        }
        
        return $applied;
    }

    /**
     * Obtient les filtres disponibles basés sur les résultats
     */
    private function getAvailableFilters(array $rides): array
    {
        if (empty($rides)) {
            return [];
        }
        
        $prices = array_map(function($ride) {
            return $ride->getPricePerPerson()->getAmount();
        }, $rides);
        
        return [
            'priceRange' => [
                'min' => min($prices),
                'max' => max($prices)
            ],
            'availableSeats' => array_unique(array_map(function($ride) {
                return $ride->getAvailableSeats();
            }, $rides))
        ];
    }

    /**
     * Récupère les gammes de prix populaires
     */
    private function getPriceRanges(): array
    {
        return [
            ['min' => 0, 'max' => 10, 'label' => 'Moins de 10€'],
            ['min' => 10, 'max' => 25, 'label' => '10€ - 25€'],
            ['min' => 25, 'max' => 50, 'label' => '25€ - 50€'],
            ['min' => 50, 'max' => null, 'label' => 'Plus de 50€']
        ];
    }

    /**
     * Récupère les créneaux horaires populaires
     */
    private function getDepartureTimeRanges(): array
    {
        return [
            ['start' => '06:00', 'end' => '09:00', 'label' => 'Tôt le matin (6h-9h)'],
            ['start' => '09:00', 'end' => '12:00', 'label' => 'Matinée (9h-12h)'],
            ['start' => '12:00', 'end' => '14:00', 'label' => 'Midi (12h-14h)'],
            ['start' => '14:00', 'end' => '18:00', 'label' => 'Après-midi (14h-18h)'],
            ['start' => '18:00', 'end' => '21:00', 'label' => 'Soirée (18h-21h)'],
            ['start' => '21:00', 'end' => '06:00', 'label' => 'Nuit (21h-6h)']
        ];
    }

    /**
     * Validation des limites de carte
     */
    private function validateBounds(array $bounds): bool
    {
        $required = ['north', 'south', 'east', 'west'];
        
        foreach ($required as $key) {
            if (!isset($bounds[$key]) || !is_numeric($bounds[$key])) {
                return false;
            }
        }
        
        return true;
    }
} 