<?php

namespace App\Controllers\Refactored;

use App\Controllers\Controller;
use App\Domain\Services\RideManagementService;
use App\Domain\Exceptions\RideNotFoundException;
use App\Domain\Exceptions\BookingException;
use App\Domain\Exceptions\UnauthorizedException;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Logger;
use InvalidArgumentException;
use Exception;

/**
 * Contrôleur refactorisé pour la gestion des trajets
 * Utilise l'architecture orientée objet avec injection de dépendances
 */
class RideController extends Controller
{
    private RideManagementService $rideService;
    private Logger $logger;

    public function __construct(RideManagementService $rideService, Logger $logger)
    {
        parent::__construct();
        $this->rideService = $rideService;
        $this->logger = $logger;
    }

    /**
     * Liste des trajets disponibles avec recherche
     */
    public function index(): array
    {
        try {
            // Récupération des paramètres de recherche
            $departureLocation = $this->getParam('departureLocation');
            $arrivalLocation = $this->getParam('arrivalLocation');
            $date = $this->getParam('date');
            $maxPrice = $this->getParam('maxPrice') ? (float) $this->getParam('maxPrice') : null;
            $sortBy = $this->getParam('sortBy', 'departureTime');
            $page = (int) $this->getParam('page', 1);
            $limit = (int) $this->getParam('limit', 10);

            // Validation des paramètres
            $this->validateSearchParameters($page, $limit, $sortBy);

            // Recherche des trajets via le service métier
            $searchResults = $this->rideService->searchRides(
                $departureLocation,
                $arrivalLocation,
                $date,
                $maxPrice,
                $sortBy,
                $page,
                $limit
            );

            $this->logger->info('Recherche de trajets effectuée', [
                'parameters' => compact('departureLocation', 'arrivalLocation', 'date', 'maxPrice'),
                'results_count' => count($searchResults['rides'])
            ]);

            return $this->success($searchResults);

        } catch (InvalidArgumentException $e) {
            $this->logger->warning('Paramètres de recherche invalides', ['error' => $e->getMessage()]);
            return $this->error($e->getMessage(), 400);
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la recherche de trajets', ['error' => $e->getMessage()]);
            return $this->error('Une erreur est survenue lors de la recherche des trajets', 500);
        }
    }

