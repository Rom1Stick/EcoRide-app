<?php

namespace Tests\Unit\DataAccess\NoSql\Service;

use App\DataAccess\NoSql\Model\GeoData;
use App\DataAccess\NoSql\Service\GeoDataService;
use App\DataAccess\NoSql\Service\MongoServiceInterface;
use MongoDB\Collection;
use MongoDB\Driver\Cursor;
use PHPUnit\Framework\TestCase;
use MongoDB\BSON\ObjectId;

/**
 * @group mongodb
 */
class GeoDataServiceTest extends TestCase
{
    private $mongoConnectionMock;
    private $collectionMock;
    private $geoDataService;
    
    protected function setUp(): void
    {
        $this->mongoConnectionMock = $this->createMock(MongoServiceInterface::class);
        $this->collectionMock = $this->createMock(Collection::class);
        
        // Configuration pour créer les index
        $this->collectionMock->expects($this->exactly(3))
            ->method('createIndex')
            ->willReturn('index_name');
        
        $this->mongoConnectionMock->expects($this->once())
            ->method('getCollection')
            ->with('geo_data')
            ->willReturn($this->collectionMock);
        
        $this->geoDataService = new GeoDataService($this->mongoConnectionMock);
    }
    
    public function testSave_New(): void
    {
        // Données de test
        $geoData = new GeoData();
        $geoData->setType(GeoDataService::TYPE_ITINERAIRE);
        $geoData->setCovoiturageId(123);
        $geoData->setGeometry([
            'type' => 'LineString',
            'coordinates' => [
                [2.349, 48.864], // Paris
                [4.835, 45.764]  // Lyon
            ]
        ]);
        $geoData->setMetadata([
            'distance' => 450.5,
            'duration' => 240
        ]);
        
        // Configuration du mock
        $this->collectionMock->expects($this->once())
            ->method('insertOne')
            ->willReturn((object)['insertedCount' => 1, 'insertedId' => new ObjectId('6579a20a3853a3e2a7d0fc1a')]);
        
        // Appel de la méthode testée
        $savedGeoData = $this->geoDataService->save($geoData);
        
        // Assertions
        $this->assertInstanceOf(GeoData::class, $savedGeoData);
        $this->assertEquals('6579a20a3853a3e2a7d0fc1a', $savedGeoData->getId());
        $this->assertEquals(GeoDataService::TYPE_ITINERAIRE, $savedGeoData->getType());
        $this->assertEquals(123, $savedGeoData->getCovoiturageId());
    }
    
    public function testSave_Update(): void
    {
        // Données de test
        $geoData = new GeoData();
        $geoData->setId('6579a20a3853a3e2a7d0fc1b');
        $geoData->setType(GeoDataService::TYPE_POINT_INTERET);
        $geoData->setCovoiturageId(456);
        $geoData->setGeometry([
            'type' => 'Point',
            'coordinates' => [2.349, 48.864] // Paris
        ]);
        $geoData->setMetadata([
            'name' => 'Station de recharge',
            'description' => 'Station de recharge électrique'
        ]);
        
        // Configuration du mock
        $this->collectionMock->expects($this->once())
            ->method('updateOne')
            ->willReturn((object)['modifiedCount' => 1, 'matchedCount' => 1]);
        
        // Appel de la méthode testée
        $updatedGeoData = $this->geoDataService->save($geoData);
        
        // Assertions
        $this->assertInstanceOf(GeoData::class, $updatedGeoData);
        $this->assertEquals('6579a20a3853a3e2a7d0fc1b', $updatedGeoData->getId());
        $this->assertEquals(GeoDataService::TYPE_POINT_INTERET, $updatedGeoData->getType());
        $this->assertEquals(456, $updatedGeoData->getCovoiturageId());
    }
    
    public function testFindById(): void
    {
        // Données de test
        $id = '6579a20a3853a3e2a7d0fc1c';
        $expectedResult = [
            '_id' => new ObjectId($id),
            'type' => GeoDataService::TYPE_ITINERAIRE,
            'covoiturageId' => 789,
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => [
                    [2.349, 48.864], // Paris
                    [4.835, 45.764]  // Lyon
                ]
            ],
            'metadata' => [
                'distance' => 450.5,
                'duration' => 240
            ]
        ];
        
