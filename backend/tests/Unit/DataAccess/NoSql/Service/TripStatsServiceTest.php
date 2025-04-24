<?php

namespace Tests\Unit\DataAccess\NoSql\Service;

use App\DataAccess\NoSql\Model\TripStats;
use App\DataAccess\NoSql\Service\MongoServiceInterface;
use App\DataAccess\NoSql\Service\TripStatsService;
use App\DataAccess\Sql\Entity\Trip;
use MongoDB\Collection;
use MongoDB\Driver\Cursor;
use PHPUnit\Framework\TestCase;
use MongoDB\BSON\ObjectId;
use DateTime;

/**
 * @group mongodb
 */
class TripStatsServiceTest extends TestCase
{
    private $mongoConnectionMock;
    private $collectionMock;
    private $tripStatsService;
    
    protected function setUp(): void
    {
        $this->mongoConnectionMock = $this->createMock(MongoServiceInterface::class);
        $this->collectionMock = $this->createMock(Collection::class);
        
        $this->mongoConnectionMock->expects($this->once())
            ->method('getCollection')
            ->with('trip_stats')
            ->willReturn($this->collectionMock);
        
        $this->tripStatsService = new TripStatsService($this->mongoConnectionMock);
    }
    
    protected function initService(): void
    {
        $this->tripStatsService = new TripStatsService($this->mongoConnectionMock);
    }
    
    public function testFindOrCreateByDriverId_Existing(): void
    {
        // Données de test
        $driverId = 123;
        $expectedResult = [
            '_id' => new ObjectId('6579a20a3853a3e2a7d0fc1a'),
            'driver_id' => $driverId,
            'total_trips' => 5,
            'trips_by_status' => [
                'completed' => 3,
                'cancelled' => 1,
                'active' => 1
            ]
        ];
        
        // Configuration du mock
        $this->collectionMock->expects($this->once())
            ->method('findOne')
            ->with(['driver_id' => $driverId])
            ->willReturn($expectedResult);
        
        // Appel de la méthode testée
        $tripStats = $this->tripStatsService->findOrCreateByDriverId($driverId);
        
        // Assertions
        $this->assertInstanceOf(TripStats::class, $tripStats);
        $this->assertEquals($driverId, $tripStats->getDriverId());
        $this->assertEquals(5, $tripStats->getTotalTrips());
    }
    
    public function testFindOrCreateByDriverId_New(): void
    {
        // Données de test
        $driverId = 456;
        
        // Configuration des mocks
        $this->collectionMock->expects($this->once())
            ->method('findOne')
            ->with(['driver_id' => $driverId])
            ->willReturn(null);
        
        $this->collectionMock->expects($this->once())
            ->method('insertOne')
            ->willReturn((object)['insertedCount' => 1, 'insertedId' => new ObjectId('6579a20a3853a3e2a7d0fc1b')]);
        
        // Appel de la méthode testée
        $tripStats = $this->tripStatsService->findOrCreateByDriverId($driverId);
        
        // Assertions
        $this->assertInstanceOf(TripStats::class, $tripStats);
        $this->assertEquals($driverId, $tripStats->getDriverId());
        $this->assertEquals(0, $tripStats->getTotalTrips());
    }
    
    public function testUpdateForNewTrip(): void
    {
        // Données de test
        $driverId = 789;
        $existingStats = new TripStats($driverId);
        $existingStats->setId('6579a20a3853a3e2a7d0fc1c');
        
        $trip = $this->createMock(Trip::class);
        $trip->expects($this->once())->method('getAvailableSeats')->willReturn(3);
        $trip->expects($this->once())->method('getStatus')->willReturn('scheduled');
        $trip->expects($this->once())->method('getOriginCity')->willReturn('Paris');
        $trip->expects($this->once())->method('getDestinationCity')->willReturn('Lyon');
        $trip->expects($this->once())->method('getDistance')->willReturn(450.5);
        $trip->expects($this->once())->method('getDuration')->willReturn(240);
        
        $departureTime = new DateTime('2023-12-25 14:00:00');
        $trip->expects($this->exactly(2))->method('getDepartureTime')->willReturn($departureTime);
        
        // Configuration des mocks
        $this->collectionMock->expects($this->once())
            ->method('findOne')
            ->with(['driver_id' => $driverId])
            ->willReturn((array)$existingStats);
        
        $this->collectionMock->expects($this->once())
            ->method('updateOne')
            ->willReturn((object)['modifiedCount' => 1, 'matchedCount' => 1]);
        
        // Appel de la méthode testée
        $updatedStats = $this->tripStatsService->updateForNewTrip($driverId, $trip);
        
        // Assertions
        $this->assertInstanceOf(TripStats::class, $updatedStats);
        $this->assertEquals($driverId, $updatedStats->getDriverId());
        $this->assertEquals(1, $updatedStats->getTotalTrips());
        $this->assertEquals(450.5, $updatedStats->getTotalDistance());
        $this->assertEquals(240, $updatedStats->getTotalDuration());
    }
    
