<?php

namespace App\Controllers\Refactored;

use App\Core\Controller;
use App\Core\Http\Response;
use App\Domain\Services\BookingService;
use App\Domain\Exceptions\BookingException;
use App\Domain\Exceptions\RideNotFoundException;
use App\Domain\Exceptions\UnauthorizedException;

/**
 * Contrôleur V2 orienté objet pour la gestion des réservations
 */
class BookingControllerV2 extends Controller
{
    public function __construct(
        private BookingService $bookingService
    ) {}

    /**
     * Liste des réservations de l'utilisateur connecté
     */
    public function index(): Response
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $bookings = $this->bookingService->getUserBookings($userId);
            
            return $this->success([
                'bookings' => $bookings,
                'count' => count($bookings)
            ]);
            
        } catch (UnauthorizedException $e) {
            return $this->error($e->getMessage(), 401);
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la récupération des réservations', $e);
            return $this->error('Erreur interne du serveur', 500);
        }
    }

    /**
     * Vérification d'éligibilité à la réservation
     */
    public function store(int $rideId): Response
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $eligibility = $this->bookingService->checkBookingEligibility($rideId, $userId);
            
            return $this->success([
                'needConfirmation' => true,
                'price' => $eligibility['price'],
                'balance' => $eligibility['balance'],
                'availableSeats' => $eligibility['availableSeats'],
                'ride_id' => $rideId
            ]);
            
        } catch (UnauthorizedException $e) {
            return $this->error($e->getMessage(), 403);
        } catch (RideNotFoundException $e) {
            return $this->error($e->getMessage(), 404);
        } catch (BookingException $e) {
            return $this->error($e->getMessage(), 409);
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la vérification d\'éligibilité', $e);
            return $this->error('Erreur interne du serveur', 500);
        }
    }

    /**
     * Confirmation de réservation
     */
    public function confirm(int $rideId): Response
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $result = $this->bookingService->confirmBooking($rideId, $userId);
            
            return $this->success($result, 'Réservation confirmée avec succès');
            
        } catch (UnauthorizedException $e) {
            return $this->error($e->getMessage(), 403);
        } catch (RideNotFoundException $e) {
            return $this->error($e->getMessage(), 404);
        } catch (BookingException $e) {
            return $this->error($e->getMessage(), 409);
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la confirmation de réservation', $e);
            return $this->error('Erreur interne du serveur', 500);
        }
    }

    /**
     * Annulation de réservation
     */
    public function cancel(int $bookingId): Response
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $success = $this->bookingService->cancelBooking($bookingId, $userId);
            
            if ($success) {
                return $this->success(null, 'Réservation annulée avec succès');
            } else {
                return $this->error('Impossible d\'annuler la réservation', 400);
            }
            
        } catch (UnauthorizedException $e) {
            return $this->error($e->getMessage(), 403);
        } catch (BookingException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->logError('Erreur lors de l\'annulation de réservation', $e);
            return $this->error('Erreur interne du serveur', 500);
        }
    }

    /**
     * Détails d'une réservation spécifique
     */
    public function show(int $bookingId): Response
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $bookingDetails = $this->bookingService->getBookingDetails($bookingId, $userId);
            
            return $this->success($bookingDetails);
            
        } catch (UnauthorizedException $e) {
            return $this->error($e->getMessage(), 403);
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la récupération des détails de réservation', $e);
            return $this->error('Erreur interne du serveur', 500);
        }
    }

    /**
     * Historique des réservations avec pagination
     */
    public function history(): Response
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $limit = (int)($_GET['limit'] ?? 20);
            $limit = min($limit, 100); // Limiter à 100 max
            
            $history = $this->bookingService->getUserBookingHistory($userId, $limit);
            
            return $this->success([
                'history' => $history,
                'count' => count($history),
                'limit' => $limit
            ]);
            
        } catch (UnauthorizedException $e) {
            return $this->error($e->getMessage(), 401);
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la récupération de l\'historique', $e);
            return $this->error('Erreur interne du serveur', 500);
        }
    }

    /**
     * Création de réservation locale (pour trajets locaux)
     */
    public function create(): Response
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $data = $this->getJsonData();
            
            // Validation des données
            $validationErrors = $this->validateBookingData($data);
            if (!empty($validationErrors)) {
                return $this->error('Données invalides', 400, ['errors' => $validationErrors]);
            }
            
            $rideId = (int)$data['ride_id'];
            $result = $this->bookingService->confirmBooking($rideId, $userId);
            
            return $this->success($result, 'Réservation créée avec succès');
            
        } catch (UnauthorizedException $e) {
            return $this->error($e->getMessage(), 403);
        } catch (RideNotFoundException $e) {
            return $this->error($e->getMessage(), 404);
        } catch (BookingException $e) {
            return $this->error($e->getMessage(), 409);
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la création de réservation', $e);
            return $this->error('Erreur interne du serveur', 500);
        }
    }

    /**
     * Suppression de réservation (alias pour cancel)
     */
    public function destroy(int $bookingId): Response
    {
        return $this->cancel($bookingId);
    }

    /**
     * Récupère l'ID de l'utilisateur authentifié
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
     * Valide les données de réservation
     */
    private function validateBookingData(array $data): array
    {
        $errors = [];
        
        if (empty($data['ride_id'])) {
            $errors[] = 'L\'ID du trajet est requis';
        }
        
        if (!empty($data['price']) && !is_numeric($data['price'])) {
            $errors[] = 'Le prix doit être un nombre valide';
        }
        
        if (!empty($data['seats']) && (!is_numeric($data['seats']) || (int)$data['seats'] <= 0)) {
            $errors[] = 'Le nombre de places doit être un entier positif';
        }
        
        return $errors;
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
     * Retourne une réponse de succès standardisée
     */
    protected function jsonSuccess($data = null, string $message = 'Opération réussie'): Response
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
     * Retourne une réponse d'erreur standardisée
     */
    protected function jsonError(string $message, int $code = 400, array $extra = []): Response
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
} 