        // Configuration du mock
        $this->collectionMock->expects($this->once())
            ->method('findOne')
            ->with(['_id' => new ObjectId($id)])
            ->willReturn($expectedResult);
        
        // Appel de la méthode testée
        $geoData = $this->geoDataService->findById($id);
        
        // Assertions
        $this->assertInstanceOf(GeoData::class, $geoData);
        $this->assertEquals($id, $geoData->getId());
        $this->assertEquals(GeoDataService::TYPE_ITINERAIRE, $geoData->getType());
        $this->assertEquals(789, $geoData->getCovoiturageId());
    }
    
    public function testFindByCovoiturageId(): void
    {
        // Données de test
        $covoiturageId = 789;
        $cursor = [
            [
                '_id' => new ObjectId('6579a20a3853a3e2a7d0fc1d'),
                'type' => GeoDataService::TYPE_ITINERAIRE,
                'covoiturageId' => $covoiturageId,
                'geometry' => [
                    'type' => 'LineString',
                    'coordinates' => [
                        [2.349, 48.864], // Paris
                        [4.835, 45.764]  // Lyon
                    ]
                ]
            ],
            [
                '_id' => new ObjectId('6579a20a3853a3e2a7d0fc1e'),
                'type' => GeoDataService::TYPE_POINT_INTERET,
                'covoiturageId' => $covoiturageId,
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [3.87, 47.08] // Quelque part sur le trajet
                ]
            ]
        ];
        
        // Configuration du mock
        $cursorMock = $this->createMock(Cursor::class);
        $cursorMock->expects($this->any())
            ->method('current')
            ->will($this->onConsecutiveCalls($cursor[0], $cursor[1], null));
        $cursorMock->expects($this->any())
            ->method('rewind')
            ->willReturn(true);
        $cursorMock->expects($this->any())
            ->method('valid')
            ->will($this->onConsecutiveCalls(true, true, false));
        $cursorMock->expects($this->any())
            ->method('next')
            ->willReturn(true);
        
        $this->collectionMock->expects($this->once())
            ->method('find')
            ->with(['covoiturageId' => $covoiturageId])
            ->willReturn($cursorMock);
        
        // Appel de la méthode testée
        $results = $this->geoDataService->findByCovoiturageId($covoiturageId);
        
        // Assertions
        $this->assertCount(2, $results);
        $this->assertInstanceOf(GeoData::class, $results[0]);
        $this->assertInstanceOf(GeoData::class, $results[1]);
        $this->assertEquals(GeoDataService::TYPE_ITINERAIRE, $results[0]->getType());
        $this->assertEquals(GeoDataService::TYPE_POINT_INTERET, $results[1]->getType());
        $this->assertEquals($covoiturageId, $results[0]->getCovoiturageId());
    }
    
    public function testFindItineraireByCovoiturageId(): void
    {
        // Données de test
        $covoiturageId = 789;
        $expectedResult = [
            '_id' => new ObjectId('6579a20a3853a3e2a7d0fc1d'),
            'type' => GeoDataService::TYPE_ITINERAIRE,
            'covoiturageId' => $covoiturageId,
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => [
                    [2.349, 48.864], // Paris
                    [4.835, 45.764]  // Lyon
                ]
            ]
        ];
        
        // Configuration du mock
        $this->collectionMock->expects($this->once())
            ->method('findOne')
            ->with([
                'covoiturageId' => $covoiturageId,
                'type' => GeoDataService::TYPE_ITINERAIRE
            ])
            ->willReturn($expectedResult);
        
        // Appel de la méthode testée
        $geoData = $this->geoDataService->findItineraireByCovoiturageId($covoiturageId);
        
        // Assertions
        $this->assertInstanceOf(GeoData::class, $geoData);
        $this->assertEquals('6579a20a3853a3e2a7d0fc1d', $geoData->getId());
        $this->assertEquals(GeoDataService::TYPE_ITINERAIRE, $geoData->getType());
        $this->assertEquals($covoiturageId, $geoData->getCovoiturageId());
    }
    
    public function testFindPointsInteretNear(): void
    {
        // Données de test
        $longitude = 2.35;
        $latitude = 48.86;
        $distanceKm = 10;
        
        $cursor = [
            [
                '_id' => new ObjectId('6579a20a3853a3e2a7d0fc1f'),
                'type' => GeoDataService::TYPE_POINT_INTERET,
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [2.34, 48.85]
                ],
                'metadata' => [
                    'name' => 'Station A',
                    'type' => 'charge'
                ]
            ],
            [
                '_id' => new ObjectId('6579a20a3853a3e2a7d0fc20'),
                'type' => GeoDataService::TYPE_POINT_INTERET,
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [2.36, 48.87]
                ],
                'metadata' => [
                    'name' => 'Station B',
                    'type' => 'rest'
                ]
            ]
        ];
        
        // Configuration du mock
        $cursorMock = $this->createMock(Cursor::class);
        $cursorMock->expects($this->any())
            ->method('current')
            ->will($this->onConsecutiveCalls($cursor[0], $cursor[1], null));
        $cursorMock->expects($this->any())
            ->method('rewind')
            ->willReturn(true);
        $cursorMock->expects($this->any())
            ->method('valid')
            ->will($this->onConsecutiveCalls(true, true, false));
        $cursorMock->expects($this->any())
            ->method('next')
            ->willReturn(true);
        
        $this->collectionMock->expects($this->once())
            ->method('find')
            ->willReturn($cursorMock);
        
        // Appel de la méthode testée
        $results = $this->geoDataService->findPointsInteretNear($longitude, $latitude, $distanceKm);
        
        // Assertions
        $this->assertCount(2, $results);
        $this->assertInstanceOf(GeoData::class, $results[0]);
        $this->assertInstanceOf(GeoData::class, $results[1]);
        $this->assertEquals('Station A', $results[0]->getMetadata()['name']);
        $this->assertEquals('Station B', $results[1]->getMetadata()['name']);
    }
    
    public function testSaveItineraire(): void
    {
        // Données de test
        $covoiturageId = 123;
        $coordinates = [
            [2.349, 48.864], // Paris
            [3.500, 47.500], // Point intermédiaire
            [4.835, 45.764]  // Lyon
        ];
        $metadata = [
            'distance' => 450.5,
            'duration' => 240
        ];
        
        // Configuration du mock
        $this->collectionMock->expects($this->once())
            ->method('insertOne')
            ->willReturn((object)['insertedCount' => 1, 'insertedId' => new ObjectId('6579a20a3853a3e2a7d0fc21')]);
        
        // Appel de la méthode testée
        $geoData = $this->geoDataService->saveItineraire($covoiturageId, $coordinates, $metadata);
        
        // Assertions
        $this->assertInstanceOf(GeoData::class, $geoData);
        $this->assertEquals('6579a20a3853a3e2a7d0fc21', $geoData->getId());
        $this->assertEquals(GeoDataService::TYPE_ITINERAIRE, $geoData->getType());
        $this->assertEquals($covoiturageId, $geoData->getCovoiturageId());
        $this->assertEquals('LineString', $geoData->getGeometry()['type']);
        $this->assertEquals($coordinates, $geoData->getGeometry()['coordinates']);
        $this->assertEquals($metadata, $geoData->getMetadata());
    }
    
    public function testDeleteByCovoiturageId(): void
    {
        // Données de test
        $covoiturageId = 123;
        
        // Configuration du mock
        $this->collectionMock->expects($this->once())
            ->method('deleteMany')
            ->with(['covoiturageId' => $covoiturageId])
            ->willReturn((object)['deletedCount' => 3]);
        
        // Appel de la méthode testée
        $count = $this->geoDataService->deleteByCovoiturageId($covoiturageId);
        
        // Assertions
        $this->assertEquals(3, $count);
    }
    
    public function testCalculateDistance(): void
    {
        // Coordonnées de Paris et Lyon
        $lon1 = 2.349;
        $lat1 = 48.864;
        $lon2 = 4.835;
        $lat2 = 45.764;
        
        // Appel de la méthode testée
        $distance = $this->geoDataService->calculateDistance($lon1, $lat1, $lon2, $lat2);
        
        // La distance entre Paris et Lyon est d'environ 392 km à vol d'oiseau
        // On accepte une marge d'erreur de 10 km
        $this->assertGreaterThan(380, $distance);
        $this->assertLessThan(405, $distance);
    }
} 