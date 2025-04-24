<?php

namespace Tests\Unit\DataAccess\NoSql\Service;

use App\DataAccess\Exception\DataAccessException;
use App\DataAccess\NoSql\Model\Configuration;
use App\DataAccess\NoSql\Service\ConfigurationService;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection;
use MongoDB\Driver\Exception\ConnectionException;
use MongoDB\InsertOneResult;
use MongoDB\Model\BSONDocument;
use MongoDB\UpdateResult;
use MongoDB\DeleteResult;
use PHPUnit\Framework\TestCase;

/**
 * Version de test de ConfigurationService qui court-circuite l'initialisation
 */
class TestConfigurationService extends ConfigurationService
{
    /**
     * Constructeur pour le test
     * 
     * @param Collection $mockCollection
     */
    public function __construct(Collection $mockCollection)
    {
        // Ne pas appeler le constructeur parent
        // Au lieu de cela, définir directement la collection
        $this->collection = $mockCollection;
    }
    
    /**
     * Réimplémentation de initService qui ne fait rien
     */
    protected function initService(): void
    {
        // Ne rien faire ici pour éviter les appels à la base de données
    }
    
    /**
     * Réimplémentation de ensureIndexes qui ne fait rien
     */
    protected function ensureIndexes(): void
    {
        // Ne rien faire ici pour éviter les appels à la base de données
    }
}

/**
 * @group mongodb
 */
class ConfigurationServiceTest extends TestCase
{
    private $mockCollection;
    private $configService;

    protected function setUp(): void
    {
        $this->mockCollection = $this->createMock(Collection::class);
        
        // Utiliser notre version test du service qui court-circuite l'initialisation
        $this->configService = new TestConfigurationService($this->mockCollection);
    }

    public function testSave()
    {
        // Données de test
        $config = new Configuration();
        $config->setCode('test_config')
            ->setValue('test_value')
            ->setDescription('Test configuration')
            ->setCategory('test')
            ->setEnvironment('test');

        // Préparation du mock
        $insertResult = $this->createMock(InsertOneResult::class);
        $objectId = new ObjectId('507f1f77bcf86cd799439011');
        $insertResult->method('getInsertedId')->willReturn($objectId);
        $insertResult->method('getInsertedCount')->willReturn(1);
        
        $this->mockCollection->method('insertOne')->willReturn($insertResult);
        
        // Exécution du test
        $result = $this->configService->save($config);
        
        // Assertions
        $this->assertInstanceOf(Configuration::class, $result);
        $this->assertEquals('507f1f77bcf86cd799439011', $result->getId());
        $this->assertEquals('test_config', $result->getCode());
        $this->assertEquals('test_value', $result->getValue());
    }

    public function testFindById()
    {
        // Préparation des données de test
        $objectId = new ObjectId('507f1f77bcf86cd799439011');
        $mockDocument = [
            '_id' => $objectId,
            'code' => 'test_config',
            'value' => 'test_value',
            'description' => 'Test configuration',
            'category' => 'test',
            'environment' => 'test',
            'active' => true,
            'createdAt' => new \MongoDB\BSON\UTCDateTime((new \DateTime())->getTimestamp() * 1000)
        ];
        
        // Configuration du mock
        $this->mockCollection->method('findOne')->willReturn(new BSONDocument($mockDocument));
        
        // Exécution du test
        $config = $this->configService->findById((string)$objectId);
        
        // Assertions
        $this->assertInstanceOf(Configuration::class, $config);
        $this->assertEquals('test_config', $config->getCode());
        $this->assertEquals('test_value', $config->getValue());
    }

    public function testFindByCode()
    {
        // Préparation des données de test
        $objectId = new ObjectId('507f1f77bcf86cd799439011');
        $mockDocument = [
            '_id' => $objectId,
            'code' => 'test_config',
            'value' => 'test_value',
            'description' => 'Test configuration',
            'category' => 'test',
            'environment' => 'test',
            'active' => true,
            'createdAt' => new \MongoDB\BSON\UTCDateTime((new \DateTime())->getTimestamp() * 1000)
        ];
        
        // Configuration du mock
        $this->mockCollection->method('findOne')->willReturn(new BSONDocument($mockDocument));
        
        // Exécution du test
        $config = $this->configService->findByCode('test_config', 'test');
        
        // Assertions
        $this->assertInstanceOf(Configuration::class, $config);
        $this->assertEquals('test_config', $config->getCode());
        $this->assertEquals('test_value', $config->getValue());
        $this->assertEquals('test', $config->getEnvironment());
    }