    /**
     * Détails d'un trajet spécifique
     */
    public function show(int $id): array
    {
        try {
            $rideDetails = $this->rideService->getRideDetails($id);
            
            $this->logger->info('Consultation des détails du trajet', ['ride_id' => $id]);
            
            return $this->success($rideDetails);

        } catch (RideNotFoundException $e) {
            $this->logger->warning('Trajet non trouvé', ['ride_id' => $id]);
            return $this->error($e->getMessage(), 404);
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la récupération du trajet', [
                'ride_id' => $id,
                'error' => $e->getMessage()
            ]);
            return $this->error('Une erreur est survenue lors de la récupération du trajet', 500);
        }
    }

    /**
     * Création d'un nouveau trajet
     */
    public function store(): array
    {
        try {
            // Récupération des données de la requête
            $data = $this->getJsonData();
            
            // Validation des données requises
            $this->validateRideCreationData($data);

            // Récupération de l'utilisateur connecté (à implémenter selon votre système d'auth)
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                return $this->error('Utilisateur non authentifié', 401);
            }

            // Note: Dans une implémentation complète, il faudrait :
            // 1. Convertir les données en objets Value Objects (Location, etc.)
            // 2. Appeler le service de création
            // 3. Gérer la persistance

            $this->logger->info('Création d\'un nouveau trajet', [
                'driver_id' => $currentUser->getId(),
                'departure' => $data['departure'],
                'arrival' => $data['arrival']
            ]);

            // Pour l'instant, on retourne un succès simulé
            return $this->success(['message' => 'Trajet créé avec succès'], 201);

        } catch (InvalidArgumentException $e) {
            $this->logger->warning('Données de création de trajet invalides', ['error' => $e->getMessage()]);
            return $this->error($e->getMessage(), 400);
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la création du trajet', ['error' => $e->getMessage()]);
            return $this->error('Une erreur est survenue lors de la création du trajet', 500);
        }
    }

    /**
     * Mise à jour d'un trajet
     */
    public function update(int $id): array
    {
        try {
            // Récupération des données de la requête
            $data = $this->getJsonData();
            
            // Récupération de l'utilisateur connecté
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                return $this->error('Utilisateur non authentifié', 401);
            }

            // Note: Implémentation à compléter avec les Value Objects
            $this->logger->info('Mise à jour du trajet', [
                'ride_id' => $id,
                'user_id' => $currentUser->getId()
            ]);

            return $this->success(['message' => 'Trajet mis à jour avec succès']);

        } catch (RideNotFoundException $e) {
            return $this->error($e->getMessage(), 404);
        } catch (UnauthorizedException $e) {
            return $this->error($e->getMessage(), 403);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la mise à jour du trajet', [
                'ride_id' => $id,
                'error' => $e->getMessage()
            ]);
            return $this->error('Une erreur est survenue lors de la mise à jour du trajet', 500);
        }
    }

    /**
     * Réservation d'un trajet
     */
    public function book(int $id): array
    {
        try {
            // Récupération des données de la requête
            $data = $this->getJsonData();
            $seatsRequested = (int) ($data['seats'] ?? 1);
            
            // Récupération de l'utilisateur connecté
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                return $this->error('Utilisateur non authentifié', 401);
            }

            // Réservation via le service métier
            $this->rideService->bookRide($id, $currentUser, $seatsRequested);
            
            $this->logger->info('Réservation effectuée', [
                'ride_id' => $id,
                'passenger_id' => $currentUser->getId(),
                'seats' => $seatsRequested
            ]);

            return $this->success(['message' => 'Réservation effectuée avec succès']);

        } catch (RideNotFoundException $e) {
            return $this->error($e->getMessage(), 404);
        } catch (BookingException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la réservation', [
                'ride_id' => $id,
                'error' => $e->getMessage()
            ]);
            return $this->error('Une erreur est survenue lors de la réservation', 500);
        }
    }

    /**
     * Annulation d'une réservation
     */
    public function cancelBooking(int $id): array
    {
        try {
            // Récupération de l'utilisateur connecté
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                return $this->error('Utilisateur non authentifié', 401);
            }

            // Annulation via le service métier
            $this->rideService->cancelBooking($id, $currentUser);
            
            $this->logger->info('Réservation annulée', [
                'ride_id' => $id,
                'passenger_id' => $currentUser->getId()
            ]);

            return $this->success(['message' => 'Réservation annulée avec succès']);

        } catch (RideNotFoundException $e) {
            return $this->error($e->getMessage(), 404);
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de l\'annulation de la réservation', [
                'ride_id' => $id,
                'error' => $e->getMessage()
            ]);
            return $this->error('Une erreur est survenue lors de l\'annulation', 500);
        }
    }

    /**
     * Suppression d'un trajet
     */
    public function destroy(int $id): array
    {
        try {
            // Récupération de l'utilisateur connecté
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                return $this->error('Utilisateur non authentifié', 401);
            }

            // Suppression via le service métier
            $this->rideService->deleteRide($id, $currentUser);
            
            $this->logger->info('Trajet supprimé', [
                'ride_id' => $id,
                'driver_id' => $currentUser->getId()
            ]);

            return $this->success(['message' => 'Trajet supprimé avec succès']);

        } catch (RideNotFoundException $e) {
            return $this->error($e->getMessage(), 404);
        } catch (UnauthorizedException $e) {
            return $this->error($e->getMessage(), 403);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la suppression du trajet', [
                'ride_id' => $id,
                'error' => $e->getMessage()
            ]);
            return $this->error('Une erreur est survenue lors de la suppression du trajet', 500);
        }
    }

    /**
     * Récupère les trajets de l'utilisateur connecté
     */
    public function getMyRides(): array
    {
        try {
            // Récupération de l'utilisateur connecté
            $currentUser = $this->getCurrentUser();
            if (!$currentUser) {
                return $this->error('Utilisateur non authentifié', 401);
            }

            // Récupération des trajets via le service métier
            $rides = $this->rideService->getDriverRides($currentUser);
            
            return $this->success(['rides' => $rides]);

        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la récupération des trajets utilisateur', [
                'error' => $e->getMessage()
            ]);
            return $this->error('Une erreur est survenue lors de la récupération de vos trajets', 500);
        }
    }

    /**
     * Valide les paramètres de recherche
     */
    private function validateSearchParameters(int $page, int $limit, string $sortBy): void
    {
        if ($page < 1) {
            throw new InvalidArgumentException('Le numéro de page doit être positif');
        }
        
        if ($limit < 1 || $limit > 100) {
            throw new InvalidArgumentException('La limite doit être comprise entre 1 et 100');
        }
        
        $allowedSortBy = ['departureTime', 'price', 'duration'];
        if (!in_array($sortBy, $allowedSortBy)) {
            throw new InvalidArgumentException('Critère de tri invalide');
        }
    }

    /**
     * Valide les données de création d'un trajet
     */
    private function validateRideCreationData(array $data): void
    {
        $requiredFields = ['departure', 'arrival', 'departureDate', 'departureTime', 'price', 'seats'];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new InvalidArgumentException("Le champ '$field' est obligatoire");
            }
        }
        
        if ((float) $data['price'] <= 0) {
            throw new InvalidArgumentException('Le prix doit être positif');
        }
        
        if ((int) $data['seats'] <= 0 || (int) $data['seats'] > 8) {
            throw new InvalidArgumentException('Le nombre de places doit être compris entre 1 et 8');
        }
    }

    /**
     * Récupère l'utilisateur actuellement connecté
     * Note: Cette méthode doit être adaptée selon votre système d'authentification
     */
    private function getCurrentUser(): ?object
    {
        // À implémenter selon votre système d'authentification
        // Par exemple, récupération depuis la session, JWT, etc.
        return null;
    }
} 