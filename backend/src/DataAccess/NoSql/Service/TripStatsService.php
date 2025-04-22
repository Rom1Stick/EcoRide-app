<?php

namespace App\DataAccess\NoSql\Service;

use App\DataAccess\DataAccessException;
use App\DataAccess\NoSql\Model\TripStats;
use App\DataAccess\Sql\Entity\Trip;
use MongoDB\Collection;
use MongoDB\BSON\ObjectId;
use DateTime;

/**
 * Service pour gérer les statistiques de trajets dans MongoDB
 */
class TripStatsService extends AbstractMongoService
{
    /**
     * Nom de la collection MongoDB
     * 
     * @var string
     */
    private const COLLECTION_NAME = 'trip_stats';
    
    /**
     * Collection MongoDB des statistiques de trajet
     * 
     * @var Collection
     */
    private Collection $collection;
    
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
     * Trouver ou créer les statistiques d'un conducteur
     * 
     * @param int $driverId ID du conducteur
     * @return TripStats
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function findOrCreateByDriverId(int $driverId): TripStats
    {
        try {
            $result = $this->collection->findOne(['driver_id' => $driverId]);
            
            if ($result !== null) {
                return TripStats::fromArray((array)$result);
            }
            
            // Créer de nouvelles statistiques si aucune n'existe
            $tripStats = new TripStats($driverId);
            $this->save($tripStats);
            
            return $tripStats;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la récupération des statistiques de trajet : " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Sauvegarder les statistiques d'un conducteur
     * 
     * @param TripStats $tripStats Statistiques à sauvegarder
     * @return TripStats
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function save(TripStats $tripStats): TripStats
    {
        try {
            $tripStats->updateTimestamp();
            $data = $tripStats->jsonSerialize();
            
            // Supprimer l'ID pour les nouvelles statistiques
            if ($tripStats->getId() === null) {
                unset($data['_id']);
                $result = $this->collection->insertOne($data);
                
                if ($result->getInsertedCount() > 0) {
                    $tripStats->setId((string)$result->getInsertedId());
                } else {
                    throw new DataAccessException("Échec de l'insertion des statistiques de trajet");
                }
            } else {
                $id = new ObjectId($tripStats->getId());
                $result = $this->collection->updateOne(
                    ['_id' => $id],
                    ['$set' => $data]
                );
                
                if ($result->getModifiedCount() === 0 && $result->getMatchedCount() === 0) {
                    throw new DataAccessException("Statistiques de trajet non trouvées pour la mise à jour");
                }
            }
            
            return $tripStats;
        } catch (DataAccessException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la sauvegarde des statistiques de trajet : " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Mettre à jour les statistiques pour un nouveau trajet
     * 
     * @param int $driverId ID du conducteur
     * @param Trip $trip Informations sur le trajet
     * @return TripStats
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function updateForNewTrip(int $driverId, Trip $trip): TripStats
    {
        try {
            $stats = $this->findOrCreateByDriverId($driverId);
            
            // Mettre à jour les compteurs
            $stats->incrementTotalTrips();
            $stats->addSeatsOffered($trip->getAvailableSeats());
            $stats->incrementStatusCount($trip->getStatus());
            
            // Ajouter destination et origine
            $stats->addOrigin($trip->getOriginCity());
            $stats->addDestination($trip->getDestinationCity());
            
            // Jour de la semaine
            $dayOfWeek = strtolower($trip->getDepartureTime()->format('l'));
            $stats->incrementDayOfWeekCount($dayOfWeek);
            
            // Distance et durée
            $stats->addToDistance($trip->getDistance());
            $stats->addToDuration($trip->getDuration());
            
            // Historique mensuel
            $yearMonth = $trip->getDepartureTime()->format('Y-m');
            $stats->addToMonthlyHistory($yearMonth, 1, 0); // Gains à 0 car pas encore de réservations
            
            return $this->save($stats);
        } catch (DataAccessException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la mise à jour des statistiques pour un nouveau trajet : " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Mettre à jour les statistiques pour un changement de statut de trajet
     * 
     * @param int $driverId ID du conducteur
     * @param string $oldStatus Ancien statut
     * @param string $newStatus Nouveau statut
     * @param int $count Nombre de trajets concernés
     * @return TripStats
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function updateForStatusChange(int $driverId, string $oldStatus, string $newStatus, int $count = 1): TripStats
    {
        try {
            $stats = $this->findOrCreateByDriverId($driverId);
            $stats->moveStatusCount($oldStatus, $newStatus, $count);
            return $this->save($stats);
        } catch (DataAccessException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la mise à jour du statut dans les statistiques : " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Mettre à jour les statistiques pour une nouvelle réservation
     * 
     * @param int $driverId ID du conducteur
     * @param float $amount Montant de la réservation
     * @param int $seatCount Nombre de places réservées
     * @param string $yearMonth Mois de la réservation (format "YYYY-MM")
     * @return TripStats
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function updateForNewBooking(int $driverId, float $amount, int $seatCount, string $yearMonth): TripStats
    {
        try {
            $stats = $this->findOrCreateByDriverId($driverId);
            
            // Mettre à jour les places réservées et les gains
            $stats->addSeatsBooked($seatCount);
            $stats->addToEarnings($amount);
            
            // Mettre à jour l'historique mensuel des gains
            if (isset($stats->getMonthlyTripHistory()[$yearMonth])) {
                $stats->addToMonthlyHistory($yearMonth, 0, $amount);
            }
            
            return $this->save($stats);
        } catch (DataAccessException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la mise à jour des statistiques pour une nouvelle réservation : " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Récupérer les principales destinations de tous les conducteurs
     * 
     * @param int $limit Nombre maximum de résultats
     * @return array
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function getTopGlobalDestinations(int $limit = 10): array
    {
        try {
            $pipeline = [
                ['$project' => [
                    'top_destinations' => 1
                ]],
                ['$unwind' => '$top_destinations'],
                ['$group' => [
                    '_id' => '$top_destinations.city',
                    'count' => ['$sum' => '$top_destinations.count']
                ]],
                ['$sort' => ['count' => -1]],
                ['$limit' => $limit],
                ['$project' => [
                    '_id' => 0,
                    'city' => '$_id',
                    'count' => 1
                ]]
            ];
            
            $result = $this->collection->aggregate($pipeline)->toArray();
            return $result;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la récupération des principales destinations globales : " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Récupérer les principales origines de tous les conducteurs
     * 
     * @param int $limit Nombre maximum de résultats
     * @return array
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function getTopGlobalOrigins(int $limit = 10): array
    {
        try {
            $pipeline = [
                ['$project' => [
                    'top_origins' => 1
                ]],
                ['$unwind' => '$top_origins'],
                ['$group' => [
                    '_id' => '$top_origins.city',
                    'count' => ['$sum' => '$top_origins.count']
                ]],
                ['$sort' => ['count' => -1]],
                ['$limit' => $limit],
                ['$project' => [
                    '_id' => 0,
                    'city' => '$_id',
                    'count' => 1
                ]]
            ];
            
            $result = $this->collection->aggregate($pipeline)->toArray();
            return $result;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la récupération des principales origines globales : " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Obtenir les statistiques globales 
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
                    'total_drivers' => ['$sum' => 1],
                    'total_trips' => ['$sum' => '$total_trips'],
                    'total_seats_offered' => ['$sum' => '$total_seats_offered'],
                    'total_seats_booked' => ['$sum' => '$total_seats_booked'],
                    'total_earnings' => ['$sum' => '$total_earnings'],
                    'total_distance' => ['$sum' => '$total_distance'],
                    'total_duration' => ['$sum' => '$total_duration'],
                    'total_co2_saved' => ['$sum' => '$total_co2_saved'],
                    'avg_occupancy_rate' => ['$avg' => '$average_occupancy_rate'],
                    'trips_by_status' => ['$push' => '$trips_by_status'],
                    'trips_by_day_of_week' => ['$push' => '$trips_by_day_of_week']
                ]],
                ['$project' => [
                    '_id' => 0,
                    'total_drivers' => 1,
                    'total_trips' => 1,
                    'total_seats_offered' => 1,
                    'total_seats_booked' => 1,
                    'total_earnings' => 1,
                    'total_distance' => 1,
                    'total_duration' => 1,
                    'total_co2_saved' => 1,
                    'avg_occupancy_rate' => 1,
                    'trips_by_status' => 1,
                    'trips_by_day_of_week' => 1
                ]]
            ];
            
            $result = $this->collection->aggregate($pipeline)->toArray();
            
            if (empty($result)) {
                return [
                    'total_drivers' => 0,
                    'total_trips' => 0,
                    'total_seats_offered' => 0,
                    'total_seats_booked' => 0,
                    'total_earnings' => 0,
                    'total_distance' => 0,
                    'total_duration' => 0,
                    'total_co2_saved' => 0,
                    'avg_occupancy_rate' => 0,
                    'trips_by_status' => [
                        'scheduled' => 0,
                        'active' => 0,
                        'completed' => 0,
                        'cancelled' => 0
                    ],
                    'trips_by_day_of_week' => [
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
            
            $globalStats = $result[0];
            
            // Calculer le total des trajets par statut
            $tripsByStatus = [
                'scheduled' => 0,
                'active' => 0,
                'completed' => 0,
                'cancelled' => 0
            ];
            
            foreach ($globalStats['trips_by_status'] as $driverStatusCounts) {
                foreach ($driverStatusCounts as $status => $count) {
                    if (isset($tripsByStatus[$status])) {
                        $tripsByStatus[$status] += $count;
                    }
                }
            }
            $globalStats['trips_by_status'] = $tripsByStatus;
            
            // Calculer le total des trajets par jour de la semaine
            $tripsByDayOfWeek = [
                'monday' => 0,
                'tuesday' => 0,
                'wednesday' => 0,
                'thursday' => 0,
                'friday' => 0,
                'saturday' => 0,
                'sunday' => 0
            ];
            
            foreach ($globalStats['trips_by_day_of_week'] as $driverDayCounts) {
                foreach ($driverDayCounts as $day => $count) {
                    if (isset($tripsByDayOfWeek[$day])) {
                        $tripsByDayOfWeek[$day] += $count;
                    }
                }
            }
            $globalStats['trips_by_day_of_week'] = $tripsByDayOfWeek;
            
            return $globalStats;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la récupération des statistiques globales : " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Obtenir la tendance des trajets par mois
     * 
     * @param int $months Nombre de mois à récupérer
     * @return array
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function getMonthlyTripTrend(int $months = 12): array
    {
        try {
            // Construire la liste des mois récents
            $monthsList = [];
            $currentDate = new DateTime();
            
            for ($i = 0; $i < $months; $i++) {
                $date = clone $currentDate;
                $date->modify("-{$i} month");
                $monthsList[] = $date->format('Y-m');
            }
            
            // Récupérer les données pour ces mois
            $pipeline = [
                ['$project' => [
                    'monthly_trip_history' => 1
                ]],
                ['$project' => [
                    'months' => [
                        '$objectToArray' => '$monthly_trip_history'
                    ]
                ]],
                ['$unwind' => '$months'],
                ['$match' => [
                    'months.k' => ['$in' => $monthsList]
                ]],
                ['$group' => [
                    '_id' => '$months.k',
                    'trips' => ['$sum' => '$months.v.count'],
                    'earnings' => ['$sum' => '$months.v.earnings']
                ]],
                ['$sort' => ['_id' => 1]],
                ['$project' => [
                    '_id' => 0,
                    'month' => '$_id',
                    'trips' => 1,
                    'earnings' => 1
                ]]
            ];
            
            $results = $this->collection->aggregate($pipeline)->toArray();
            
            // Construire le tableau final avec tous les mois, même ceux sans données
            $trend = [];
            foreach ($monthsList as $month) {
                $found = false;
                foreach ($results as $result) {
                    if ($result['month'] === $month) {
                        $trend[] = $result;
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    $trend[] = [
                        'month' => $month,
                        'trips' => 0,
                        'earnings' => 0
                    ];
                }
            }
            
            // Trier par mois croissant
            usort($trend, function($a, $b) {
                return strcmp($a['month'], $b['month']);
            });
            
            return $trend;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la récupération de la tendance mensuelle des trajets : " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Obtenir les statistiques d'un conducteur par ID
     * 
     * @param int $driverId ID du conducteur
     * @return TripStats|null
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function getByDriverId(int $driverId): ?TripStats
    {
        try {
            $result = $this->collection->findOne(['driver_id' => $driverId]);
            
            if ($result === null) {
                return null;
            }
            
            return TripStats::fromArray((array)$result);
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la récupération des statistiques du conducteur : " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Supprimer les statistiques d'un conducteur
     * 
     * @param int $driverId ID du conducteur
     * @return bool
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function deleteByDriverId(int $driverId): bool
    {
        try {
            $result = $this->collection->deleteOne(['driver_id' => $driverId]);
            return $result->getDeletedCount() > 0;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la suppression des statistiques du conducteur : " . $e->getMessage(), 0, $e);
        }
    }
} 