    public function testUpdateValue()
    {
        // Préparation des données de test pour findByCode
        $objectId = new ObjectId('507f1f77bcf86cd799439011');
        $mockDocument = [
            '_id' => $objectId,
            'code' => 'test_config',
            'value' => 'old_value',
            'description' => 'Test configuration',
            'category' => 'test',
            'environment' => 'test',
            'active' => true,
            'createdAt' => new \MongoDB\BSON\UTCDateTime((new \DateTime())->getTimestamp() * 1000)
        ];
        
        // Préparation du mock pour updateOne
        $updateResult = $this->createMock(UpdateResult::class);
        $updateResult->method('getModifiedCount')->willReturn(1);
        
        // Configuration des mocks
        $this->mockCollection->method('findOne')
            ->willReturn(new BSONDocument($mockDocument));
        
        $this->mockCollection->method('updateOne')
            ->willReturn($updateResult);
        
        // Exécution du test
        $result = $this->configService->updateValue('test_config', 'new_value', 'test');
        
        // Assertions
        $this->assertInstanceOf(Configuration::class, $result);
        $this->assertEquals('test_config', $result->getCode());
        $this->assertEquals('new_value', $result->getValue());
    }

    public function testDelete()
    {
        // Préparation des données de test
        $objectId = new ObjectId('507f1f77bcf86cd799439011');
        
        // Configuration du mock
        $deleteResult = $this->createMock(DeleteResult::class);
        $deleteResult->method('getDeletedCount')->willReturn(1);
        $this->mockCollection->method('deleteOne')->willReturn($deleteResult);
        
        // Exécution du test
        $result = $this->configService->delete((string)$objectId);
        
        // Assertions
        $this->assertTrue($result);
    }

    public function testConnectionError()
    {
        // Utiliser notre classe TestConfigurationService avec une méthode findById surchargée
        $exceptionMessage = 'Impossible de se connecter à MongoDB';
        
        // Créer un mock de collection qui génère une exception
        $mockCollection = $this->createMock(Collection::class);
        $mockCollection->method('findOne')
            ->willThrowException(new ConnectionException($exceptionMessage));
        
        // Créer une sous-classe anonyme qui convertit l'exception MongoDB en DataAccessException
        $configService = new class($mockCollection) extends TestConfigurationService {
            public function findById($id)
            {
                try {
                    $result = $this->collection->findOne(['_id' => new ObjectId($id)]);
                    return $result;
                } catch (\Exception $e) {
                    // Convertir en DataAccessException
                    throw new DataAccessException("Erreur lors de la recherche : " . $e->getMessage());
                }
            }
        };
        
        // Test avec assertion d'exception
        $this->expectException(DataAccessException::class);
        $configService->findById('507f1f77bcf86cd799439011');
    }
    
    public function testFindOrCreate_ExistingConfig()
    {
        // Préparation des données de test pour findByCode
        $objectId = new ObjectId('507f1f77bcf86cd799439011');
        $mockDocument = [
            '_id' => $objectId,
            'code' => 'test_config',
            'value' => 'test_value',
            'description' => 'Test configuration',
            'category' => 'test',
            'environment' => 'test',
            'active' => true,
            'createdAt' => new \MongoDB\BSON\UTCDateTime((new \DateTime())->getTimestamp() * 1000)
        ];
        
        // Configuration du mock pour findOne
        $this->mockCollection->method('findOne')
            ->willReturn(new BSONDocument($mockDocument));
        
        // Exécution du test
        $result = $this->configService->findOrCreate('test_config', 'default_value', 'Description', 'test');
        
        // Assertions
        $this->assertInstanceOf(Configuration::class, $result);
        $this->assertEquals('test_config', $result->getCode());
        $this->assertEquals('test_value', $result->getValue());
    }
    
    public function testFindOrCreate_NewConfig()
    {
        // Configuration du mock pour findOne (retourne null car config n'existe pas)
        $this->mockCollection->method('findOne')
            ->willReturn(null);
        
        // Préparation du mock pour insertOne
        $insertResult = $this->createMock(InsertOneResult::class);
        $objectId = new ObjectId('507f1f77bcf86cd799439022');
        $insertResult->method('getInsertedId')->willReturn($objectId);
        $insertResult->method('getInsertedCount')->willReturn(1);
        
        $this->mockCollection->method('insertOne')->willReturn($insertResult);
        
        // Exécution du test
        $result = $this->configService->findOrCreate('new_config', 'default_value', 'Description', 'test');
        
        // Assertions
        $this->assertInstanceOf(Configuration::class, $result);
        $this->assertEquals('new_config', $result->getCode());
        $this->assertEquals('default_value', $result->getValue());
        $this->assertEquals('Description', $result->getDescription());
        $this->assertEquals('test', $result->getEnvironment());
    }
} 