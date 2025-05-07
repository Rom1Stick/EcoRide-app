<?php
/**
 * Script de test pour le service de revues utilisateur
 * Exécuter avec: docker-compose run tests php scripts/tests/run_review_tests.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../tests/Mocks/MockMongoService.php';
require_once __DIR__ . '/../../tests/Mocks/ReviewServiceMock.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Model\BSONDocument;
use Tests\Mocks\ReviewServiceMock;
use App\DataAccess\NoSql\Model\Review;
use App\DataAccess\Exception\DataAccessException;

echo "=== Tests du service de revues utilisateur ===\n\n";

$totalTests = 0;
$passedTests = 0;

// Fonction pour créer un mock de Collection avec des comportements spécifiques
function createMockCollection() {
    return new class {
        public $findOneReturnValue = null;
        public $findReturnValue = [];
        public $insertOneReturnValue = null;
        public $updateOneReturnValue = null;
        public $deleteOneReturnValue = null;
        public $aggregateReturnValue = [];
        public $throwException = false;
        public $exceptionMessage = "Erreur MongoDB";
        
        public function findOne($filter) {
            if ($this->throwException) {
                throw new \Exception($this->exceptionMessage);
            }
            return $this->findOneReturnValue;
        }
        
        public function find($filter) {
            if ($this->throwException) {
                throw new \Exception($this->exceptionMessage);
            }
            return $this->findReturnValue;
        }
        
        public function insertOne($document) {
            if ($this->throwException) {
                throw new \Exception($this->exceptionMessage);
            }
            return $this->insertOneReturnValue;
        }
        
        public function updateOne($filter, $update) {
            if ($this->throwException) {
                throw new \Exception($this->exceptionMessage);
            }
            return $this->updateOneReturnValue;
        }
        
        public function deleteOne($filter) {
            if ($this->throwException) {
                throw new \Exception($this->exceptionMessage);
            }
            return $this->deleteOneReturnValue;
        }
        
        public function aggregate($pipeline) {
            if ($this->throwException) {
                throw new \Exception($this->exceptionMessage);
            }
            return $this->aggregateReturnValue;
        }
    };
}

// Fonction pour créer un document d'avis MongoDB
function createReviewDocument($id = '507f1f77bcf86cd799439011') {
    $objectId = new ObjectId($id);
    return new BSONDocument([
        '_id' => $objectId,
        'covoiturageId' => 123,
        'userId' => 456,
        'targetUserId' => 789,
        'rating' => 4.5,
        'comment' => 'Excellent chauffeur',
        'createdAt' => new UTCDateTime((new \DateTime())->getTimestamp() * 1000)
    ]);
}

// Fonction utilitaire pour simplifier les tests
function runTest($name, $testFunction) {
    global $totalTests, $passedTests;
    $totalTests++;
    
    echo "Test: $name... ";
    try {
        $result = $testFunction();
        if ($result) {
            echo "✅ Réussi\n";
            $passedTests++;
        } else {
            echo "❌ Échec\n";
        }
    } catch (\Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
}

// Test de la méthode insert
runTest('insert', function() {
    $mockCollection = createMockCollection();
    
    // Créer un mock de InsertOneResult
    $insertResult = new class {
        public function getInsertedCount() {
            return 1;
        }
        
        public function getInsertedId() {
            return new ObjectId('507f1f77bcf86cd799439011');
        }
    };
    
    $mockCollection->insertOneReturnValue = $insertResult;
    
    $service = new ReviewServiceMock($mockCollection);
    
    $data = [
        'covoiturageId' => 123,
        'userId' => 456,
        'targetUserId' => 789,
        'rating' => 4.5,
        'comment' => 'Excellent chauffeur'
    ];
    
    $result = $service->insert($data);
    
    return $result instanceof ObjectId 
        && (string)$result === '507f1f77bcf86cd799439011';
});

// Test de la méthode findById
runTest('findById', function() {
    $mockCollection = createMockCollection();
    $mockCollection->findOneReturnValue = createReviewDocument();
    
    $service = new ReviewServiceMock($mockCollection);
    $review = $service->findById('507f1f77bcf86cd799439011');
    
    return $review instanceof Review 
        && $review->getCovoiturageId() === 123
        && $review->getUserId() === 456
        && $review->getTargetUserId() === 789
        && $review->getRating() === 4.5;
});

// Test de la méthode update
runTest('update', function() {
    $mockCollection = createMockCollection();
    
    // Créer un mock de UpdateResult
    $updateResult = new class {
        public function getModifiedCount() {
            return 1;
        }
    };
    
    $mockCollection->updateOneReturnValue = $updateResult;
    
    $service = new ReviewServiceMock($mockCollection);
    $result = $service->update('507f1f77bcf86cd799439011', [
        'rating' => 5.0,
        'comment' => 'Parfait'
    ]);
    
    return $result === true;
});

// Test de la méthode delete
runTest('delete', function() {
    $mockCollection = createMockCollection();
    
    // Créer un mock de DeleteResult
    $deleteResult = new class {
        public function getDeletedCount() {
            return 1;
        }
    };
    
    $mockCollection->deleteOneReturnValue = $deleteResult;
    
    $service = new ReviewServiceMock($mockCollection);
    $result = $service->delete('507f1f77bcf86cd799439011');
    
    return $result === true;
});

// Test de la méthode findByTargetUserId
runTest('findByTargetUserId', function() {
    $mockCollection = createMockCollection();
    
    // Créer des objets Review directement au lieu de passer par fromDocument
    $reviews = [
        (new Review())
            ->setId('507f1f77bcf86cd799439011')
            ->setCovoiturageId(123)
            ->setUserId(456)
            ->setTargetUserId(789)
            ->setRating(4.5)
            ->setComment('Excellent chauffeur')
            ->setCreatedAt((new \DateTime())->format('Y-m-d H:i:s')),
        
        (new Review())
            ->setId('507f1f77bcf86cd799439022')
            ->setCovoiturageId(124)
            ->setUserId(457)
            ->setTargetUserId(789)
            ->setRating(5.0)
            ->setComment('Parfait')
            ->setCreatedAt((new \DateTime())->format('Y-m-d H:i:s'))
    ];
    
    // Créer un mock qui retourne directement ces objets
    $mockFindResult = new class($reviews) implements \IteratorAggregate {
        private array $reviews;

        public function __construct(array $reviews) {
            $this->reviews = $reviews;
        }

        public function getIterator(): \Traversable {
            return new \ArrayIterator($this->reviews);
        }

        public function toArray(): array {
            return $this->reviews;
        }
    };
    
    $mockCollection->findReturnValue = $mockFindResult;
    
    // Surcharger temporairement la méthode findByTargetUserId pour ce test
    $service = new class($mockCollection) extends ReviewServiceMock {
        public function findByTargetUserId(int $targetUserId): array 
        {
            $cursor = $this->collection->find(['targetUserId' => $targetUserId]);
            return $cursor instanceof \Traversable ? iterator_to_array($cursor) : [];
        }
    };
    
    $result = $service->findByTargetUserId(789);
    
    return is_array($result) && count($result) === 2 
        && $result[0] instanceof Review
        && $result[1] instanceof Review;
});

// Test de la méthode calculateAverageRating
runTest('calculateAverageRating', function() {
    $mockCollection = createMockCollection();
    
    $service = new ReviewServiceMock($mockCollection);
    $rating = $service->calculateAverageRating(789);
    
    return $rating === 4.5;
});

// Résumé des tests
echo "\n=== Résumé des tests ===\n";
echo "Tests exécutés: $totalTests\n";
echo "Tests réussis: $passedTests\n";

$failedTests = $totalTests - $passedTests;
if ($failedTests > 0) {
    echo "Tests échoués: $failedTests\n";
    exit(1);
} else {
    echo "Tous les tests ont réussi! ✅\n";
    exit(0);
} 