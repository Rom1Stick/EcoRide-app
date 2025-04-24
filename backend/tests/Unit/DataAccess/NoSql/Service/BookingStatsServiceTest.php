<?php

namespace Tests\Unit\DataAccess\NoSql\Service;

use App\DataAccess\NoSql\Model\BookingStats;
use App\DataAccess\NoSql\Service\BookingStatsService;
use App\DataAccess\NoSql\Service\MongoServiceInterface;
use MongoDB\Collection;
use MongoDB\Driver\Cursor;
use PHPUnit\Framework\TestCase;
use MongoDB\BSON\ObjectId;
use App\DataAccess\NoSql\Service\MongoConnection;

/**
 * @group mongodb
 */
class BookingStatsServiceTest extends TestCase
{
    private $mockCollection;
    private $mockMongoConnection;
    private $service;
    
    protected function setUp(): void
    {
        $this->mockCollection = $this->createMock(Collection::class);
        $this->mockMongoConnection = $this->createMock(MongoConnection::class);
        $this->mockMongoConnection->method('getCollection')->willReturn($this->mockCollection);
        
        $this->service = new BookingStatsService($this->mockMongoConnection);
    }
    
    public function testFindOrCreateByUserId_Existing(): void
    {
        // Données de test
        $userId = 123;
        $expectedResult = [
            '_id' => new ObjectId('6579a20a3853a3e2a7d0fc1a'),
            'user_id' => $userId,
            'total_bookings' => 5,
            'total_seats_booked' => 8,
            'total_amount_spent' => 125.50,
            'bookings_by_status' => [
                'confirmed' => 3,
                'cancelled' => 1,
                'pending' => 1
            ]
        ];
        
        // Configuration du mock
        $this->mockCollection->expects($this->once())
            ->method('findOne')
            ->with(['user_id' => $userId])
            ->willReturn($expectedResult);
        
        // Appel de la méthode testée
        $bookingStats = $this->service->findOrCreateByUserId($userId);
        
        // Assertions
        $this->assertInstanceOf(BookingStats::class, $bookingStats);
        $this->assertEquals($userId, $bookingStats->getUserId());
        $this->assertEquals(5, $bookingStats->getTotalBookings());
        $this->assertEquals(8, $bookingStats->getTotalSeatsBooked());
        $this->assertEquals(125.50, $bookingStats->getTotalAmountSpent());
    }
    
    public function testFindOrCreateByUserId_New(): void
    {
        // Données de test
        $userId = 456;
        
        // Configuration des mocks
        $this->collectionMock->expects($this->once())
            ->method('findOne')
            ->with(['user_id' => $userId])
            ->willReturn(null);
        
        $this->collectionMock->expects($this->once())
            ->method('insertOne')
            ->willReturn((object)['insertedCount' => 1, 'insertedId' => new ObjectId('6579a20a3853a3e2a7d0fc1b')]);
        
        // Appel de la méthode testée
        $bookingStats = $this->bookingStatsService->findOrCreateByUserId($userId);
        
        // Assertions
        $this->assertInstanceOf(BookingStats::class, $bookingStats);
        $this->assertEquals($userId, $bookingStats->getUserId());
        $this->assertEquals(0, $bookingStats->getTotalBookings());
        $this->assertEquals(0, $bookingStats->getTotalSeatsBooked());
        $this->assertEquals(0, $bookingStats->getTotalAmountSpent());
    }
    
