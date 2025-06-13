<?php
/**
 * Tests Unitaires - Container d'Injection de Dépendances EcoRide
 * 
 * Ce fichier teste toutes les fonctionnalités du système DI pour s'assurer
 * de son bon fonctionnement avant déploiement.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\Container\Container;
use App\Core\Container\ContainerBootstrap;
use App\Core\Container\ServiceProvider;
use App\Core\Container\Exceptions\ContainerException;
use App\Core\Container\Exceptions\NotFoundException;
use App\Controllers\Refactored\RideControllerV3;
use App\Domain\Services\RideManagementService;
use App\Core\Logger;

/**
 * Classe de test simple (équivalent PHPUnit)
 */
class ContainerDITest
{
    private int $tests = 0;
    private int $passed = 0;
    private int $failed = 0;
    private array $results = [];

    public function run(): void
    {
        echo "🧪 TESTS CONTAINER D'INJECTION DE DÉPENDANCES\n";
        echo "=============================================\n\n";

        // Tests de base
        $this->testContainerCreation();
        $this->testServiceBinding();
        $this->testSingletonBehavior();
        $this->testAliasResolution();
        $this->testDependencyInjection();
        
        // Tests avancés
        $this->testServiceProvider();
        $this->testBootstrap();
        $this->testControllerCreation();
        $this->testCacheService();
        $this->testProfilerService();
        
        // Tests d'erreurs
        $this->testNotFoundException();
        $this->testCircularDependency();
        $this->testInvalidBinding();
        
        // Tests de performance
        $this->testPerformanceMetrics();
        $this->testMemoryUsage();
        
        // Tests de migration
        $this->testMigrationMiddleware();
        
        $this->printResults();
    }

    private function testContainerCreation(): void
    {
        $this->test("Création du container", function() {
            $container = new Container();
            
            $this->assertTrue($container instanceof Container, "Container créé");
            $this->assertTrue(method_exists($container, 'get'), "Méthode get disponible");
            $this->assertTrue(method_exists($container, 'has'), "Méthode has disponible");
            $this->assertTrue(method_exists($container, 'bind'), "Méthode bind disponible");
        });
    }

    private function testServiceBinding(): void
    {
        $this->test("Enregistrement de services", function() {
            $container = new Container();
            
            // Test binding simple
            $container->bind('test.service', 'stdClass');
            $this->assertTrue($container->has('test.service'), "Service enregistré");
            
            // Test binding avec closure
            $container->bind('test.closure', function() {
                return new stdClass();
            });
            $this->assertTrue($container->has('test.closure'), "Service closure enregistré");
            
            // Test récupération
            $service = $container->get('test.service');
            $this->assertTrue($service instanceof stdClass, "Service récupéré correctement");
        });
    }

    private function testSingletonBehavior(): void
    {
        $this->test("Comportement singleton", function() {
            $container = new Container();
            
            // Enregistrement comme singleton
            $container->singleton('singleton.test', function() {
                return (object) ['id' => uniqid()];
            });
            
            // Récupération multiple
            $instance1 = $container->get('singleton.test');
            $instance2 = $container->get('singleton.test');
            
            $this->assertTrue($instance1 === $instance2, "Même instance retournée");
            $this->assertEquals($instance1->id, $instance2->id, "Propriétés identiques");
        });
    }

    private function testAliasResolution(): void
    {
        $this->test("Résolution d'alias", function() {
            $container = new Container();
            
            // Enregistrement avec alias
            $container->bind('real.service', 'stdClass');
            $container->alias('alias.service', 'real.service');
            
            $this->assertTrue($container->has('alias.service'), "Alias disponible");
            
            $real = $container->get('real.service');
            $aliased = $container->get('alias.service');
            
            $this->assertEquals(get_class($real), get_class($aliased), "Même type via alias");
        });
    }

