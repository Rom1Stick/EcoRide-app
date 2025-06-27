#!/usr/bin/env php
<?php

/**
 * Script de Test Sécurisé - Migration Architecture OO
 * 
 * Valide l'architecture orientée objet en mode LECTURE SEULE uniquement.
 * GARANTIE : Aucune donnée existante ne sera modifiée.
 * 
 * Usage: php scripts/safe_migration_test.php [--detailed] [--benchmark]
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/bootstrap.php';

use App\Core\Database;
use App\Core\Logger;
use App\Infrastructure\Factories\RepositoryFactory;
use App\Infrastructure\Repositories\MySQLRideRepository;
use App\Infrastructure\Repositories\MySQLLocationRepository;
use App\Domain\Services\RideManagementService;

class SafeMigrationTest
{
    private Database $db;
    private Logger $logger;
    private array $safetyConfig;
    private bool $detailedMode = false;
    private bool $benchmarkMode = false;
    private array $testResults = [];

    public function __construct()
    {
        $this->db = new Database();
        $this->logger = new Logger(__DIR__ . '/../logs/safe_migration_test.log');
        $this->safetyConfig = require __DIR__ . '/../config/safe_mode.php';
        
        echo "🛡️  MODE SÉCURISÉ ACTIVÉ - AUCUNE MODIFICATION DE DONNÉES\n";
        echo str_repeat("=", 60) . "\n";
        
        $this->logger->info('Test sécurisé de migration démarré');
    }

    public function run(array $args): void
    {
        $this->parseArguments($args);
        
        echo "🚀 DÉMARRAGE DES TESTS SÉCURISÉS\n\n";
        
        // Tests en lecture seule uniquement
        $this->testDatabaseConnection();
        $this->testRepositoryCreation();
        $this->testDataMapping();
        $this->testDomainServices();
        
        if ($this->benchmarkMode) {
            $this->runPerformanceBenchmarks();
        }
        
        $this->displayResults();
        $this->generateReport();
    }

    // ==========================================
    // TESTS DE CONNECTIVITÉ
    // ==========================================

    private function testDatabaseConnection(): void
    {
        echo "📊 Test 1: Connexion Base de Données\n";
        echo str_repeat("-", 40) . "\n";
        
        try {
            // Test de connexion simple
            $result = $this->db->query("SELECT COUNT(*) as total FROM covoiturage WHERE 1=1")->fetch();
            $totalRides = $result['total'];
            
            $this->addTestResult('database_connection', true, [
                'total_rides_in_db' => $totalRides,
                'message' => 'Connexion réussie'
            ]);
            
            echo "✅ Connexion DB: OK\n";
            echo "📈 Trajets en base: {$totalRides}\n";
            
            // Test des tables critiques
            $tables = ['utilisateur', 'covoiturage', 'lieu', 'voiture'];
            foreach ($tables as $table) {
                $count = $this->db->query("SELECT COUNT(*) as count FROM {$table}")->fetch()['count'];
                echo "   📋 Table {$table}: {$count} entrées\n";
            }
            
        } catch (Exception $e) {
            $this->addTestResult('database_connection', false, [
                'error' => $e->getMessage()
            ]);
            echo "❌ Erreur de connexion: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }

    // ==========================================
    // TESTS DES REPOSITORIES
    // ==========================================

    private function testRepositoryCreation(): void
    {
        echo "🏗️  Test 2: Création des Repositories\n";
        echo str_repeat("-", 40) . "\n";
        
        try {
            // Test de création via Factory
            $factory = RepositoryFactory::createFromLegacyDatabase($this->db, $this->logger);
            
            $rideRepo = $factory->createRideRepository();
            $locationRepo = $factory->createLocationRepository();
            
            $this->addTestResult('repository_creation', true, [
                'ride_repository' => get_class($rideRepo),
                'location_repository' => get_class($locationRepo),
                'message' => 'Repositories créés avec succès'
            ]);
            
            echo "✅ RideRepository: " . get_class($rideRepo) . "\n";
            echo "✅ LocationRepository: " . get_class($locationRepo) . "\n";
            
            // Test d'une requête simple (lecture seule)
            $availableRides = $rideRepo->findAvailableRides(5);
            echo "✅ Requête test: " . count($availableRides) . " trajets disponibles\n";
            
        } catch (Exception $e) {
            $this->addTestResult('repository_creation', false, [
                'error' => $e->getMessage()
            ]);
            echo "❌ Erreur création repository: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }

    // ==========================================
    // TESTS DE MAPPING DES DONNÉES
    // ==========================================

    private function testDataMapping(): void
    {
        echo "🔄 Test 3: Mapping des Données\n";
        echo str_repeat("-", 40) . "\n";
        
        try {
            $factory = RepositoryFactory::createFromLegacyDatabase($this->db, $this->logger);
            $rideRepo = $factory->createRideRepository();
            
            // Test avec un échantillon de trajets
            $rides = $rideRepo->findAvailableRides(10);
            $mappingResults = [];
            
            foreach ($rides as $ride) {
                $validation = $this->validateRideMapping($ride);
                $mappingResults[] = $validation;
                
                if ($this->detailedMode) {
                    echo "   🔍 Trajet {$ride->getId()}: Score {$validation['score']}%\n";
                }
            }
            
            $avgScore = array_sum(array_column($mappingResults, 'score')) / count($mappingResults);
            
            $this->addTestResult('data_mapping', $avgScore >= 90, [
                'sample_size' => count($mappingResults),
                'average_score' => round($avgScore, 1),
                'details' => $this->detailedMode ? $mappingResults : null
            ]);
            
            echo "✅ Échantillon testé: " . count($mappingResults) . " trajets\n";
            echo "📊 Score moyen de mapping: " . round($avgScore, 1) . "%\n";
            
            if ($avgScore >= 90) {
                echo "🎯 Mapping EXCELLENT (≥90%)\n";
            } elseif ($avgScore >= 75) {
                echo "⚠️  Mapping ACCEPTABLE (≥75%)\n";
            } else {
                echo "❌ Mapping INSUFFISANT (<75%)\n";
            }
            
        } catch (Exception $e) {
            $this->addTestResult('data_mapping', false, [
                'error' => $e->getMessage()
            ]);
            echo "❌ Erreur mapping: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }

    private function validateRideMapping($ride): array
    {
        $checks = [
            'has_id' => $ride->getId() > 0,
            'has_departure' => $ride->getDeparture() !== null,
            'has_arrival' => $ride->getArrival() !== null,
            'has_driver' => $ride->getDriver() !== null,
            'has_valid_price' => $ride->getPricePerPerson()->getAmount() > 0,
            'has_valid_datetime' => $ride->getDepartureDateTime() !== null,
            'has_seats' => $ride->getAvailableSeats() >= 0,
            'has_status' => $ride->getStatus() !== null
        ];
        
        $score = (array_sum(array_map(fn($v) => $v ? 1 : 0, $checks)) / count($checks)) * 100;
        
        return [
            'ride_id' => $ride->getId(),
            'checks' => $checks,
            'score' => round($score, 1)
        ];
    }

    // ==========================================
    // TESTS DES SERVICES MÉTIER
    // ==========================================

    private function testDomainServices(): void
    {
        echo "⚙️  Test 4: Services Métier\n";
        echo str_repeat("-", 40) . "\n";
        
        try {
            $factory = RepositoryFactory::createFromLegacyDatabase($this->db, $this->logger);
            $rideRepo = $factory->createRideRepository();
            
            // Test du service métier
            $rideService = new RideManagementService($rideRepo);
            
            // Test de recherche (lecture seule)
            $searchResults = $rideService->searchRides(null, null, null, 'departureTime', 1, 5);
            
            $this->addTestResult('domain_services', true, [
                'service_class' => get_class($rideService),
                'search_results_count' => count($searchResults),
                'message' => 'Service métier fonctionnel'
            ]);
            
            echo "✅ RideManagementService: " . get_class($rideService) . "\n";
            echo "✅ Recherche test: " . count($searchResults) . " résultats\n";
            echo "✅ Logique métier: Opérationnelle\n";
            
        } catch (Exception $e) {
            $this->addTestResult('domain_services', false, [
                'error' => $e->getMessage()
            ]);
            echo "❌ Erreur service métier: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }

    // ==========================================
    // BENCHMARKS DE PERFORMANCE
    // ==========================================

    private function runPerformanceBenchmarks(): void
    {
        echo "⚡ Test 5: Benchmarks de Performance\n";
        echo str_repeat("-", 40) . "\n";
        
        try {
            $factory = RepositoryFactory::createFromLegacyDatabase($this->db, $this->logger);
            $rideRepo = $factory->createRideRepository();
            
            $benchmarks = [];
            
            // Benchmark 1: findAvailableRides
            $start = microtime(true);
            $rideRepo->findAvailableRides(20);
            $benchmarks['find_available_rides'] = round((microtime(true) - $start) * 1000, 2);
            
            // Benchmark 2: searchRides
            $start = microtime(true);
            $rideRepo->searchRides(null, null, new DateTime(), 10);
            $benchmarks['search_rides'] = round((microtime(true) - $start) * 1000, 2);
            
            // Benchmark 3: countSearchResults
            $start = microtime(true);
            $rideRepo->countSearchResults(null, null, null);
            $benchmarks['count_results'] = round((microtime(true) - $start) * 1000, 2);
            
            $avgTime = array_sum($benchmarks) / count($benchmarks);
            
            $this->addTestResult('performance_benchmarks', true, [
                'benchmarks_ms' => $benchmarks,
                'average_time_ms' => round($avgTime, 2),
                'total_time_ms' => array_sum($benchmarks)
            ]);
            
            echo "⏱️  findAvailableRides: {$benchmarks['find_available_rides']}ms\n";
            echo "⏱️  searchRides: {$benchmarks['search_rides']}ms\n";
            echo "⏱️  countResults: {$benchmarks['count_results']}ms\n";
            echo "📊 Temps moyen: " . round($avgTime, 2) . "ms\n";
            
            if ($avgTime < 200) {
                echo "🚀 Performance EXCELLENTE (<200ms)\n";
            } elseif ($avgTime < 500) {
                echo "✅ Performance ACCEPTABLE (<500ms)\n";
            } else {
                echo "⚠️  Performance à OPTIMISER (>500ms)\n";
            }
            
        } catch (Exception $e) {
            $this->addTestResult('performance_benchmarks', false, [
                'error' => $e->getMessage()
            ]);
            echo "❌ Erreur benchmark: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }

    // ==========================================
    // RÉSULTATS ET RAPPORTS
    // ==========================================

    private function displayResults(): void
    {
        echo "📋 RÉSUMÉ DES TESTS SÉCURISÉS\n";
        echo str_repeat("=", 60) . "\n";
        
        $totalTests = count($this->testResults);
        $passedTests = count(array_filter($this->testResults, fn($r) => $r['success']));
        $successRate = round(($passedTests / $totalTests) * 100, 1);
        
        echo "Total des tests: {$totalTests}\n";
        echo "Tests réussis: {$passedTests}\n";
        echo "Taux de réussite: {$successRate}%\n\n";
        
        foreach ($this->testResults as $test => $result) {
            $status = $result['success'] ? '✅' : '❌';
            echo "{$status} {$test}\n";
        }
        
        echo "\n";
        
        if ($successRate >= 90) {
            echo "🎉 ARCHITECTURE PRÊTE POUR LA MIGRATION!\n";
            echo "   Tous les composants fonctionnent correctement.\n";
        } elseif ($successRate >= 70) {
            echo "⚠️  ARCHITECTURE PARTIELLEMENT PRÊTE\n";
            echo "   Quelques ajustements nécessaires.\n";
        } else {
            echo "❌ ARCHITECTURE NON PRÊTE\n";
            echo "   Corrections importantes requises.\n";
        }
        
        echo "\n🛡️  GARANTIE: Aucune donnée n'a été modifiée during les tests.\n";
    }

    private function generateReport(): void
    {
        $reportPath = __DIR__ . '/../logs/safe_migration_report_' . date('Y-m-d_H-i-s') . '.json';
        
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'test_mode' => 'safe_read_only',
            'data_protection' => 'guaranteed',
            'results' => $this->testResults,
            'summary' => [
                'total_tests' => count($this->testResults),
                'passed_tests' => count(array_filter($this->testResults, fn($r) => $r['success'])),
                'success_rate' => round((count(array_filter($this->testResults, fn($r) => $r['success'])) / count($this->testResults)) * 100, 1)
            ]
        ];
        
        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        
        echo "📄 Rapport détaillé généré: {$reportPath}\n";
        $this->logger->info('Test sécurisé terminé', $report['summary']);
    }

    // ==========================================
    // MÉTHODES UTILITAIRES
    // ==========================================

    private function addTestResult(string $testName, bool $success, array $data = []): void
    {
        $this->testResults[$testName] = [
            'success' => $success,
            'timestamp' => date('Y-m-d H:i:s'),
            'data' => $data
        ];
    }

    private function parseArguments(array $args): void
    {
        $this->detailedMode = in_array('--detailed', $args);
        $this->benchmarkMode = in_array('--benchmark', $args);
        
        if ($this->detailedMode) {
            echo "🔍 Mode détaillé activé\n";
        }
        
        if ($this->benchmarkMode) {
            echo "⚡ Benchmarks de performance activés\n";
        }
        
        echo "\n";
    }
}

// Exécution du script
if ($argc > 0) {
    $tester = new SafeMigrationTest();
    $tester->run(array_slice($argv, 1));
} else {
    echo "Usage: php safe_migration_test.php [--detailed] [--benchmark]\n";
} 