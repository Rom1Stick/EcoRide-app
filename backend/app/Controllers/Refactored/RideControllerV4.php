<?php

namespace App\Controllers\Refactored;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Container\SimpleContainerV2;
use App\Domain\Services\RideManagementService;
use App\Domain\Repositories\RideRepositoryInterface;
use App\Domain\Entities\Ride;
use App\Domain\Entities\User;
use App\Domain\ValueObjects\Location;
use App\Domain\ValueObjects\Money;
use App\Domain\Exceptions\RideNotFoundException;
use App\Domain\Exceptions\BookingException;
use App\Core\Logger;
use Exception;

/**
 * RideController V4 - Architecture Orientée Objet Complète
 * 
 * Version moderne utilisant les services Domain, les repositories
 * et l'injection de dépendances pour une architecture propre et maintenable.
 */
class RideControllerV4 extends Controller
{
    /**
     * Service de gestion métier des trajets
     */
    private RideManagementService $rideManagementService;
    
    /**
     * Repository pour l'accès aux données des trajets
     */
    private RideRepositoryInterface $rideRepository;
    
    /**
     * Logger pour le suivi des opérations
     */
    private Logger $logger;

    /**
     * Constructeur avec injection de dépendances
     */
    public function __construct(
        RideManagementService $rideManagementService = null,
        RideRepositoryInterface $rideRepository = null,
        Logger $logger = null
    ) {
        parent::__construct();

        // Si pas d'injection, utiliser le container DI
        if (!$rideManagementService || !$rideRepository || !$logger) {
            $container = new SimpleContainerV2();
            $container->configureForEcoRide();
            
            $this->rideManagementService = $rideManagementService ?? 
                $container->get('App\Domain\Services\RideManagementService');
            $this->rideRepository = $rideRepository ?? 
                $container->get('App\Domain\Repositories\RideRepositoryInterface');
            $this->logger = $logger ?? 
                $container->get('App\Core\Logger');
        } else {
            $this->rideManagementService = $rideManagementService;
            $this->rideRepository = $rideRepository;
            $this->logger = $logger;
        }
    }