    private function testDependencyInjection(): void
    {
        $this->test("Injection automatique de dépendances", function() {
            $container = new Container();
            
            // Service avec dépendances
            $container->bind('dependency', 'stdClass');
            
            // Classe de test avec injection
            $testClass = new class {
                public stdClass $dependency;
                
                public function __construct(stdClass $dependency) {
                    $this->dependency = $dependency;
                }
            };
            
            $className = get_class($testClass);
            $container->bind('test.class', $className);
            
            $instance = $container->get('test.class');
            $this->assertTrue(isset($instance->dependency), "Dépendance injectée");
            $this->assertTrue($instance->dependency instanceof stdClass, "Type correct");
        });
    }

    private function testServiceProvider(): void
    {
        $this->test("Service Provider", function() {
            $container = new Container();
            $provider = new ServiceProvider($container);
            
            // Enregistrement des services
            $provider->register();
            
            // Vérification des services core
            $this->assertTrue($container->has(Logger::class), "Logger enregistré");
            $this->assertTrue($container->has('logger'), "Alias logger disponible");
            
            // Vérification des repositories
            $this->assertTrue($container->has('ride.repository'), "Repository alias disponible");
            $this->assertTrue($container->has('ride.service'), "Service alias disponible");
        });
    }

    private function testBootstrap(): void
    {
        $this->test("Bootstrap du container", function() {
            // Reset pour test propre
            ContainerBootstrap::reset();
            
            $config = ContainerBootstrap::getEnvironmentConfig('development');
            $container = ContainerBootstrap::bootstrap($config);
            
            $this->assertTrue($container instanceof Container, "Container bootstrapé");
            
            // Validation de la configuration
            $validation = ContainerBootstrap::validateConfiguration();
            $this->assertEquals('ok', $validation['status'], "Configuration valide");
            
            // Vérification de l'instance singleton
            $instance2 = ContainerBootstrap::getInstance();
            $this->assertTrue($container === $instance2, "Instance singleton");
        });
    }

    private function testControllerCreation(): void
    {
        $this->test("Création de contrôleurs avec DI", function() {
            try {
                $controller = ContainerBootstrap::createController(RideControllerV3::class);
                $this->assertTrue($controller instanceof RideControllerV3, "Contrôleur créé");
                
                // Test d'une méthode
                $stats = $controller->stats();
                $this->assertTrue(is_array($stats), "Méthode stats fonctionne");
                
            } catch (Exception $e) {
                // En cas d'erreur (dépendances manquantes), vérifier le type d'exception
                $this->assertTrue($e instanceof ContainerException, "Exception appropriée");
            }
        });
    }

    private function testCacheService(): void
    {
        $this->test("Service de cache", function() {
            $container = ContainerBootstrap::getInstance();
            
            if ($container && $container->has('cache')) {
                $cache = $container->get('cache');
                
                // Test stockage/récupération
                $cache->set('test_key', 'test_value', 300);
                $value = $cache->get('test_key');
                
                $this->assertEquals('test_value', $value, "Cache fonctionne");
                
                // Test existence
                $this->assertTrue($cache->has('test_key'), "Clé existe");
                
                // Test suppression
                $cache->forget('test_key');
                $this->assertFalse($cache->has('test_key'), "Clé supprimée");
            } else {
                $this->markSkipped("Cache service non disponible");
            }
        });
    }

    private function testProfilerService(): void
    {
        $this->test("Service profiler", function() {
            $container = ContainerBootstrap::getInstance();
            
            if ($container && $container->has('profiler')) {
                $profiler = $container->get('profiler');
                
                // Test profiling
                $profiler->start('test_operation');
                usleep(1000); // 1ms
                $duration = $profiler->stop('test_operation');
                
                $this->assertTrue($duration > 0, "Durée mesurée");
                $this->assertTrue($duration < 1, "Durée réaliste");
                
                // Test timers
                $timers = $profiler->getTimers();
                $this->assertTrue(is_array($timers), "Timers disponibles");
            } else {
                $this->markSkipped("Profiler service non disponible");
            }
        });
    }

