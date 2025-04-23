<?php

namespace Tests\Feature\DataAccess;

use App\Core\Database;
use App\DataAccess\NoSql\MongoConnection;
use App\DataAccess\NoSql\Model\Review;
use App\DataAccess\NoSql\Service\ReviewService;
use App\DataAccess\Sql\Entity\User;
use App\DataAccess\Sql\Entity\Vehicle;
use App\DataAccess\Sql\Repository\UserRepository;
use App\DataAccess\Sql\Repository\VehicleRepository;
use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\TestCase;

/**
 * Tests d'intégration pour la couche d'accès aux données
 * 
 * Note: Ces tests nécessitent des bases de données MySQL et MongoDB fonctionnelles.
 * Ils modifient le contenu des bases et doivent donc être exécutés dans un environnement
 * de test isolé.
 */
class IntegrationTest extends TestCase
{
    private $database;
    private $mongoConnection;
    private $userRepository;
    private $vehicleRepository;
    private $reviewService;
    
    /**
     * ID temporaires créés pendant les tests
     */
    private $createdUserId;
    private $createdVehicleId;
    private $createdReviewId;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Connexion aux bases de données
        $this->database = new Database();
        $this->mongoConnection = new MongoConnection();
        
        // Initialisation des repositories et services
        $this->userRepository = new UserRepository($this->database);
        $this->vehicleRepository = new VehicleRepository($this->database);
        $this->reviewService = new ReviewService($this->mongoConnection);
        
