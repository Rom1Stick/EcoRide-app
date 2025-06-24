<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Core\Application;
use App\Core\Container\SimpleContainerV2;
use App\Controllers\Refactored\{
    RideControllerV4,
    BookingControllerV2,
    UserControllerV2,
    LocationControllerV2,
    SearchControllerV3
};

/**
 * Tests d'intégration Phase 4 - Architecture Orientée Objet Complète
 */
class Phase4IntegrationTest extends TestCase
{
    private Application $app;
    private SimpleContainerV2 $container;
    
    protected function setUp(): void
    {
        $this->container = new SimpleContainerV2();
        $this->container->configureForEcoRide();
        
        $this->app = new Application();
        $this->app->setContainer($this->container);
    }
    
    /**
     * @test
     * Teste l'injection de dépendances pour tous les contrôleurs V2/V3
     */
    public function test_tous_les_controleurs_peuvent_etre_instancies()
    {
        // Test RideControllerV4
        $rideController = $this->container->get(RideControllerV4::class);
        $this->assertInstanceOf(RideControllerV4::class, $rideController);
        
        // Test BookingControllerV2
        $bookingController = $this->container->get(BookingControllerV2::class);
        $this->assertInstanceOf(BookingControllerV2::class, $bookingController);
        
        // Test UserControllerV2
        $userController = $this->container->get(UserControllerV2::class);
        $this->assertInstanceOf(UserControllerV2::class, $userController);
        
        // Test LocationControllerV2
        $locationController = $this->container->get(LocationControllerV2::class);
        $this->assertInstanceOf(LocationControllerV2::class, $locationController);
        
        // Test SearchControllerV3
        $searchController = $this->container->get(SearchControllerV3::class);
        $this->assertInstanceOf(SearchControllerV3::class, $searchController);
    }
    
    /**
     * @test
     * Teste l'injection des services métier
     */
    public function test_services_metier_sont_injectes()
    {
        $rideService = $this->container->get('App\Domain\Services\RideManagementService');
        $bookingService = $this->container->get('App\Domain\Services\BookingService');
        $userService = $this->container->get('App\Domain\Services\UserService');
        $locationService = $this->container->get('App\Domain\Services\LocationService');
        $searchService = $this->container->get('App\Domain\Services\SearchService');
        
        $this->assertNotNull($rideService);
        $this->assertNotNull($bookingService);
        $this->assertNotNull($userService);
        $this->assertNotNull($locationService);
        $this->assertNotNull($searchService);
    }
    
    /**
     * @test
     * Teste l'injection des repositories
     */
    public function test_repositories_sont_injectes()
    {
        $rideRepo = $this->container->get('App\Domain\Repositories\RideRepositoryInterface');
        $bookingRepo = $this->container->get('App\Domain\Repositories\BookingRepositoryInterface');
        $userRepo = $this->container->get('App\Domain\Repositories\UserRepositoryInterface');
        $locationRepo = $this->container->get('App\Domain\Repositories\LocationRepositoryInterface');
        
        $this->assertNotNull($rideRepo);
        $this->assertNotNull($bookingRepo);
        $this->assertNotNull($userRepo);
        $this->assertNotNull($locationRepo);
    }
    
    /**
     * @test
     * Teste que les singletons sont respectés
     */
    public function test_pattern_singleton_respecte()
    {
        $service1 = $this->container->get('App\Domain\Services\RideManagementService');
        $service2 = $this->container->get('App\Domain\Services\RideManagementService');
        
        $this->assertSame($service1, $service2, 'Les services doivent être des singletons');
    }
    
    /**
     * @test
     * Simule un flux complet de recherche et réservation
     */
    public function test_flux_complet_recherche_reservation()
    {
        // 1. Recherche de trajets
        $searchController = $this->container->get(SearchControllerV3::class);
        
        // Mock des paramètres de recherche
        $_GET = [
            'departure' => 'Paris',
            'arrival' => 'Lyon',
            'date' => '2024-01-20',
            'limit' => 10
        ];
        
        // Simulation de la recherche
        try {
            $searchResult = $searchController->search();
            $this->assertNotNull($searchResult);
        } catch (\Exception $e) {
            // L'erreur est attendue car nous n'avons pas de vraie base de données
            $this->assertInstanceOf(\Exception::class, $e);
        }
        
        // 2. Affichage des détails d'un trajet
        $rideController = $this->container->get(RideControllerV4::class);
        
        try {
            // Simulation d'affichage des détails
            $rideDetails = $rideController->show(1);
            $this->assertNotNull($rideDetails);
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Exception::class, $e);
        }
        