    public function testGetTopGlobalDestinations(): void
    {
        // Données de test
        $expectedResult = [
            ['city' => 'Lyon', 'count' => 150],
            ['city' => 'Paris', 'count' => 100],
            ['city' => 'Marseille', 'count' => 75]
        ];
        
        // Configuration du mock pour simuler un résultat d'agrégation
        $cursorMock = $this->createMock(Cursor::class);
        $cursorMock->expects($this->once())
            ->method('toArray')
            ->willReturn($expectedResult);
        
        $this->collectionMock->expects($this->once())
            ->method('aggregate')
            ->willReturn($cursorMock);
        
        // Appel de la méthode testée
        $result = $this->tripStatsService->getTopGlobalDestinations(3);
        
        // Assertions
        $this->assertCount(3, $result);
        $this->assertEquals('Lyon', $result[0]['city']);
        $this->assertEquals(150, $result[0]['count']);
        $this->assertEquals('Paris', $result[1]['city']);
    }
    
    public function testAnalyzeByTimeAndDistance(): void
    {
        // Données de test pour le résultat d'agrégation
        $aggregationResult = [
            [
                'summary' => [
                    'totalDrivers' => 50,
                    'totalTrips' => 1200,
                    'totalDistance' => 45000.75,
                    'totalDuration' => 60000,
                    'totalEarnings' => 15000.50,
                    'averageDistancePerTrip' => 37.50,
                    'averageEarningsPerTrip' => 12.50
                ],
                'tripsByDayOfWeek' => [
                    'monday' => 150,
                    'tuesday' => 180,
                    'wednesday' => 200,
                    'thursday' => 210,
                    'friday' => 260,
                    'saturday' => 120,
                    'sunday' => 80
                ],
                'monthlyStats' => [
                    ['month' => '2023-10', 'trips' => 400, 'earnings' => 5000.25],
                    ['month' => '2023-11', 'trips' => 380, 'earnings' => 4800.75],
                    ['month' => '2023-12', 'trips' => 420, 'earnings' => 5200.50]
                ],
                'distanceAnalysis' => [
                    [
                        'range' => 'short',
                        'minDistance' => 0,
                        'maxDistance' => 50,
                        'stats' => [
                            ['distanceRange' => 'short', 'distance' => 15000.25, 'trips' => 600, 'earnings' => 6000.25]
                        ]
                    ],
                    [
                        'range' => 'medium',
                        'minDistance' => 50,
                        'maxDistance' => 200,
                        'stats' => [
                            ['distanceRange' => 'medium', 'distance' => 20000.50, 'trips' => 400, 'earnings' => 5000.25]
                        ]
                    ],
                    [
                        'range' => 'long',
                        'minDistance' => 200,
                        'maxDistance' => 500,
                        'stats' => [
                            ['distanceRange' => 'long', 'distance' => 10000.00, 'trips' => 200, 'earnings' => 4000.00]
                        ]
                    ]
                ]
            ]
        ];
        
        // Configuration du mock pour simuler un résultat d'agrégation
        $cursorMock = $this->createMock(Cursor::class);
        $cursorMock->expects($this->once())
            ->method('toArray')
            ->willReturn($aggregationResult);
        
        $this->collectionMock->expects($this->once())
            ->method('aggregate')
            ->willReturn($cursorMock);
        
        // Options pour l'analyse
        $options = [
            'timeSlots' => [
                'morning' => ['start' => 6, 'end' => 10],
                'evening' => ['start' => 18, 'end' => 22]
            ],
            'distanceRanges' => [
                'short' => ['min' => 0, 'max' => 50],
                'medium' => ['min' => 50, 'max' => 200],
                'long' => ['min' => 200, 'max' => 500]
            ]
        ];
        
        // Appel de la méthode testée
        $result = $this->tripStatsService->analyzeByTimeAndDistance($options);
        
        // Assertions
        $this->assertEquals(50, $result['summary']['totalDrivers']);
        $this->assertEquals(1200, $result['summary']['totalTrips']);
        $this->assertEquals(45000.75, $result['summary']['totalDistance']);
        $this->assertEquals(15000.50, $result['summary']['totalEarnings']);
        
        $this->assertEquals(260, $result['tripsByDayOfWeek']['friday']);
        
        $this->assertCount(3, $result['monthlyStats']);
        $this->assertEquals('2023-10', $result['monthlyStats'][0]['month']);
        
        $this->assertEquals('short', $result['distanceAnalysis'][0]['range']);
        $this->assertEquals(600, $result['distanceAnalysis'][0]['stats'][0]['trips']);
    }
} 