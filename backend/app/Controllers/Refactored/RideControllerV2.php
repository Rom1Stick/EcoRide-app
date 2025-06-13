<?php

namespace App\Controllers\Refactored;

use App\Controllers\Controller;
use App\Domain\Repositories\RideRepositoryInterface;
use App\Domain\Services\RideManagementService;
use App\Domain\Entities\Ride;
use App\Domain\Entities\User;
use App\Domain\ValueObjects\Location;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\Email;
use App\Domain\Enums\RideStatus;
use App\Domain\Exceptions\RideNotFoundException;
use App\Domain\Exceptions\BookingException;
use App\Domain\Exceptions\UnauthorizedException;
use App\Infrastructure\Factories\RepositoryFactory;
use App\Infrastructure\Repositories\MySQLLocationRepository;
use App\Core\Logger;
use App\Core\Validator;
use DateTime;
use Exception;

/**
 * Contrôleur de trajets refactorisé - Architecture Orientée Objet
 * 
 * Cette version utilise les repositories, entités Domain et services métier
 * pour une séparation claire des responsabilités et une meilleure maintenabilité.
 */
class RideControllerV2 extends Controller
{
    private RideRepositoryInterface $rideRepository;
    private MySQLLocationRepository $locationRepository;
    private RideManagementService $rideManagementService;
    private Logger $logger;

    public function __construct()
    {
        parent::__construct();
        
        // Initialisation du logger
        $logPath = BASE_PATH . '/logs/rides_v2.log';
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
        
        $this->logger->info('RideControllerV2 initialisé avec architecture OO');
    }

    /**
     * Liste des trajets disponibles avec recherche avancée
     */
    public function index(): array
    {
        try {
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
            
            // Comptage pour pagination
            $total = $this->rideRepository->countSearchResults($departure, $arrival, $date);
            $pages = ceil($total / $limit);
            
            $this->logger->info('Recherche de trajets réussie', [
                'count' => count($rides),
                'total' => $total,
                'page' => $page,
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
            $ride = $this->rideRepository->findById($id);
            
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
     * Création d'un nouveau trajet
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
            
            $this->logger->info('Nouveau trajet créé', [
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
     * Mise à jour d'un trajet existant
     */
    public function update(int $id): array
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
            
            // Vérification des droits (propriétaire du trajet)
            if ($ride->getDriver()->getId() !== $currentUserId) {
                throw new UnauthorizedException('Seul le propriétaire peut modifier ce trajet');
            }
            
            $data = $this->getJsonData();
            $validationErrors = $this->validateRideData($data, true);
            
            if (!empty($validationErrors)) {
                return $this->error([
                    'message' => 'Données invalides',
                    'errors' => $validationErrors
                ], 400);
            }
            
            // Mise à jour via le service métier
            $updatedRide = $this->rideManagementService->updateRide($ride, $data);
            
            $this->logger->info('Trajet mis à jour', [
                'ride_id' => $id,
                'user_id' => $currentUserId
            ]);
            
            return $this->success(
                $this->formatRideForApi($updatedRide),
                'Trajet mis à jour avec succès'
            );
            
        } catch (RideNotFoundException | UnauthorizedException $e) {
            return $this->error($e->getMessage(), $e instanceof UnauthorizedException ? 403 : 404);
            
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la mise à jour du trajet', [
                'ride_id' => $id,
                'error' => $e->getMessage()
            ]);
            return $this->error('Erreur lors de la mise à jour du trajet', 500);
        }
    }

    /**
     * Suppression d'un trajet
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
            
            // Suppression via le service métier (avec vérifications business)
            $this->rideManagementService->cancelRide($ride);
            
            $this->logger->info('Trajet supprimé', [
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
     * Trajets de l'utilisateur connecté
     */
    public function getMyRides(): array
    {
        try {
            $currentUserId = $this->getCurrentUserId();
            if (!$currentUserId) {
                throw new UnauthorizedException('Utilisateur non authentifié');
            }
            
            // Création d'un objet User temporaire (dans une implémentation complète, 
            // on récupérerait l'utilisateur via un UserRepository)
            $currentUser = new User(
                $currentUserId,
                $_SERVER['AUTH_USER_NAME'] ?? 'Utilisateur',
                new Email($_SERVER['AUTH_USER_EMAIL'] ?? 'user@example.com'),
                'dummy_hash'
            );
            
            $rides = $this->rideRepository->findByDriver($currentUser);
            
            $this->logger->info('Trajets utilisateur récupérés', [
                'user_id' => $currentUserId,
                'count' => count($rides)
            ]);
            
            return $this->success([
                'rides' => array_map([$this, 'formatRideForApi'], $rides)
            ]);
            
        } catch (UnauthorizedException $e) {
            return $this->error($e->getMessage(), 401);
            
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la récupération des trajets utilisateur', [
                'user_id' => $this->getCurrentUserId(),
                'error' => $e->getMessage()
            ]);
            return $this->error('Erreur lors de la récupération de vos trajets', 500);
        }
    }