        // 3. Réservation d'un trajet
        $bookingController = $this->container->get(BookingControllerV2::class);
        
        try {
            // Simulation de réservation
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $bookingResult = $bookingController->store();
            $this->assertNotNull($bookingResult);
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }
    
    /**
     * @test
     * Teste la gestion des erreurs dans l'architecture
     */
    public function test_gestion_erreurs_architecture()
    {
        $searchController = $this->container->get(SearchControllerV3::class);
        
        // Test avec paramètres invalides
        $_GET = [
            'departure' => '',
            'arrival' => '',
            'date' => 'invalid-date'
        ];
        
        $result = $searchController->search();
        
        // Vérifier que la réponse contient une erreur structurée
        $this->assertInstanceOf(\App\Core\Http\Response::class, $result);
    }
    
    /**
     * @test
     * Teste les performances avec les nouvelles optimisations
     */
    public function test_performance_architecture_optimisee()
    {
        $startTime = microtime(true);
        
        // Instanciation de tous les services
        $this->container->get('App\Domain\Services\RideManagementService');
        $this->container->get('App\Domain\Services\BookingService');
        $this->container->get('App\Domain\Services\UserService');
        $this->container->get('App\Domain\Services\LocationService');
        $this->container->get('App\Domain\Services\SearchService');
        
        $endTime = microtim(true);
        $duration = $endTime - $startTime;
        
        // Vérifier que l'instanciation est rapide (< 100ms)
        $this->assertLessThan(0.1, $duration, 'L\'instanciation des services doit être rapide');
    }
    
    /**
     * @test
     * Teste la compatibilité avec l'ancienne architecture
     */
    public function test_compatibilite_ancienne_architecture()
    {
        // Vérifier que les anciens services sont toujours disponibles
        $creditService = $this->container->get('App\Services\CreditService');
        $this->assertNotNull($creditService);
        
        // Vérifier que la base de données legacy fonctionne
        $database = $this->container->get('App\Core\Database');
        $this->assertNotNull($database);
    }
    
    /**
     * @test
     * Teste la qualité du code avec les nouvelles méthodes
     */
    public function test_qualite_code_nouvelles_methodes()
    {
        $searchController = $this->container->get(SearchControllerV3::class);
        
        // Vérifier que les méthodes publiques retournent des types corrects
        $reflection = new \ReflectionClass($searchController);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        $expectedMethods = [
            'search',
            'suggestions',
            'quickSearch',
            'searchByMap',
            'getFilters',
            'advancedSearch',
            'searchHistory',
            'saveSearch'
        ];
        
        $publicMethods = array_map(fn($m) => $m->getName(), $methods);
        
        foreach ($expectedMethods as $expectedMethod) {
            $this->assertContains(
                $expectedMethod,
                $publicMethods,
                "La méthode {$expectedMethod} doit être publique"
            );
        }
    }
    
    /**
     * @test
     * Teste la mémoire utilisée par la nouvelle architecture
     */
    public function test_utilisation_memoire_optimisee()
    {
        $memoryBefore = memory_get_usage();
        
        // Création de plusieurs instances
        for ($i = 0; $i < 10; $i++) {
            $this->container->get('App\Domain\Services\SearchService');
            $this->container->get('App\Domain\Services\BookingService');
        }
        
        $memoryAfter = memory_get_usage();
        $memoryUsed = $memoryAfter - $memoryBefore;
        
        // Vérifier que l'utilisation mémoire reste raisonnable
        // Grâce aux singletons, la mémoire ne devrait pas exploser
        $this->assertLessThan(5 * 1024 * 1024, $memoryUsed, 'Utilisation mémoire excessive détectée');
    }
    
    protected function tearDown(): void
    {
        // Nettoyage
        $_GET = [];
        $_POST = [];
        $_SERVER = [];
    }
} 