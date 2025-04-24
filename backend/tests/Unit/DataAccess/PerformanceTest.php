<?php

namespace Tests\Unit\DataAccess;

use App\Core\Database;
use App\DataAccess\NoSql\MongoConnection;
use App\DataAccess\NoSql\Service\ReviewService;
use App\DataAccess\Sql\Repository\UserRepository;
use App\DataAccess\Sql\Repository\VehicleRepository;
use PHPUnit\Framework\TestCase;

/**
 * Tests de performance pour la couche d'accès aux données
 * 
 * Ces tests mesurent le temps d'exécution des requêtes clés pour s'assurer
 * qu'elles respectent les objectifs de performance. Les seuils sont ajustables
 * en fonction des besoins spécifiques du projet.
 */
class PerformanceTest extends TestCase
{
    private $database;
    private $mongoConnection;
    private $userRepository;
    private $vehicleRepository;
    private $reviewService;
    
    // Seuils de performance en millisecondes
    private const THRESHOLD_FIND_BY_ID = 50;
    private const THRESHOLD_COMPLEX_QUERY = 200;
    private const THRESHOLD_COUNT = 100;
    
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
    }
    
    protected function tearDown(): void
    {
        // Fermer les connexions
        $this->database->closeConnections();
        
        parent::tearDown();
    }
    
    /**
     * Mesure le temps d'exécution d'une fonction
     * 
     * @param callable $callback La fonction à mesurer
     * @return float Temps d'exécution en millisecondes
     */
    private function measureExecutionTime(callable $callback): float
    {
        $startTime = microtime(true);
        $callback();
        $endTime = microtime(true);
        
        return ($endTime - $startTime) * 1000; // Conversion en millisecondes
    }
    
    /**
     * Test de performance pour la recherche par ID (MySQL)
     */
    public function testUserFindByIdPerformance()
    {
        // ID d'un utilisateur existant dans la base de données de test
        $existingUserId = 1; // À ajuster selon votre base de test
        
        $executionTime = $this->measureExecutionTime(function() use ($existingUserId) {
            $this->userRepository->findById($existingUserId);
        });
        
        $this->assertLessThan(
            self::THRESHOLD_FIND_BY_ID,
            $executionTime,
            "La recherche d'un utilisateur par ID prend trop de temps ($executionTime ms)"
        );
        
        echo "Performance findById (User): $executionTime ms\n";
    }
    
    /**
     * Test de performance pour une requête complexe (MySQL)
     */
    public function testVehicleComplexQueryPerformance()
    {
        // Requête complexe : trouver les véhicules électriques avec pagination
        $executionTime = $this->measureExecutionTime(function() {
            $this->vehicleRepository->findByEnergyType('electric', 1, 10);
        });
        
        $this->assertLessThan(
            self::THRESHOLD_COMPLEX_QUERY,
            $executionTime,
            "La recherche complexe de véhicules prend trop de temps ($executionTime ms)"
        );
        
        echo "Performance requête complexe (Vehicle): $executionTime ms\n";
    }
    
    /**
     * Test de performance pour le comptage (MySQL)
     */
    public function testVehicleCountPerformance()
    {
        $executionTime = $this->measureExecutionTime(function() {
            $this->vehicleRepository->countByEnergyType('electric');
        });
        
        $this->assertLessThan(
            self::THRESHOLD_COUNT,
            $executionTime,
            "Le comptage des véhicules prend trop de temps ($executionTime ms)"
        );
        
        echo "Performance count (Vehicle): $executionTime ms\n";
    }
    
    /**
     * Test de performance pour la recherche par ID (MongoDB)
     */
    public function testReviewFindByIdPerformance()
    {
        // ID d'un avis existant dans MongoDB (à ajuster)
        $existingReviewId = '507f1f77bcf86cd799439011'; // Remplacer par un ID valide
        
        $executionTime = $this->measureExecutionTime(function() use ($existingReviewId) {
            $this->reviewService->findById($existingReviewId);
        });
        
        $this->assertLessThan(
            self::THRESHOLD_FIND_BY_ID,
            $executionTime,
            "La recherche d'un avis par ID prend trop de temps ($executionTime ms)"
        );
        
        echo "Performance findById (Review): $executionTime ms\n";
    }
    
    /**
     * Test de performance pour une requête complexe (MongoDB)
     */
    public function testReviewComplexQueryPerformance()
    {
        // Requête complexe : trouver les avis par conducteur
        $executionTime = $this->measureExecutionTime(function() {
            $this->reviewService->findByDriverId(777); // ID de conducteur pour le test
        });
        
        $this->assertLessThan(
            self::THRESHOLD_COMPLEX_QUERY,
            $executionTime,
            "La recherche complexe d'avis prend trop de temps ($executionTime ms)"
        );
        
        echo "Performance requête complexe (Review): $executionTime ms\n";
    }
    
    /**
     * Test de performance pour l'agrégation (MongoDB)
     */
    public function testReviewAggregationPerformance()
    {
        // Agrégation : calculer la moyenne des notes par conducteur
        $executionTime = $this->measureExecutionTime(function() {
            $this->reviewService->getAverageRatingByDriver(777); // ID de conducteur pour le test
        });
        
        $this->assertLessThan(
            self::THRESHOLD_COMPLEX_QUERY,
            $executionTime,
            "L'agrégation des avis prend trop de temps ($executionTime ms)"
        );
        
        echo "Performance agrégation (Review): $executionTime ms\n";
    }
    
    /**
     * Test de performance pour les opérations d'insertion (MySQL)
     * 
     * Note: Ce test est commenté car il modifie la base de données.
     * Décommenter pour exécuter dans un environnement de test isolé.
     */
    /*
    public function testInsertPerformance()
    {
        $pdo = $this->database->getMysqlConnection();
        $pdo->beginTransaction();
        
        try {
            // Créer un nouvel utilisateur pour le test
            $user = new \App\DataAccess\Sql\Entity\User();
            $user->setEmail('perf.test.' . uniqid() . '@example.com')
                 ->setFirstName('Perf')
                 ->setLastName('Test')
                 ->setPassword(password_hash('password123', PASSWORD_DEFAULT))
                 ->setPhone('0699887766')
                 ->setRole('ROLE_USER');
            
            $executionTime = $this->measureExecutionTime(function() use ($user) {
                $this->userRepository->create($user);
            });
            
            $this->assertLessThan(
                self::THRESHOLD_COMPLEX_QUERY,
                $executionTime,
                "L'insertion d'un utilisateur prend trop de temps ($executionTime ms)"
            );
            
            echo "Performance insertion (User): $executionTime ms\n";
            
            // Rollback pour ne pas affecter la base de données
            $pdo->rollBack();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    */
    
    /**
     * Test de charge: multiples requêtes simultanées
     */
    public function testLoadPerformance()
    {
        $iterations = 10; // Nombre de requêtes à effectuer
        $totalTime = 0;
        
        for ($i = 0; $i < $iterations; $i++) {
            $executionTime = $this->measureExecutionTime(function() {
                $this->userRepository->findAll(1, 20);
            });
            $totalTime += $executionTime;
        }
        
        $averageTime = $totalTime / $iterations;
        
        $this->assertLessThan(
            self::THRESHOLD_COMPLEX_QUERY,
            $averageTime,
            "Le temps moyen pour les requêtes multiples est trop élevé ($averageTime ms)"
        );
        
        echo "Performance moyenne pour $iterations requêtes: $averageTime ms\n";
    }
} 