    /**
     * Trajets populaires/recommandés
     */
    public function popular(): array
    {
        try {
            $limit = min(20, max(1, (int) ($_GET['limit'] ?? 10)));
            
            $popularRides = $this->rideRepository->findPopularRides($limit);
            
            return $this->success([
                'rides' => array_map([$this, 'formatRideForApi'], $popularRides)
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la récupération des trajets populaires', [
                'error' => $e->getMessage()
            ]);
            return $this->error('Erreur lors de la récupération des trajets populaires', 500);
        }
    }

    // =============================================================================
    // MÉTHODES PRIVÉES - UTILITAIRES
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
        
        // Ajouter des détails supplémentaires pour la vue détaillée
        $basic['canBook'] = $ride->canAcceptBooking();
        $basic['isBookable'] = $ride->getAvailableSeats() > 0 && $ride->getStatus() === RideStatus::PLANNED;
        $basic['estimatedDuration'] = $this->calculateDuration($ride->getDepartureDateTime(), $ride->getArrivalDateTime());
        
        return $basic;
    }

    /**
     * Récupère une location depuis un paramètre
     */
    private function getLocationFromParam(string $paramName): ?Location
    {
        $locationName = $_GET[$paramName] ?? null;
        if (!$locationName) {
            return null;
        }
        
        return $this->locationRepository->findByName($locationName);
    }

    /**
     * Récupère une date depuis un paramètre
     */
    private function getDateFromParam(string $paramName): ?DateTime
    {
        $dateString = $_GET[$paramName] ?? null;
        if (!$dateString) {
            return null;
        }
        
        try {
            return new DateTime($dateString);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Validation des données de trajet
     */
    private function validateRideData(array $data, bool $isUpdate = false): array
    {
        $validator = new Validator($data);
        
        if (!$isUpdate || isset($data['departure'])) {
            $validator->required('departure', 'Le lieu de départ est requis');
        }
        
        if (!$isUpdate || isset($data['destination'])) {
            $validator->required('destination', 'Le lieu d\'arrivée est requis');
        }
        
        if (!$isUpdate || isset($data['date'])) {
            $validator->required('date', 'La date est requise');
            $validator->date('date', 'Format de date invalide');
        }
        
        if (!$isUpdate || isset($data['departureTime'])) {
            $validator->required('departureTime', 'L\'heure de départ est requise');
        }
        
        if (!$isUpdate || isset($data['price'])) {
            $validator->required('price', 'Le prix est requis');
            $validator->numeric('price', 'Le prix doit être un nombre');
            $validator->min('price', 0, 'Le prix ne peut pas être négatif');
        }
        
        if (!$isUpdate || isset($data['totalSeats'])) {
            $validator->required('totalSeats', 'Le nombre de places est requis');
            $validator->integer('totalSeats', 'Le nombre de places doit être un nombre entier');
            $validator->min('totalSeats', 1, 'Le nombre de places doit être d\'au moins 1');
            $validator->max('totalSeats', 8, 'Le nombre de places ne peut pas dépasser 8');
        }
        
        return $validator->isValid() ? [] : $validator->getErrors();
    }

    /**
     * Calcule l'heure d'arrivée estimée
     */
    private function calculateArrivalDateTime(DateTime $departureDateTime): DateTime
    {
        // Estimation simple : ajouter 2 heures
        // Dans une vraie app, on utiliserait une API de routing comme Google Maps
        $arrivalDateTime = clone $departureDateTime;
        $arrivalDateTime->add(new \DateInterval('PT2H'));
        return $arrivalDateTime;
    }

    /**
     * Calcule la durée entre deux DateTime
     */
    private function calculateDuration(DateTime $start, DateTime $end): string
    {
        $interval = $start->diff($end);
        return $interval->format('%h heures %i minutes');
    }

    /**
     * Récupère l'ID de l'utilisateur connecté
     */
    private function getCurrentUserId(): ?int
    {
        return (int) ($_SERVER['AUTH_USER_ID'] ?? 0) ?: null;
    }

    /**
     * Vérifie si l'utilisateur a le rôle chauffeur
     */
    private function isDriverRole(): bool
    {
        $userRoles = $_SERVER['AUTH_USER_ROLES'] ?? [];
        
        if (is_array($userRoles)) {
            return in_array('chauffeur', $userRoles);
        }
        
        return ($_SERVER['AUTH_USER_ROLE'] ?? '') === 'chauffeur';
    }
} 