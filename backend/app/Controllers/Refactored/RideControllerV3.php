<?php

namespace App\Controllers\Refactored;

use App\Controllers\Controller;
use App\Core\Container\ContainerInterface;
use App\Domain\Repositories\RideRepositoryInterface;
use App\Domain\Services\RideManagementService;
use App\Infrastructure\Repositories\MySQLLocationRepository;
use App\Domain\Entities\Ride;
use App\Domain\Entities\User;
use App\Domain\ValueObjects\Location;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\Email;
use App\Domain\Enums\RideStatus;
use App\Domain\Exceptions\RideNotFoundException;
use App\Domain\Exceptions\BookingException;
use App\Domain\Exceptions\UnauthorizedException;
use App\Core\Logger;
use App\Core\Validator;
use DateTime;
use Exception;

/**
 * RideController V3 - Avec Container d'Injection de Dépendances
 * 
 * Cette version utilise le container DI pour une gestion automatique
 * des dépendances et une meilleure testabilité.
 */
class RideControllerV3 extends Controller
{
    private RideRepositoryInterface $rideRepository;
    private MySQLLocationRepository $locationRepository;
    private RideManagementService $rideManagementService;
    private Logger $logger;
    private ContainerInterface $container;

    /**
     * Constructeur avec injection automatique des dépendances
     */
    public function __construct(
        ContainerInterface $container,
        RideRepositoryInterface $rideRepository,
        MySQLLocationRepository $locationRepository,
        RideManagementService $rideManagementService,
        Logger $logger
    ) {
        parent::__construct();
        
        $this->container = $container;
        $this->rideRepository = $rideRepository;
        $this->locationRepository = $locationRepository;
        $this->rideManagementService = $rideManagementService;
        $this->logger = $logger;
        
        $this->logger->info('RideControllerV3 initialisé avec container DI');
    }