        // Nettoyer les données potentiellement laissées par des tests précédents
        $this->cleanupTestData();
    }
    
    protected function tearDown(): void
    {
        // Nettoyer les données créées pendant le test
        $this->cleanupTestData();
        
        // Fermer les connexions
        $this->database->closeConnections();
        
        parent::tearDown();
    }
    
    /**
     * Nettoie les données temporaires créées durant les tests
     */
    private function cleanupTestData(): void
    {
        if ($this->createdUserId) {
            try {
                $this->userRepository->delete($this->createdUserId);
                $this->createdUserId = null;
            } catch (\Exception $e) {
                // Ignorer les erreurs de nettoyage
            }
        }
        
        if ($this->createdVehicleId) {
            try {
                $this->vehicleRepository->delete($this->createdVehicleId);
                $this->createdVehicleId = null;
            } catch (\Exception $e) {
                // Ignorer les erreurs de nettoyage
            }
        }
        
        if ($this->createdReviewId) {
            try {
                $this->reviewService->delete($this->createdReviewId);
                $this->createdReviewId = null;
            } catch (\Exception $e) {
                // Ignorer les erreurs de nettoyage
            }
        }
    }
    
    /**
     * Test d'intégration MySQL : cycle complet CRUD sur User
     */
    public function testUserCrudCycle()
    {
        // 1. Création d'un utilisateur
        $user = new User();
        $user->setEmail('test.integration@example.com')
             ->setFirstName('Test')
             ->setLastName('Integration')
             ->setPassword(password_hash('password123', PASSWORD_DEFAULT))
             ->setPhone('0612345678')
             ->setRole('ROLE_USER');
        
        $this->createdUserId = $this->userRepository->create($user);
        $this->assertIsInt($this->createdUserId);
        $this->assertGreaterThan(0, $this->createdUserId);
        
        // 2. Lecture de l'utilisateur
        $fetchedUser = $this->userRepository->findById($this->createdUserId);
        $this->assertInstanceOf(User::class, $fetchedUser);
        $this->assertEquals('test.integration@example.com', $fetchedUser->getEmail());
        $this->assertEquals('Test', $fetchedUser->getFirstName());
        
        // 3. Mise à jour de l'utilisateur
        $fetchedUser->setFirstName('Updated');
        $updateResult = $this->userRepository->update($fetchedUser);
        $this->assertTrue($updateResult);
        
        // Vérification de la mise à jour
        $updatedUser = $this->userRepository->findById($this->createdUserId);
        $this->assertEquals('Updated', $updatedUser->getFirstName());
        
        // 4. Suppression de l'utilisateur
        $deleteResult = $this->userRepository->delete($this->createdUserId);
        $this->assertTrue($deleteResult);
        
        // Vérification de la suppression
        $deletedUser = $this->userRepository->findById($this->createdUserId);
        $this->assertNull($deletedUser);
        
        // Réinitialiser l'ID pour éviter un double nettoyage
        $this->createdUserId = null;
    }
    
    /**
     * Test d'intégration MySQL : cycle complet CRUD sur Vehicle
     */
    public function testVehicleCrudCycle()
    {
        // Créer d'abord un utilisateur pour le véhicule
        $user = new User();
        $user->setEmail('vehicle.owner@example.com')
             ->setFirstName('Vehicle')
             ->setLastName('Owner')
             ->setPassword(password_hash('password123', PASSWORD_DEFAULT))
             ->setPhone('0687654321')
             ->setRole('ROLE_USER');
        
        $this->createdUserId = $this->userRepository->create($user);
        
        // 1. Création d'un véhicule
        $vehicle = new Vehicle();
        $vehicle->setUserId($this->createdUserId)
                ->setBrand('Tesla')
                ->setModel('Model 3')
                ->setYear(2023)
                ->setLicensePlate('ECO-123-RD')
                ->setColor('Blue')
                ->setEnergyType('electric')
                ->setSeats(5)
                ->setComfortLevel('premium')
                ->setEcoScore(95);
        
        $this->createdVehicleId = $this->vehicleRepository->create($vehicle);
        $this->assertIsInt($this->createdVehicleId);
        $this->assertGreaterThan(0, $this->createdVehicleId);
        
        // 2. Lecture du véhicule
        $fetchedVehicle = $this->vehicleRepository->findById($this->createdVehicleId);
        $this->assertInstanceOf(Vehicle::class, $fetchedVehicle);
        $this->assertEquals('Tesla', $fetchedVehicle->getBrand());
        $this->assertEquals('Model 3', $fetchedVehicle->getModel());
        $this->assertEquals('electric', $fetchedVehicle->getEnergyType());
        
        // 3. Mise à jour du véhicule
        $fetchedVehicle->setColor('Green');
        $updateResult = $this->vehicleRepository->update($fetchedVehicle);
        $this->assertTrue($updateResult);
        
        // Vérification de la mise à jour
        $updatedVehicle = $this->vehicleRepository->findById($this->createdVehicleId);
        $this->assertEquals('Green', $updatedVehicle->getColor());
        
        // 4. Recherche par utilisateur
        $userVehicles = $this->vehicleRepository->findByUserId($this->createdUserId);
        $this->assertIsArray($userVehicles);
        $this->assertCount(1, $userVehicles);
        $this->assertEquals($this->createdVehicleId, $userVehicles[0]->getId());
        
        // 5. Suppression du véhicule
        $deleteResult = $this->vehicleRepository->delete($this->createdVehicleId);
        $this->assertTrue($deleteResult);
        
        // Vérification de la suppression
        $deletedVehicle = $this->vehicleRepository->findById($this->createdVehicleId);
        $this->assertNull($deletedVehicle);
        
        // Réinitialiser l'ID pour éviter un double nettoyage
        $this->createdVehicleId = null;
    }
    
    /**
     * Test d'intégration MongoDB : cycle complet CRUD sur Review
     */
    public function testReviewCrudCycle()
    {
        // 1. Création d'un avis
        $review = new Review();
        $review->setTripId(999) // ID fictif pour le test
               ->setUserId(888) // ID fictif pour le test
               ->setDriverId(777) // ID fictif pour le test
               ->setRating(4.5)
               ->setComment('Excellent trajet, chauffeur très sympathique.')
               ->setCreatedAt(new \DateTime());
        
        $reviewObjectId = $this->reviewService->insert($review->toArray());
        $this->assertInstanceOf(ObjectId::class, $reviewObjectId);
        $this->createdReviewId = (string)$reviewObjectId;
        
        // 2. Lecture de l'avis
        $fetchedReview = $this->reviewService->findById($this->createdReviewId);
        $this->assertInstanceOf(Review::class, $fetchedReview);
        $this->assertEquals(4.5, $fetchedReview->getRating());
        $this->assertEquals('Excellent trajet, chauffeur très sympathique.', $fetchedReview->getComment());
        
        // 3. Mise à jour de l'avis
        $updateData = [
            'rating' => 5.0,
            'comment' => 'Mise à jour : trajet parfait !'
        ];
        $updateResult = $this->reviewService->update($this->createdReviewId, $updateData);
        $this->assertTrue($updateResult);
        
        // Vérification de la mise à jour
        $updatedReview = $this->reviewService->findById($this->createdReviewId);
        $this->assertEquals(5.0, $updatedReview->getRating());
        $this->assertEquals('Mise à jour : trajet parfait !', $updatedReview->getComment());
        
        // 4. Recherche par ID de conducteur
        $driverReviews = $this->reviewService->findByDriverId(777);
        $this->assertIsArray($driverReviews);
        $this->assertNotEmpty($driverReviews);
        $this->assertEquals($this->createdReviewId, (string)$driverReviews[0]->getId());
        
        // 5. Suppression de l'avis
        $deleteResult = $this->reviewService->delete($this->createdReviewId);
        $this->assertTrue($deleteResult);
        
        // Vérification de la suppression
        $deletedReview = $this->reviewService->findById($this->createdReviewId);
        $this->assertNull($deletedReview);
        
        // Réinitialiser l'ID pour éviter un double nettoyage
        $this->createdReviewId = null;
    }
    
    /**
     * Test de transaction MySQL
     */
    public function testMySqlTransaction()
    {
        // Obtenir la connexion PDO
        $pdo = $this->database->getMysqlConnection();
        
        try {
            // Démarrer une transaction
            $pdo->beginTransaction();
            
            // Créer un utilisateur
            $user = new User();
            $user->setEmail('transaction.test@example.com')
                 ->setFirstName('Transaction')
                 ->setLastName('Test')
                 ->setPassword(password_hash('password123', PASSWORD_DEFAULT))
                 ->setPhone('0601020304')
                 ->setRole('ROLE_USER');
            
            $userId = $this->userRepository->create($user);
            
            // Créer un véhicule lié à cet utilisateur
            $vehicle = new Vehicle();
            $vehicle->setUserId($userId)
                    ->setBrand('Peugeot')
                    ->setModel('e-208')
                    ->setYear(2022)
                    ->setLicensePlate('ECO-456-TX')
                    ->setColor('Red')
                    ->setEnergyType('electric')
                    ->setSeats(5)
                    ->setComfortLevel('standard')
                    ->setEcoScore(88);
            
            $vehicleId = $this->vehicleRepository->create($vehicle);
            
            // Valider la transaction
            $pdo->commit();
            
            // Mémoriser les IDs pour le nettoyage
            $this->createdUserId = $userId;
            $this->createdVehicleId = $vehicleId;
            
            // Vérifier que les deux entités ont été créées
            $this->assertNotNull($this->userRepository->findById($userId));
            $this->assertNotNull($this->vehicleRepository->findById($vehicleId));
            
        } catch (\Exception $e) {
            // En cas d'erreur, annuler la transaction
            $pdo->rollBack();
            $this->fail('La transaction a échoué : ' . $e->getMessage());
        }
    }
} 