    public function testUpdateForNewBooking(): void
    {
        // Données de test
        $userId = 789;
        $existingStats = new BookingStats($userId);
        $existingStats->setId('6579a20a3853a3e2a7d0fc1c');
        
        $bookingData = [
            'seats' => 2,
            'amount' => 45.75,
            'status' => 'confirmed',
            'destination' => 'Lyon',
            'date' => '2023-12-25 14:00:00',
            'distance' => 150.5,
            'co2_saved' => 15.2
        ];
        
        // Configuration des mocks
        $this->collectionMock->expects($this->once())
            ->method('findOne')
            ->with(['user_id' => $userId])
            ->willReturn((array)$existingStats);
        
        $this->collectionMock->expects($this->once())
            ->method('updateOne')
            ->willReturn((object)['modifiedCount' => 1, 'matchedCount' => 1]);
        
        // Appel de la méthode testée
        $updatedStats = $this->bookingStatsService->updateForNewBooking($userId, $bookingData);
        
        // Assertions
        $this->assertInstanceOf(BookingStats::class, $updatedStats);
        $this->assertEquals($userId, $updatedStats->getUserId());
        $this->assertEquals(1, $updatedStats->getTotalBookings());
        $this->assertEquals(2, $updatedStats->getTotalSeatsBooked());
        $this->assertEquals(45.75, $updatedStats->getTotalAmountSpent());
        $this->assertEquals(150.5, $updatedStats->getTotalDistance());
        $this->assertEquals(15.2, $updatedStats->getTotalCO2Saved());
        
        // Vérifier le statut
        $bookingsByStatus = $updatedStats->getBookingsByStatus();
        $this->assertEquals(1, $bookingsByStatus['confirmed']);
        
        // Vérifier le jour de la semaine
        $bookingsByDayOfWeek = $updatedStats->getBookingsByDayOfWeek();
        $this->assertEquals(1, $bookingsByDayOfWeek['monday']); // 25 décembre 2023 est un lundi
        
        // Vérifier les destinations
        $topDestinations = $updatedStats->getTopDestinations();
        $this->assertCount(1, $topDestinations);
        $this->assertEquals('Lyon', $topDestinations[0]['destination']);
        $this->assertEquals(1, $topDestinations[0]['count']);
        
        // Vérifier l'historique mensuel
        $monthlyHistory = $updatedStats->getMonthlyBookingHistory();
        $this->assertArrayHasKey('2023-12', $monthlyHistory);
        $this->assertEquals(1, $monthlyHistory['2023-12']['count']);
        $this->assertEquals(45.75, $monthlyHistory['2023-12']['amount']);
    }
    
    public function testUpdateForStatusChange(): void
    {
        // Données de test
        $userId = 123;
        $existingStats = new BookingStats($userId);
        $existingStats->setId('6579a20a3853a3e2a7d0fc1d');
        
        // Ajouter des statistiques initiales pour le test
        $bookingsByStatus = ['pending' => 2, 'confirmed' => 3];
        $reflection = new \ReflectionClass($existingStats);
        $property = $reflection->getProperty('bookingsByStatus');
        $property->setAccessible(true);
        $property->setValue($existingStats, $bookingsByStatus);
        
        // Configuration des mocks
        $this->collectionMock->expects($this->once())
            ->method('findOne')
            ->with(['user_id' => $userId])
            ->willReturn((array)$existingStats);
        
        $this->collectionMock->expects($this->once())
            ->method('updateOne')
            ->willReturn((object)['modifiedCount' => 1, 'matchedCount' => 1]);
        
        // Appel de la méthode testée
        $updatedStats = $this->bookingStatsService->updateForStatusChange($userId, 'pending', 'confirmed', 1);
        
        // Assertions
        $this->assertInstanceOf(BookingStats::class, $updatedStats);
        $this->assertEquals($userId, $updatedStats->getUserId());
        
        // Vérifier le changement de statut
        $updatedBookingsByStatus = $updatedStats->getBookingsByStatus();
        $this->assertEquals(1, $updatedBookingsByStatus['pending']);
        $this->assertEquals(4, $updatedBookingsByStatus['confirmed']);
    }
    