    /**
     * Liste des trajets disponibles avec recherche avancée
     */
    public function index(): array
    {
        try {
            // Utilisation du profiler si disponible
            $profiler = $this->container->get('profiler');
            $profiler->start('rides_index');

            // Paramètres de pagination
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int) ($_GET['limit'] ?? 10)));
            
            // Critères de recherche
            $departure = $this->getLocationFromParam('departure');
            $arrival = $this->getLocationFromParam('arrival');
            $date = $this->getDateFromParam('date');
            $sortBy = $_GET['sort'] ?? 'departureTime';
            
            // Recherche via le service métier
            $rides = $this->rideManagementService->searchRides(
                $departure,
                $arrival,
                $date,
                $sortBy,
                $page,
                $limit
            );
            
            // Utilisation du cache si disponible
            $cache = $this->container->get('cache');
            $cacheKey = "rides_count_{$departure?->getId()}_{$arrival?->getId()}_{$date?->format('Y-m-d')}";
            
            $total = $cache->get($cacheKey);
            if ($total === null) {
                $total = $this->rideRepository->countSearchResults($departure, $arrival, $date);
                $cache->set($cacheKey, $total, 300); // Cache 5 minutes
            }
            
            $pages = ceil($total / $limit);
            
            $executionTime = $profiler->stop('rides_index');
            
            $this->logger->info('Recherche de trajets réussie', [
                'count' => count($rides),
                'total' => $total,
                'page' => $page,
                'execution_time' => $executionTime,
                'criteria' => [
                    'departure' => $departure?->getName(),
                    'arrival' => $arrival?->getName(),
                    'date' => $date?->format('Y-m-d')
                ]
            ]);
            
            return $this->success([
                'rides' => array_map([$this, 'formatRideForApi'], $rides),
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => $pages
                ],
                'meta' => [
                    'execution_time' => $executionTime,
                    'cache_used' => true
                ]
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la recherche de trajets', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('Erreur lors de la récupération des trajets', 500);
        }
    }

    /**
     * Détails d'un trajet spécifique
     */
    public function show(int $id): array
    {
        try {
            // Utilisation du cache pour les détails du trajet
            $cache = $this->container->get('cache');
            $cacheKey = "ride_details_{$id}";
            
            $ride = $cache->get($cacheKey);
            if ($ride === null) {
                $ride = $this->rideRepository->findById($id);
                if ($ride) {
                    $cache->set($cacheKey, $ride, 600); // Cache 10 minutes
                }
            }
            
            if (!$ride) {
                throw new RideNotFoundException("Trajet avec l'ID $id non trouvé");
            }
            
            $this->logger->info('Détails trajet récupérés', ['ride_id' => $id]);
            
            return $this->success($this->formatRideDetailsForApi($ride));
            
        } catch (RideNotFoundException $e) {
            $this->logger->warning('Trajet non trouvé', ['ride_id' => $id]);
            return $this->error('Trajet non trouvé', 404);
            
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la récupération du trajet', [
                'ride_id' => $id,
                'error' => $e->getMessage()
            ]);
            return $this->error('Erreur lors de la récupération du trajet', 500);
        }
    }

    /**
     * Création d'un nouveau trajet avec notification
     */
    public function store(): array
    {
        try {
            // Vérification de l'authentification
            $currentUserId = $this->getCurrentUserId();
            if (!$currentUserId) {
                throw new UnauthorizedException('Utilisateur non authentifié');
            }
            
            if (!$this->isDriverRole()) {
                throw new UnauthorizedException('Seuls les chauffeurs peuvent créer des trajets');
            }
            
            // Récupération et validation des données
            $data = $this->getJsonData();
            $validationErrors = $this->validateRideData($data);
            
            if (!empty($validationErrors)) {
                return $this->error([
                    'message' => 'Données invalides',
                    'errors' => $validationErrors
                ], 400);
            }
            
            // Création des Value Objects
            $departure = $this->locationRepository->findOrCreate($data['departure']);
            $arrival = $this->locationRepository->findOrCreate($data['destination']);
            $pricePerPerson = new Money((float) $data['price']);
            
            // Création des DateTime
            $departureDateTime = new DateTime($data['date'] . ' ' . $data['departureTime']);
            $arrivalDateTime = $this->calculateArrivalDateTime($departureDateTime);
            
            // Création de l'entité Ride via le service métier
            $ride = $this->rideManagementService->createRide(
                $departure,
                $arrival,
                $departureDateTime,
                $arrivalDateTime,
                $pricePerPerson,
                (int) $data['totalSeats'],
                $currentUserId
            );
            
            // Envoi de notification via le service de notification
            $notificationService = $this->container->get('notification');
            $notificationService->sendEmail(
                $_SERVER['AUTH_USER_EMAIL'] ?? 'user@example.com',
                'Trajet créé avec succès',
                "Votre trajet de {$departure->getName()} à {$arrival->getName()} a été créé."
            );
            
            // Invalidation du cache
            $cache = $this->container->get('cache');
            $cache->forget("rides_count_{$departure->getId()}_{$arrival->getId()}_*");
            
            $this->logger->info('Nouveau trajet créé avec notification', [
                'ride_id' => $ride->getId(),
                'driver_id' => $currentUserId,
                'departure' => $departure->getName(),
                'arrival' => $arrival->getName()
            ]);
            
            return $this->success(
                $this->formatRideForApi($ride),
                'Trajet créé avec succès'
            );
            
        } catch (UnauthorizedException $e) {
            $this->logger->warning('Tentative de création non autorisée', [
                'user_id' => $this->getCurrentUserId(),
                'error' => $e->getMessage()
            ]);
            return $this->error($e->getMessage(), 403);
            
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la création du trajet', [
                'error' => $e->getMessage(),
                'data' => $data ?? []
            ]);
            return $this->error('Erreur lors de la création du trajet', 500);
        }
    }

    /**
     * Suppression d'un trajet avec notification
     */
    public function destroy(int $id): array
    {
        try {
            $currentUserId = $this->getCurrentUserId();
            if (!$currentUserId) {
                throw new UnauthorizedException('Utilisateur non authentifié');
            }
            
            $ride = $this->rideRepository->findById($id);
            if (!$ride) {
                throw new RideNotFoundException("Trajet avec l'ID $id non trouvé");
            }
            
            // Vérification des droits
            if ($ride->getDriver()->getId() !== $currentUserId) {
                throw new UnauthorizedException('Seul le propriétaire peut supprimer ce trajet');
            }
            
            // Suppression via le service métier
            $this->rideManagementService->cancelRide($ride);
            
            // Notification de suppression
            $notificationService = $this->container->get('notification');
            $notificationService->sendEmail(
                $ride->getDriver()->getEmail()->getValue(),
                'Trajet annulé',
                "Votre trajet de {$ride->getDeparture()->getName()} à {$ride->getArrival()->getName()} a été annulé."
            );
            
            // Invalidation du cache
            $cache = $this->container->get('cache');
            $cache->forget("ride_details_{$id}");
            $cache->forget("rides_count_*");
            
            $this->logger->info('Trajet supprimé avec notification', [
                'ride_id' => $id,
                'user_id' => $currentUserId
            ]);
            
            return $this->success(null, 'Trajet supprimé avec succès');
            
        } catch (RideNotFoundException | UnauthorizedException | BookingException $e) {
            $statusCode = match(true) {
                $e instanceof UnauthorizedException => 403,
                $e instanceof RideNotFoundException => 404,
                $e instanceof BookingException => 409,
                default => 400
            };
            return $this->error($e->getMessage(), $statusCode);
            
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la suppression du trajet', [
                'ride_id' => $id,
                'error' => $e->getMessage()
            ]);
            return $this->error('Erreur lors de la suppression du trajet', 500);
        }
    }

    /**
     * Statistiques des trajets avec cache
     */
    public function stats(): array
    {
        try {
            $cache = $this->container->get('cache');
            $cacheKey = 'ride_stats';
            
            $stats = $cache->get($cacheKey);
            if ($stats === null) {
                // Calcul des statistiques
                $stats = [
                    'total_rides' => $this->rideRepository->countTotal(),
                    'available_rides' => count($this->rideRepository->findAvailableRides(1000)),
                    'popular_destinations' => $this->locationRepository->findMostPopular(5),
                    'generated_at' => date('Y-m-d H:i:s')
                ];
                
                $cache->set($cacheKey, $stats, 1800); // Cache 30 minutes
            }
            
            return $this->success($stats);
            
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la récupération des statistiques', [
                'error' => $e->getMessage()
            ]);
            return $this->error('Erreur lors de la récupération des statistiques', 500);
        }
    }

    /**
     * Debug du container (développement uniquement)
     */
    public function debugContainer(): array
    {
        if (env('APP_ENV', 'production') === 'production') {
            return $this->error('Debug non disponible en production', 403);
        }
        
        try {
            $debug = $this->container->get('debug');
            
            return $this->success([
                'container_stats' => $debug->getContainerStats(),
                'profiler_timers' => $this->container->get('profiler')->getTimers(),
                'services' => array_keys($this->container->getBindings())
            ]);
            
        } catch (Exception $e) {
            return $this->error('Erreur lors du debug', 500);
        }
    }

    // =============================================================================
    // MÉTHODES PRIVÉES - UTILITAIRES (identiques à V2)
    // =============================================================================

    /**
     * Formate une entité Ride pour l'API
     */
    private function formatRideForApi(Ride $ride): array
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
            'arrivalTime' => $ride->getArrivalDateTime()->format('Y-m-d H:i:s'),
            'price' => $ride->getPricePerPerson()->getAmount(),
            'currency' => $ride->getPricePerPerson()->getCurrency(),
            'seats' => [
                'total' => $ride->getTotalSeats(),
                'available' => $ride->getAvailableSeats()
            ],
            'driver' => [
                'id' => $ride->getDriver()->getId(),
                'username' => $ride->getDriver()->getUsername(),
                'rating' => $ride->getDriver()->getAverageRating(),
                'totalRatings' => $ride->getDriver()->getTotalRatings(),
                'profilePicture' => $ride->getDriver()->getProfilePicture()
            ],
            'status' => $ride->getStatus()->value,
            'carbonFootprint' => $ride->getCarbonFootprint()
        ];
    }

    /**
     * Formate les détails complets d'un trajet pour l'API
     */
    private function formatRideDetailsForApi(Ride $ride): array
    {
        $basic = $this->formatRideForApi($ride);
        
        // Ajouter des détails supplémentaires
        $basic['canBook'] = $ride->canAcceptBooking();
        $basic['isBookable'] = $ride->getAvailableSeats() > 0 && $ride->getStatus() === RideStatus::PLANNED;
        $basic['estimatedDuration'] = $this->calculateDuration($ride->getDepartureDateTime(), $ride->getArrivalDateTime());
        
        return $basic;
    }

    // Autres méthodes utilitaires identiques à V2...
    private function getLocationFromParam(string $paramName): ?Location { /* ... */ }
    private function getDateFromParam(string $paramName): ?DateTime { /* ... */ }
    private function validateRideData(array $data, bool $isUpdate = false): array { /* ... */ }
    private function calculateArrivalDateTime(DateTime $departureDateTime): DateTime { /* ... */ }
    private function calculateDuration(DateTime $start, DateTime $end): string { /* ... */ }
    private function getCurrentUserId(): ?int { /* ... */ }
    private function isDriverRole(): bool { /* ... */ }
} 