    /**
     * Liste des trajets disponibles (GET /api/rides)
     */
    public function index(Request $request): Response
    {
        try {
            $page = (int) ($request->get('page') ?? 1);
            $limit = min((int) ($request->get('limit') ?? 10), 50); // Max 50 par page
            
            $this->logger->info("Recherche trajets - Page: {$page}, Limite: {$limit}");
            
            // Utiliser la méthode du repository avec pagination
            $rides = $this->rideRepository->findAvailableRides($limit, ($page - 1) * $limit);
            $total = $this->rideRepository->countAvailableRides();
            
            // Conversion en tableau pour la réponse API
            $ridesData = array_map(fn(Ride $ride) => $this->formatRideForApi($ride), $rides);
            
            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'rides' => $ridesData,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $total,
                        'total_pages' => ceil($total / $limit)
                    ]
                ]
            ]);

        } catch (Exception $e) {
            $this->logger->error('Erreur liste trajets: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la récupération des trajets', 500);
        }
    }

    /**
     * Détails d'un trajet (GET /api/rides/{id})
     */
    public function show(int $id): Response
    {
        try {
            $this->logger->info("Demande détails trajet ID: {$id}");
            
            $ride = $this->rideRepository->findById($id);
            
            if (!$ride) {
                throw new RideNotFoundException("Trajet avec ID {$id} non trouvé");
            }
            
            return $this->jsonResponse([
                'success' => true,
                'data' => $this->formatRideForApi($ride, true) // Mode détaillé
            ]);

        } catch (RideNotFoundException $e) {
            $this->logger->warning("Trajet non trouvé: {$e->getMessage()}");
            return $this->errorResponse('Trajet non trouvé', 404);
            
        } catch (Exception $e) {
            $this->logger->error('Erreur détails trajet: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la récupération du trajet', 500);
        }
    }

    /**
     * Création d'un nouveau trajet (POST /api/rides)
     */
    public function store(Request $request): Response
    {
        try {
            $data = $request->getJsonData();
            $this->logger->info('Création nouveau trajet', ['data' => $data]);
            
            // Validation des données d'entrée
            $validationResult = $this->validateRideData($data);
            if (!$validationResult['valid']) {
                return $this->errorResponse('Données invalides', 422, $validationResult['errors']);
            }
            
            // Création via le service métier (contient toute la logique)
            $ride = $this->rideManagementService->createRide(
                (int) $data['driver_id'],
                new Location(
                    $data['departure']['name'],
                    (float) $data['departure']['latitude'],
                    (float) $data['departure']['longitude'],
                    $data['departure']['address'] ?? ''
                ),
                new Location(
                    $data['arrival']['name'],
                    (float) $data['arrival']['latitude'],
                    (float) $data['arrival']['longitude'],
                    $data['arrival']['address'] ?? ''
                ),
                new \DateTime($data['departure_datetime']),
                (int) $data['available_seats'],
                new Money((float) $data['price_per_seat'], 'EUR'),
                (int) $data['vehicle_id']
            );
            
            $this->logger->info("Trajet créé avec succès ID: {$ride->getId()}");
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Trajet créé avec succès',
                'data' => $this->formatRideForApi($ride, true)
            ], 201);

        } catch (Exception $e) {
            $this->logger->error('Erreur création trajet: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la création du trajet', 500);
        }
    }

    /**
     * Réservation d'un trajet (POST /api/rides/{id}/book)
     */
    public function book(int $rideId, Request $request): Response
    {
        try {
            $data = $request->getJsonData();
            $userId = (int) $data['user_id'];
            $seatsRequested = (int) $data['seats_requested'];
            
            $this->logger->info("Réservation trajet ID: {$rideId} par utilisateur {$userId}");
            
            // Réservation via le service métier
            $booking = $this->rideManagementService->bookRide($rideId, $userId, $seatsRequested);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Réservation effectuée avec succès',
                'data' => [
                    'booking_id' => $booking->getId(),
                    'ride_id' => $rideId,
                    'seats_booked' => $seatsRequested,
                    'total_price' => $booking->getTotalPrice()->getAmount()
                ]
            ]);

        } catch (BookingException $e) {
            $this->logger->warning("Erreur réservation: {$e->getMessage()}");
            return $this->errorResponse($e->getMessage(), 422);
            
        } catch (RideNotFoundException $e) {
            return $this->errorResponse('Trajet non trouvé', 404);
            
        } catch (Exception $e) {
            $this->logger->error('Erreur réservation trajet: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la réservation', 500);
        }
    }

    /**
     * Recherche de trajets avec critères (POST /api/rides/search)
     */
    public function search(Request $request): Response
    {
        try {
            $criteria = $request->getJsonData();
            $this->logger->info('Recherche trajets avec critères', ['criteria' => $criteria]);
            
            // Recherche via le repository avec critères
            $rides = $this->rideRepository->findByCriteria([
                'departure_location' => $criteria['departure'] ?? null,
                'arrival_location' => $criteria['arrival'] ?? null,
                'departure_date' => $criteria['departure_date'] ?? null,
                'min_seats' => $criteria['min_seats'] ?? 1,
                'max_price' => $criteria['max_price'] ?? null
            ]);
            
            $ridesData = array_map(fn(Ride $ride) => $this->formatRideForApi($ride), $rides);
            
            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'rides' => $ridesData,
                    'total' => count($ridesData)
                ]
            ]);

        } catch (Exception $e) {
            $this->logger->error('Erreur recherche trajets: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la recherche', 500);
        }
    }

    /**
     * Formatage d'un trajet pour l'API
     */
    private function formatRideForApi(Ride $ride, bool $detailed = false): array
    {
        $data = [
            'id' => $ride->getId(),
            'departure' => [
                'location' => $ride->getDeparture()->getName(),
                'coordinates' => [
                    'latitude' => $ride->getDeparture()->getLatitude(),
                    'longitude' => $ride->getDeparture()->getLongitude()
                ],
                'datetime' => $ride->getDepartureDateTime()->format('Y-m-d H:i:s')
            ],
            'arrival' => [
                'location' => $ride->getArrival()->getName(),
                'coordinates' => [
                    'latitude' => $ride->getArrival()->getLatitude(),
                    'longitude' => $ride->getArrival()->getLongitude()
                ],
                'datetime' => $ride->getArrivalDateTime()?->format('Y-m-d H:i:s')
            ],
            'price_per_seat' => [
                'amount' => $ride->getPricePerSeat()->getAmount(),
                'currency' => $ride->getPricePerSeat()->getCurrency()
            ],
            'seats' => [
                'total' => $ride->getTotalSeats(),
                'available' => $ride->getAvailableSeats()
            ],
            'status' => $ride->getStatus()->value,
            'created_at' => $ride->getCreatedAt()->format('Y-m-d H:i:s')
        ];

        // Informations détaillées si demandées
        if ($detailed) {
            $data['driver'] = [
                'id' => $ride->getDriver()->getId(),
                'username' => $ride->getDriver()->getUsername(),
                'rating' => $ride->getDriver()->getAverageRating()
            ];
            $data['distance'] = $ride->getDeparture()->calculateDistanceTo($ride->getArrival());
        }

        return $data;
    }

    /**
     * Validation des données de création de trajet
     */
    private function validateRideData(array $data): array
    {
        $errors = [];

        if (empty($data['driver_id'])) {
            $errors[] = 'ID du conducteur requis';
        }

        if (empty($data['departure']['name']) || 
            empty($data['departure']['latitude']) || 
            empty($data['departure']['longitude'])) {
            $errors[] = 'Informations de départ complètes requises';
        }

        if (empty($data['arrival']['name']) || 
            empty($data['arrival']['latitude']) || 
            empty($data['arrival']['longitude'])) {
            $errors[] = 'Informations d\'arrivée complètes requises';
        }

        if (empty($data['departure_datetime'])) {
            $errors[] = 'Date et heure de départ requises';
        }

        if (empty($data['available_seats']) || $data['available_seats'] < 1) {
            $errors[] = 'Nombre de places disponibles invalide';
        }

        if (empty($data['price_per_seat']) || $data['price_per_seat'] < 0) {
            $errors[] = 'Prix par place invalide';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Réponse JSON standardisée
     */
    private function jsonResponse(array $data, int $status = 200): Response
    {
        return new Response(json_encode($data), $status, ['Content-Type' => 'application/json']);
    }

    /**
     * Réponse d'erreur standardisée
     */
    private function errorResponse(string $message, int $status = 400, array $errors = []): Response
    {
        $data = [
            'success' => false,
            'message' => $message
        ];

        if (!empty($errors)) {
            $data['errors'] = $errors;
        }

        return $this->jsonResponse($data, $status);
    }
} 