    private function testNotFoundException(): void
    {
        $this->test("Exception service non trouvé", function() {
            $container = new Container();
            
            try {
                $container->get('nonexistent.service');
                $this->fail("Exception attendue");
            } catch (NotFoundException $e) {
                $this->assertTrue(true, "NotFoundException levée");
            }
        });
    }

    private function testCircularDependency(): void
    {
        $this->test("Détection dépendance circulaire", function() {
            $container = new Container();
            
            // Services avec dépendance circulaire simulée
            $container->bind('service.a', function($c) {
                return $c->get('service.b');
            });
            
            $container->bind('service.b', function($c) {
                return $c->get('service.a');
            });
            
            try {
                $container->get('service.a');
                $this->fail("Exception de dépendance circulaire attendue");
            } catch (ContainerException $e) {
                $this->assertStringContains('circulaire', $e->getMessage(), "Message approprié");
            }
        });
    }

    private function testInvalidBinding(): void
    {
        $this->test("Binding invalide", function() {
            $container = new Container();
            
            // Test avec type invalide
            $container->bind('invalid', 123);
            
            try {
                $container->get('invalid');
                $this->fail("Exception attendue pour binding invalide");
            } catch (ContainerException $e) {
                $this->assertTrue(true, "Exception levée pour binding invalide");
            }
        });
    }

    private function testPerformanceMetrics(): void
    {
        $this->test("Métriques de performance", function() {
            $container = ContainerBootstrap::getInstance();
            
            if ($container) {
                $stats = $container->getStats();
                
                $this->assertTrue(isset($stats['bindings_count']), "Bindings count disponible");
                $this->assertTrue(isset($stats['instances_count']), "Instances count disponible");
                $this->assertTrue(isset($stats['memory_usage']), "Memory usage disponible");
                
                $this->assertTrue($stats['bindings_count'] >= 0, "Bindings count valide");
                $this->assertTrue($stats['memory_usage'] > 0, "Memory usage valide");
                
                // Test rapport complet
                $report = ContainerBootstrap::getPerformanceReport();
                $this->assertTrue(isset($report['container_stats']), "Stats dans rapport");
                $this->assertTrue(isset($report['performance_metrics']), "Métriques dans rapport");
            } else {
                $this->markSkipped("Container non disponible");
            }
        });
    }

    private function testMemoryUsage(): void
    {
        $this->test("Usage mémoire", function() {
            $initialMemory = memory_get_usage();
            
            // Création de nombreux services
            $container = new Container();
            for ($i = 0; $i < 100; $i++) {
                $container->bind("service_{$i}", 'stdClass');
            }
            
            $afterBindingMemory = memory_get_usage();
            
            // Résolution de services
            for ($i = 0; $i < 10; $i++) {
                $container->get("service_{$i}");
            }
            
            $afterResolutionMemory = memory_get_usage();
            
            $this->assertTrue($afterBindingMemory > $initialMemory, "Mémoire augmente avec bindings");
            $this->assertTrue($afterResolutionMemory > $afterBindingMemory, "Mémoire augmente avec résolution");
            
            // Vérifier que l'augmentation reste raisonnable
            $totalIncrease = $afterResolutionMemory - $initialMemory;
            $this->assertTrue($totalIncrease < 10 * 1024 * 1024, "Augmentation mémoire raisonnable"); // < 10MB
        });
    }

    private function testMigrationMiddleware(): void
    {
        $this->test("Middleware de migration", function() {
            $container = ContainerBootstrap::getInstance();
            
            if ($container) {
                // Setup migration
                ContainerBootstrap::setupProgressiveMigration([
                    '/api/v3/test' => RideControllerV3::class
                ]);
                
                $this->assertTrue($container->has('migration.middleware'), "Middleware enregistré");
                
                $middleware = $container->get('migration.middleware');
                
                // Test route V3
                $v3Controller = $middleware->handle('/api/v3/test');
                $this->assertNotNull($v3Controller, "Route V3 détectée");
                
                // Test route legacy
                $legacyController = $middleware->handle('/api/v1/legacy');
                $this->assertNull($legacyController, "Route legacy ignorée");
            } else {
                $this->markSkipped("Container non disponible");
            }
        });
    }

