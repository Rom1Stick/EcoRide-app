<?php

namespace Tests\Unit\Repositories\NoSQL;

use App\Core\Database\MongoConnection;
use App\Core\Exceptions\ValidationException;
use App\Models\Documents\ReviewDocument;
use App\Repositories\NoSQL\ReviewRepository;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection;
use MongoDB\InsertOneResult;
use MongoDB\DeleteResult;
use MongoDB\UpdateResult;
use PHPUnit\Framework\TestCase;
use MongoDB\Driver\Cursor;

class ReviewRepositoryTest extends TestCase
{
    private $mockMongoConnection;
    private $mockCollection;
    private $reviewRepository;
    
    protected function setUp(): void
    {
        // Créer des mocks pour MongoConnection et Collection
        $this->mockMongoConnection = $this->createMock(MongoConnection::class);
        $this->mockCollection = $this->createMock(Collection::class);
        
        // Configurer le mock de MongoConnection pour renvoyer le mock de Collection
        $this->mockMongoConnection->method('getCollection')
            ->with('reviews')
            ->willReturn($this->mockCollection);
        
        // Créer une réflexion sur la classe MongoConnection pour injecter notre mock
        $reflectionClass = new \ReflectionClass(MongoConnection::class);
        $instanceProperty = $reflectionClass->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, $this->mockMongoConnection);
        
        // Créer l'instance du repository à tester
        $this->reviewRepository = new ReviewRepository();
    }
    
    protected function tearDown(): void
    {
        // Réinitialiser l'instance de MongoConnection
        $reflectionClass = new \ReflectionClass(MongoConnection::class);
        $instanceProperty = $reflectionClass->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, null);
    }
    
    public function testFindByIdReturnsReviewWhenFound(): void
    {
        // Créer un ID de test
        $objectId = new ObjectId('6070fa036377142bd8d23bef');
        
        // Simuler un document trouvé
        $document = [
            '_id' => $objectId,
            'userId' => 1,
            'tripId' => 2,
            'comment' => 'Excellent trajet',
            'rating' => 5,
            'status' => 'approved',
            'metadata' => [],
            'createdAt' => '2023-01-01T12:00:00.000Z'
        ];
        
        // Configurer le comportement du mock Collection
        $this->mockCollection->expects($this->once())
            ->method('findOne')
            ->with(['_id' => $objectId])
            ->willReturn((object)$document);
        
        // Exécuter la méthode à tester
        $review = $this->reviewRepository->findById($objectId);
        
        // Vérifier le résultat
        $this->assertInstanceOf(ReviewDocument::class, $review);
        $this->assertEquals('6070fa036377142bd8d23bef', $review->getId());
        $this->assertEquals(1, $review->getUserId());
        $this->assertEquals(2, $review->getTripId());
        $this->assertEquals('Excellent trajet', $review->getComment());
        $this->assertEquals(5, $review->getRating());
        $this->assertEquals('approved', $review->getStatus());
    }
    
    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        // Créer un ID de test
        $objectId = new ObjectId('6070fa036377142bd8d23bef');
        
        // Configurer le comportement du mock Collection
        $this->mockCollection->expects($this->once())
            ->method('findOne')
            ->with(['_id' => $objectId])
            ->willReturn(null);
        
        // Exécuter la méthode à tester
        $review = $this->reviewRepository->findById($objectId);
        
        // Vérifier le résultat
        $this->assertNull($review);
    }
    
    public function testCreateThrowsValidationExceptionWhenInvalidData(): void
    {
        // Créer un avis avec une note invalide
        $review = new ReviewDocument(1, 2, 'Commentaire', 10, 'approved');
        
        // Vérifier que la méthode create lance une ValidationException
        $this->expectException(ValidationException::class);
        
        // Exécuter la méthode à tester
        $this->reviewRepository->create($review);
    }
    
    public function testCreateReturnsIdWhenSuccessful(): void
    {
        // Créer un avis valide
        $review = new ReviewDocument(1, 2, 'Excellent trajet', 5, 'approved');
        
        // Simuler qu'aucun avis n'existe déjà pour cet utilisateur et ce trajet
        $this->mockCollection->expects($this->once())
            ->method('findOne')
            ->with(['userId' => 1, 'tripId' => 2])
            ->willReturn(null);
        
        // Créer un mock pour InsertOneResult
        $mockInsertResult = $this->createMock(InsertOneResult::class);
        $mockInsertResult->method('isAcknowledged')->willReturn(true);
        $mockInsertResult->method('getInsertedId')->willReturn(new ObjectId('6070fa036377142bd8d23bef'));
        
        // Configurer le comportement du mock Collection
        $this->mockCollection->expects($this->once())
            ->method('insertOne')
            ->willReturn($mockInsertResult);
        
        // Exécuter la méthode à tester
        $id = $this->reviewRepository->create($review);
        
        // Vérifier le résultat
        $this->assertEquals('6070fa036377142bd8d23bef', $id);
    }
    
    public function testUpdateStatusWorksCorrectly(): void
    {
        // Créer un ID de test
        $objectId = new ObjectId('6070fa036377142bd8d23bef');
        
        // Créer un mock pour UpdateResult
        $mockUpdateResult = $this->createMock(UpdateResult::class);
        $mockUpdateResult->method('isAcknowledged')->willReturn(true);
        $mockUpdateResult->method('getModifiedCount')->willReturn(1);
        
        // Configurer le comportement du mock Collection
        $this->mockCollection->expects($this->once())
            ->method('updateOne')
            ->willReturn($mockUpdateResult);
        
        // Exécuter la méthode à tester
        $result = $this->reviewRepository->updateStatus($objectId, 'approved');
        
        // Vérifier le résultat
        $this->assertTrue($result);
    }
    
    public function testGetAverageRatingForTripWorksCorrectly(): void
    {
        // Créer un mock pour Cursor
        $mockCursor = $this->createMock(Cursor::class);
        $mockCursor->method('toArray')->willReturn([['averageRating' => 4.5]]);
        
        // Configurer le comportement du mock Collection
        $this->mockCollection->expects($this->once())
            ->method('aggregate')
            ->willReturn($mockCursor);
        
        // Exécuter la méthode à tester
        $average = $this->reviewRepository->getAverageRatingForTrip(2);
        
        // Vérifier le résultat
        $this->assertEquals(4.5, $average);
    }
    
    // D'autres tests pour update, delete, etc.
} 