    public function testGetTopGlobalDestinations(): void
    {
        // Données de test
        $expectedResult = [
            ['destination' => 'Paris', 'count' => 120],
            ['destination' => 'Lyon', 'count' => 90],
            ['destination' => 'Marseille', 'count' => 75]
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
        $result = $this->bookingStatsService->getTopGlobalDestinations(3);
        
        // Assertions
        $this->assertCount(3, $result);
        $this->assertEquals('Paris', $result[0]['destination']);
        $this->assertEquals(120, $result[0]['count']);
        $this->assertEquals('Lyon', $result[1]['destination']);
    }
    
    public function testGetGlobalStats(): void
    {
        // Données de test pour le résultat d'agrégation
        $aggregationResult = [
            [
                'total_users' => 150,
                'total_bookings' => 1200,
                'total_seats_booked' => 1800,
                'total_amount_spent' => 25000.75,
                'total_co2_saved' => 4500.50,
                'total_distance' => 45000.50,
                'bookings_by_status' => [
                    'confirmed' => 950,
                    'pending' => 150,
                    'cancelled' => 100
                ],
                'bookings_by_day_of_week' => [
                    'monday' => 150,
                    'tuesday' => 180,
                    'wednesday' => 200,
                    'thursday' => 210,
                    'friday' => 260,
                    'saturday' => 120,
                    'sunday' => 80
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
        
        // Appel de la méthode testée
        $result = $this->bookingStatsService->getGlobalStats();
        
        // Assertions
        $this->assertEquals(150, $result['total_users']);
        $this->assertEquals(1200, $result['total_bookings']);
        $this->assertEquals(1800, $result['total_seats_booked']);
        $this->assertEquals(25000.75, $result['total_amount_spent']);
        $this->assertEquals(4500.50, $result['total_co2_saved']);
        $this->assertEquals(45000.50, $result['total_distance']);
        
        $this->assertEquals(950, $result['bookings_by_status']['confirmed']);
        $this->assertEquals(150, $result['bookings_by_status']['pending']);
        $this->assertEquals(100, $result['bookings_by_status']['cancelled']);
        
        $this->assertEquals(260, $result['bookings_by_day_of_week']['friday']);
    }
    
    public function testGetMonthlyBookingTrend(): void
    {
        // Données de test pour le résultat d'agrégation
        $aggregationResult = [
            ['month' => '2023-10', 'bookings' => 120, 'amount' => 3000.50],
            ['month' => '2023-11', 'bookings' => 140, 'amount' => 3500.75],
            ['month' => '2023-12', 'bookings' => 160, 'amount' => 4000.25]
        ];
        
        // Configuration du mock pour simuler un résultat d'agrégation
        $cursorMock = $this->createMock(Cursor::class);
        $cursorMock->expects($this->once())
            ->method('toArray')
            ->willReturn($aggregationResult);
        
        $this->collectionMock->expects($this->once())
            ->method('aggregate')
            ->willReturn($cursorMock);
        
        // Appel de la méthode testée
        $result = $this->bookingStatsService->getMonthlyBookingTrend(3);
        
        // Assertions
        $this->assertCount(3, $result);
        $this->assertEquals('2023-10', $result[0]['month']);
        $this->assertEquals(120, $result[0]['bookings']);
        $this->assertEquals(3000.50, $result[0]['amount']);
        
        $this->assertEquals('2023-11', $result[1]['month']);
        $this->assertEquals(140, $result[1]['bookings']);
        
        $this->assertEquals('2023-12', $result[2]['month']);
        $this->assertEquals(160, $result[2]['bookings']);
    }
    
    public function testDeleteByUserId(): void
    {
        // Données de test
        $userId = 123;
        
        // Configuration du mock
        $this->collectionMock->expects($this->once())
            ->method('deleteOne')
            ->with(['user_id' => $userId])
            ->willReturn((object)['deletedCount' => 1]);
        
        // Appel de la méthode testée
        $result = $this->bookingStatsService->deleteByUserId($userId);
        
        // Assertions
        $this->assertTrue($result);
    }
    
    public function testDeleteByUserId_NonExistent(): void
    {
        // Données de test
        $userId = 456;
        
        // Configuration du mock
        $this->collectionMock->expects($this->once())
            ->method('deleteOne')
            ->with(['user_id' => $userId])
            ->willReturn((object)['deletedCount' => 0]);
        
        // Appel de la méthode testée
        $result = $this->bookingStatsService->deleteByUserId($userId);
        
        // Assertions
        $this->assertFalse($result);
    }
} 