<?php
/**
 * Script de test pour le service de configuration MongoDB
 * Exécuter avec: docker-compose run tests php scripts/tests/run_config_tests.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../tests/Mocks/MockMongoService.php';
require_once __DIR__ . '/../../tests/Mocks/ConfigurationServiceMock.php';

use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;
use Tests\Mocks\ConfigurationServiceMock;
use App\DataAccess\NoSql\Model\Configuration;
use App\DataAccess\Exception\DataAccessException;

echo "=== Tests du service de configuration MongoDB ===\n\n";

$totalTests = 0;
$passedTests = 0;

// Fonction pour créer un mock de Collection avec des comportements spécifiques
function createMockCollection() {
    return new class {
        public $findOneReturnValue = null;
        public $insertOneReturnValue = null;
        public $updateOneReturnValue = null;
        public $deleteOneReturnValue = null;
        public $throwException = false;
        public $exceptionMessage = "Erreur MongoDB";
        
        public function findOne($filter) {
            if ($this->throwException) {
                throw new \Exception($this->exceptionMessage);
            }
            return $this->findOneReturnValue;
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
    };
}

// Fonction pour créer un document de configuration MongoDB
function createConfigDocument($id = '507f1f77bcf86cd799439011', $code = 'test_config', $value = 'test_value') {
    $objectId = new ObjectId($id);
    return new BSONDocument([
        '_id' => $objectId,
        'code' => $code,
        'value' => $value,
        'description' => 'Test configuration',
        'category' => 'test',
        'environment' => 'test',
        'active' => true,
        'createdAt' => new \MongoDB\BSON\UTCDateTime((new \DateTime())->getTimestamp() * 1000)
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

// Test de la méthode findById
runTest('findById', function() {
    $mockCollection = createMockCollection();
    $mockCollection->findOneReturnValue = createConfigDocument();
    
    $service = new ConfigurationServiceMock($mockCollection);
    $config = $service->findById('507f1f77bcf86cd799439011');
    
    return $config instanceof Configuration 
        && $config->getId() === '507f1f77bcf86cd799439011'
        && $config->getCode() === 'test_config'
        && $config->getValue() === 'test_value';
});

// Test de la méthode findById quand aucun document n'est trouvé
runTest('findById - document non trouvé', function() {
    $mockCollection = createMockCollection();
    $mockCollection->findOneReturnValue = null;
    
    $service = new ConfigurationServiceMock($mockCollection);
    $config = $service->findById('507f1f77bcf86cd799439000');
    
    return $config === null;
});

// Test de la méthode findById lançant une exception
runTest('findById - exception', function() {
    $mockCollection = createMockCollection();
    $mockCollection->throwException = true;
    
    $service = new ConfigurationServiceMock($mockCollection);
    
    try {
        $service->findById('507f1f77bcf86cd799439011');
        return false; // Le test échoue si aucune exception n'est lancée
    } catch (DataAccessException $e) {
        return true; // Le test réussit si une DataAccessException est lancée
    }
});

// Test de la méthode findByCode
runTest('findByCode', function() {
    $mockCollection = createMockCollection();
    $mockCollection->findOneReturnValue = createConfigDocument();
    
    $service = new ConfigurationServiceMock($mockCollection);
    $config = $service->findByCode('test_config', 'test');
    
    return $config instanceof Configuration 
        && $config->getCode() === 'test_config'
        && $config->getEnvironment() === 'test';
});

// Test de la méthode save
runTest('save', function() {
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
    
    $service = new ConfigurationServiceMock($mockCollection);
    
    $config = new Configuration();
    $config->setCode('test_config')
        ->setValue('test_value')
        ->setDescription('Test configuration')
        ->setCategory('test')
        ->setEnvironment('test');
    
    $result = $service->save($config);
    
    return $result instanceof Configuration 
        && $result->getId() === '507f1f77bcf86cd799439011'
        && $result->getCode() === 'test_config';
});

// Test de la méthode updateValue
runTest('updateValue', function() {
    $mockCollection = createMockCollection();
    $mockCollection->findOneReturnValue = createConfigDocument();
    
    // Créer un mock de UpdateResult
    $updateResult = new class {
        public function getModifiedCount() {
            return 1;
        }
    };
    
    $mockCollection->updateOneReturnValue = $updateResult;
    
    $service = new ConfigurationServiceMock($mockCollection);
    $result = $service->updateValue('test_config', 'new_value', 'test');
    
    return $result instanceof Configuration 
        && $result->getValue() === 'new_value';
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
    
    $service = new ConfigurationServiceMock($mockCollection);
    $result = $service->delete('507f1f77bcf86cd799439011');
    
    return $result === true;
});

// Test de la méthode findOrCreate (cas où la configuration existe)
runTest('findOrCreate - existant', function() {
    $mockCollection = createMockCollection();
    $mockCollection->findOneReturnValue = createConfigDocument();
    
    $service = new ConfigurationServiceMock($mockCollection);
    $result = $service->findOrCreate('test_config', 'default_value', 'Description', 'test');
    
    return $result instanceof Configuration 
        && $result->getCode() === 'test_config'
        && $result->getValue() === 'test_value'; // Valeur existante, pas default_value
});

// Test de la méthode findOrCreate (cas où la configuration n'existe pas)
runTest('findOrCreate - création', function() {
    $mockCollection = createMockCollection();
    
    // Premier appel: findByCode
    $mockCollection->findOneReturnValue = null;
    
    // Deuxième appel: save
    $insertResult = new class {
        public function getInsertedCount() {
            return 1;
        }
        
        public function getInsertedId() {
            return new ObjectId('507f1f77bcf86cd799439022');
        }
    };
    
    $mockCollection->insertOneReturnValue = $insertResult;
    
    $service = new ConfigurationServiceMock($mockCollection);
    $result = $service->findOrCreate('new_config', 'default_value', 'Description', 'test');
    
    return $result instanceof Configuration 
        && $result->getCode() === 'new_config'
        && $result->getValue() === 'default_value'
        && $result->getId() === '507f1f77bcf86cd799439022';
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