<?php

namespace App\Domain\Services;

use App\Domain\Entities\Ride;
use App\Domain\Entities\User;
use App\Domain\Repositories\RideRepositoryInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Repositories\BookingRepositoryInterface;
use App\Domain\ValueObjects\Money;
use App\Domain\Exceptions\BookingException;
use App\Domain\Exceptions\RideNotFoundException;
use App\Domain\Exceptions\UnauthorizedException;
use App\Services\CreditService;
use PDO;

/**
 * Service métier pour la gestion des réservations
 */
class BookingService
{
    private const COMMISSION_RATE = 2.0; // 2€ de commission par défaut
    
    public function __construct(
        private BookingRepositoryInterface $bookingRepository,
        private RideRepositoryInterface $rideRepository,
        private UserRepositoryInterface $userRepository,
        private CreditService $creditService,
        private PDO $database
    ) {}
    
    /**
     * Récupère toutes les réservations d'un utilisateur
     */
    public function getUserBookings(int $userId): array
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new UnauthorizedException('Utilisateur non trouvé');
        }
        
        return $this->bookingRepository->findByUserId($userId);
    }
    
    /**
     * Vérifie si une réservation est possible
     */
    public function checkBookingEligibility(int $rideId, int $userId): array
    {
        $ride = $this->rideRepository->findById($rideId);
        if (!$ride) {
            throw new RideNotFoundException("Trajet avec l'ID $rideId non trouvé");
        }
        
        $user = $this->userRepository->findById($userId);
        if (!$user || !$user->isPassenger()) {
            throw new UnauthorizedException('Seuls les passagers peuvent réserver');
        }
        
        // Vérifier les places disponibles
        if ($ride->getAvailableSeats() <= 0) {
            throw new BookingException('Aucune place disponible');
        }
        
        // Vérifier les crédits
        $balance = $this->creditService->getBalance($userId);
        $price = $ride->getPrice()->getAmount();
        
        if ($balance < $price) {
            throw new BookingException('Crédits insuffisants');
        }
        
        return [
            'canBook' => true,
            'price' => $price,
            'balance' => $balance,
            'availableSeats' => $ride->getAvailableSeats()
        ];
    }
    
    /**
     * Confirme une réservation (transaction complète)
     */
    public function confirmBooking(int $rideId, int $userId): array
    {
        $this->database->beginTransaction();
        
        try {
            // Vérifier l'éligibilité
            $eligibility = $this->checkBookingEligibility($rideId, $userId);
            
            $ride = $this->rideRepository->findById($rideId);
            $price = $ride->getPrice()->getAmount();
            $driverId = $ride->getDriverId();
            
            // Débiter le compte du passager
            $balanceBefore = $this->creditService->getBalance($userId);
            $this->creditService->debitAccount(
                $userId, 
                $price, 
                'achat_trajet', 
                "Réservation trajet #$rideId"
            );
            
            // Créditer le compte du conducteur (net de commission)
            $commission = self::COMMISSION_RATE;
            $netAmount = $price - $commission;
            
            if ($netAmount > 0) {
                $this->creditService->creditAccount(
                    $driverId,
                    $netAmount,
                    'achat_trajet',
                    "Gain trajet #$rideId"
                );
            }
            
            // Réserver une place
            $updatedRide = $ride->decreaseAvailableSeats();
            $this->rideRepository->update($updatedRide);
            
            // Enregistrer la réservation
            $bookingId = $this->bookingRepository->create([
                'user_id' => $userId,
                'ride_id' => $rideId,
                'price' => $price,
                'status' => 'confirmé',
                'reserved_at' => new \DateTime()
            ]);
            
            $this->database->commit();
            
            $balanceAfter = $this->creditService->getBalance($userId);
            
            return [
                'booking_id' => $bookingId,
                'ride_id' => $rideId,
                'price' => $price,
                'commission' => $commission,
                'net_amount' => $netAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'remaining_seats' => $updatedRide->getAvailableSeats()
            ];
            
        } catch (\Exception $e) {
            $this->database->rollBack();
            throw new BookingException('Erreur lors de la confirmation : ' . $e->getMessage());
        }
    }
    
    /**
     * Annule une réservation
     */
    public function cancelBooking(int $bookingId, int $userId): bool
    {
        $booking = $this->bookingRepository->findById($bookingId);
        if (!$booking || $booking['user_id'] !== $userId) {
            throw new UnauthorizedException('Réservation non trouvée ou non autorisée');
        }
        
        if ($booking['status'] !== 'confirmé') {
            throw new BookingException('Seules les réservations confirmées peuvent être annulées');
        }
        
        $this->database->beginTransaction();
        
        try {
            // Rembourser le passager
            $this->creditService->creditAccount(
                $userId,
                $booking['price'],
                'remboursement',
                "Annulation réservation #$bookingId"
            );
            
            // Débiter le conducteur
            $ride = $this->rideRepository->findById($booking['ride_id']);
            $netAmount = $booking['price'] - self::COMMISSION_RATE;
            
            if ($netAmount > 0) {
                $this->creditService->debitAccount(
                    $ride->getDriverId(),
                    $netAmount,
                    'remboursement',
                    "Annulation trajet #" . $booking['ride_id']
                );
            }
            
            // Rendre la place available
            $updatedRide = $ride->increaseAvailableSeats();
            $this->rideRepository->update($updatedRide);
            
            // Marquer la réservation comme annulée
            $this->bookingRepository->updateStatus($bookingId, 'annulé');
            
            $this->database->commit();
            
            return true;
            
        } catch (\Exception $e) {
            $this->database->rollBack();
            throw new BookingException('Erreur lors de l\'annulation : ' . $e->getMessage());
        }
    }
    
    /**
     * Récupère les détails d'une réservation
     */
    public function getBookingDetails(int $bookingId, int $userId): array
    {
        $booking = $this->bookingRepository->findById($bookingId);
        if (!$booking || $booking['user_id'] !== $userId) {
            throw new UnauthorizedException('Réservation non trouvée ou non autorisée');
        }
        
        $ride = $this->rideRepository->findById($booking['ride_id']);
        
        return [
            'booking' => $booking,
            'ride' => $ride->toArray()
        ];
    }
} 