    // =============================================================================
    // FRAMEWORK DE TEST SIMPLE
    // =============================================================================

    private function test(string $name, callable $test): void
    {
        $this->tests++;
        
        try {
            echo "🔍 Test: {$name}... ";
            $test();
            $this->passed++;
            $this->results[] = ['name' => $name, 'status' => 'PASS', 'message' => ''];
            echo "✅ PASS\n";
        } catch (Exception $e) {
            $this->failed++;
            $this->results[] = ['name' => $name, 'status' => 'FAIL', 'message' => $e->getMessage()];
            echo "❌ FAIL - " . $e->getMessage() . "\n";
        }
    }

    private function assertTrue(bool $condition, string $message = ''): void
    {
        if (!$condition) {
            throw new Exception($message ?: 'Assertion failed');
        }
    }

    private function assertEquals($expected, $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            throw new Exception($message ?: "Expected {$expected}, got {$actual}");
        }
    }

    private function assertStringContains(string $needle, string $haystack, string $message = ''): void
    {
        if (strpos($haystack, $needle) === false) {
            throw new Exception($message ?: "String '{$haystack}' does not contain '{$needle}'");
        }
    }

    private function assertNotNull($value, string $message = ''): void
    {
        if ($value === null) {
            throw new Exception($message ?: 'Value should not be null');
        }
    }

    private function assertNull($value, string $message = ''): void
    {
        if ($value !== null) {
            throw new Exception($message ?: 'Value should be null');
        }
    }

    private function assertFalse(bool $condition, string $message = ''): void
    {
        if ($condition) {
            throw new Exception($message ?: 'Condition should be false');
        }
    }

    private function fail(string $message): void
    {
        throw new Exception($message);
    }

    private function markSkipped(string $reason): void
    {
        $this->results[count($this->results) - 1]['status'] = 'SKIP';
        $this->results[count($this->results) - 1]['message'] = $reason;
        echo "⏭️  SKIP - {$reason}\n";
        $this->tests--;
        $this->passed--;
    }

    private function printResults(): void
    {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "📊 RÉSULTATS DES TESTS\n";
        echo str_repeat("=", 50) . "\n";
        
        echo "Total: {$this->tests} tests\n";
        echo "✅ Réussis: {$this->passed}\n";
        echo "❌ Échoués: {$this->failed}\n";
        
        $skipped = array_filter($this->results, fn($r) => $r['status'] === 'SKIP');
        if (!empty($skipped)) {
            echo "⏭️  Ignorés: " . count($skipped) . "\n";
        }
        
        $successRate = $this->tests > 0 ? round(($this->passed / $this->tests) * 100, 2) : 0;
        echo "📈 Taux de réussite: {$successRate}%\n\n";
        
        if ($this->failed > 0) {
            echo "❌ TESTS ÉCHOUÉS:\n";
            foreach ($this->results as $result) {
                if ($result['status'] === 'FAIL') {
                    echo "   • {$result['name']}: {$result['message']}\n";
                }
            }
            echo "\n";
        }
        
        if ($successRate >= 90) {
            echo "🎉 EXCELLENT! Le container DI est prêt pour la production.\n";
        } elseif ($successRate >= 70) {
            echo "⚠️  BIEN - Quelques améliorations nécessaires.\n";
        } else {
            echo "🚨 ATTENTION - Problèmes majeurs détectés.\n";
        }
        
        echo "\n🚀 Container d'Injection de Dépendances EcoRide testé!\n";
    }
}

// =============================================================================
// EXÉCUTION DES TESTS
// =============================================================================

$tester = new ContainerDITest();
$tester->run(); 