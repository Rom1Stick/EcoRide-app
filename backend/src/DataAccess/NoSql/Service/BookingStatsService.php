<?php

namespace App\DataAccess\NoSql\Service;

use App\DataAccess\Exception\DataAccessException;
use App\DataAccess\NoSql\Model\BookingStats;
use MongoDB\Collection;
use MongoDB\BSON\ObjectId;

/**
 * Service pour gérer les statistiques de réservation dans MongoDB
 */
class BookingStatsService extends AbstractMongoService
{
    /**
     * Nom de la collection MongoDB
     * 
     * @var string
     */
    private const COLLECTION_NAME = 'booking_stats';
    
    /**
     * Collection MongoDB des statistiques de réservation
     * 
     * @var Collection
     */
    protected Collection $collection;
    
    /**
     * Constructeur
     * 
     * @param MongoServiceInterface $mongoConnection Service de connexion
     */
    public function __construct(MongoServiceInterface $mongoConnection)
    {
        parent::__construct($mongoConnection);
    }
    
    /**
     * Initialiser le service
     * 
     * @return void
     */
    protected function initService(): void
    {
        $this->collection = $this->getCollection(self::COLLECTION_NAME);
    }
    
    /**
     * Trouver ou créer les statistiques d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @return BookingStats
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function findOrCreateByUserId(int $userId): BookingStats
    {
        try {
            $result = $this->collection->findOne(['user_id' => $userId]);
            
            if ($result !== null) {
                return BookingStats::fromArray((array)$result);
            }
            
            // Créer de nouvelles statistiques si aucune n'existe
            $bookingStats = new BookingStats($userId);
            $this->save($bookingStats);
            
            return $bookingStats;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la récupération des statistiques de réservation : " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Enregistrer les statistiques de réservation
     * 
     * @param BookingStats $bookingStats Statistiques à enregistrer
     * @return BookingStats
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function save(BookingStats $bookingStats): BookingStats
    {
        try {
            $bookingStats->updateTimestamp();
            $data = $bookingStats->jsonSerialize();
            
            // Supprimer l'ID pour éviter les conflits
            if (isset($data['_id'])) {
                unset($data['_id']);
            }
            
            if ($bookingStats->getId() === null) {
                // Insertion d'un nouveau document
                $result = $this->collection->insertOne($data);
                
                if ($result->getInsertedCount() !== 1) {
                    throw new DataAccessException("Échec de l'insertion des statistiques de réservation");
                }
                
                $bookingStats->setId((string)$result->getInsertedId());
            } else {
                // Mise à jour d'un document existant
                $result = $this->collection->updateOne(
                    ['_id' => new ObjectId($bookingStats->getId())],
                    ['$set' => $data]
                );
                
                if ($result->getModifiedCount() !== 1 && $result->getMatchedCount() !== 1) {
                    throw new DataAccessException("Échec de la mise à jour des statistiques de réservation");
                }
            }
            
            return $bookingStats;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de l'enregistrement des statistiques de réservation : " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Mettre à jour les statistiques pour une nouvelle réservation
     * 
     * @param int $userId ID de l'utilisateur
     * @param array $bookingData Données de la réservation
     * @return BookingStats
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function updateForNewBooking(int $userId, array $bookingData): BookingStats
    {
        $bookingStats = $this->findOrCreateByUserId($userId);
        
        // Extraire les données de la réservation
        $seats = $bookingData['seats'] ?? 1;
        $amount = $bookingData['amount'] ?? 0.0;
        $status = $bookingData['status'] ?? 'pending';
        $destination = $bookingData['destination'] ?? '';
        $date = isset($bookingData['date']) ? new \DateTime($bookingData['date']) : new \DateTime();
        $distance = $bookingData['distance'] ?? 0.0;
        $co2Saved = $bookingData['co2_saved'] ?? 0.0;
        
        // Mettre à jour les compteurs généraux
        $bookingStats->incrementTotalBookings()
            ->incrementTotalSeatsBooked($seats)
            ->addToTotalAmountSpent($amount)
            ->addToTotalCO2Saved($co2Saved)
            ->addToTotalDistance($distance);
        
        // Mettre à jour le compteur de statut
        $bookingStats->incrementStatusCount($status);
        
        // Mettre à jour le jour de la semaine
        $dayOfWeek = strtolower($date->format('l'));
        $bookingStats->incrementDayOfWeekCount($dayOfWeek);
        
        // Ajouter la destination
        if (!empty($destination)) {
            $bookingStats->addDestination($destination);
        }
        
        // Ajouter à l'historique mensuel
        $yearMonth = $date->format('Y-m');
        $bookingStats->addToMonthlyHistory($yearMonth, 1, $amount);
        
        return $this->save($bookingStats);
    }
    
    /**
     * Mettre à jour les statistiques lors d'un changement de statut
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $oldStatus Ancien statut
     * @param string $newStatus Nouveau statut
     * @param int $count Nombre de réservations concernées
     * @return BookingStats
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function updateForStatusChange(int $userId, string $oldStatus, string $newStatus, int $count = 1): BookingStats
    {
        $bookingStats = $this->findOrCreateByUserId($userId);
        
        // Mettre à jour les compteurs de statut
        $bookingStats->moveStatusCount($oldStatus, $newStatus, $count);
        
        return $this->save($bookingStats);
    }
    
    /**
     * Obtenir les meilleures destinations pour tous les utilisateurs
     * 
     * @param int $limit Nombre maximum de destinations à retourner
     * @return array
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function getTopGlobalDestinations(int $limit = 10): array
    {
        try {
            $pipeline = [
                ['$unwind' => '$top_destinations'],
                ['$group' => [
                    '_id' => '$top_destinations.destination',
                    'count' => ['$sum' => '$top_destinations.count']
                ]],
                ['$sort' => ['count' => -1]],
                ['$limit' => $limit],
                ['$project' => [
                    'destination' => '$_id',
                    'count' => 1,
                    '_id' => 0
                ]]
            ];
            
            $result = $this->collection->aggregate($pipeline)->toArray();
            
            return array_map(function($item) {
                return [
                    'destination' => $item['destination'],
                    'count' => $item['count']
                ];
            }, $result);
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la récupération des destinations populaires : " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Obtenir les statistiques globales de l'application
     * 
     * @return array
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function getGlobalStats(): array
    {
        try {
            $pipeline = [
                ['$group' => [
                    '_id' => null,
                    'total_users' => ['$sum' => 1],
                    'total_bookings' => ['$sum' => '$total_bookings'],
                    'total_seats' => ['$sum' => '$total_seats_booked'],
                    'total_amount' => ['$sum' => '$total_amount_spent'],
                    'total_co2_saved' => ['$sum' => '$total_co2_saved'],
                    'total_distance' => ['$sum' => '$total_distance'],
                    'bookings_by_status' => ['$push' => '$bookings_by_status'],
                    'bookings_by_day_of_week' => ['$push' => '$bookings_by_day_of_week']
                ]]
            ];
            
            $result = $this->collection->aggregate($pipeline)->toArray();
            
            if (empty($result)) {
                return [
                    'total_users' => 0,
                    'total_bookings' => 0,
                    'total_seats' => 0,
                    'total_amount' => 0,
                    'total_co2_saved' => 0,
                    'total_distance' => 0,
                    'bookings_by_status' => [
                        'pending' => 0,
                        'confirmed' => 0,
                        'completed' => 0,
                        'cancelled' => 0,
                        'refunded' => 0
                    ],
                    'bookings_by_day_of_week' => [
                        'monday' => 0,
                        'tuesday' => 0,
                        'wednesday' => 0,
                        'thursday' => 0,
                        'friday' => 0,
                        'saturday' => 0,
                        'sunday' => 0
                    ]
                ];
            }
            
            // Fusionner les compteurs par statut
            $bookingsByStatus = [
                'pending' => 0,
                'confirmed' => 0,
                'completed' => 0,
                'cancelled' => 0,
                'refunded' => 0
            ];
            
            foreach ($result[0]['bookings_by_status'] as $userStatusCounts) {
                foreach ($userStatusCounts as $status => $count) {
                    if (isset($bookingsByStatus[$status])) {
                        $bookingsByStatus[$status] += $count;
                    } else {
                        $bookingsByStatus[$status] = $count;
                    }
                }
            }
            
            // Fusionner les compteurs par jour de la semaine
            $bookingsByDayOfWeek = [
                'monday' => 0,
                'tuesday' => 0,
                'wednesday' => 0,
                'thursday' => 0,
                'friday' => 0,
                'saturday' => 0,
                'sunday' => 0
            ];
            
            foreach ($result[0]['bookings_by_day_of_week'] as $userDayCounts) {
                foreach ($userDayCounts as $day => $count) {
                    $bookingsByDayOfWeek[$day] += $count;
                }
            }
            
            return [
                'total_users' => $result[0]['total_users'],
                'total_bookings' => $result[0]['total_bookings'],
                'total_seats' => $result[0]['total_seats'],
                'total_amount' => $result[0]['total_amount'],
                'total_co2_saved' => $result[0]['total_co2_saved'],
                'total_distance' => $result[0]['total_distance'],
                'bookings_by_status' => $bookingsByStatus,
                'bookings_by_day_of_week' => $bookingsByDayOfWeek
            ];
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la récupération des statistiques globales : " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Obtenir l'évolution mensuelle du nombre de réservations
     * 
     * @param int $months Nombre de mois à récupérer
     * @return array
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function getMonthlyBookingTrend(int $months = 12): array
    {
        try {
            // Récupérer tous les historiques mensuels de tous les utilisateurs
            $allHistories = $this->collection->find(
                [],
                ['projection' => ['monthly_booking_history' => 1, '_id' => 0]]
            )->toArray();
            
            // Agréger tous les historiques
            $monthlyData = [];
            
            foreach ($allHistories as $history) {
                if (isset($history['monthly_booking_history'])) {
                    foreach ($history['monthly_booking_history'] as $monthData) {
                        $month = $monthData['month'];
                        
                        if (!isset($monthlyData[$month])) {
                            $monthlyData[$month] = [
                                'month' => $month,
                                'count' => 0,
                                'amount' => 0
                            ];
                        }
                        
                        $monthlyData[$month]['count'] += $monthData['count'];
                        $monthlyData[$month]['amount'] += $monthData['amount'];
                    }
                }
            }
            
            // Trier par mois (du plus ancien au plus récent)
            uksort($monthlyData, function ($a, $b) {
                return strcmp($a, $b);
            });
            
            // Limiter au nombre de mois demandé
            return array_slice(array_values($monthlyData), -$months);
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la récupération des tendances mensuelles : " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Supprimer les statistiques d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @return bool
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function deleteByUserId(int $userId): bool
    {
        try {
            $result = $this->collection->deleteOne(['user_id' => $userId]);
            return $result->getDeletedCount() > 0;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la suppression des statistiques de réservation : " . $e->getMessage(), 0, $e);
        }
    }
} 