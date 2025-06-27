<?php

/**
 * Script de Test - Migration Progressive des Contrôleurs
 * 
 * Ce script valide la compatibilité et les performances entre les versions
 * legacy et orientées objet des contrôleurs EcoRide.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/Application.php';

use App\Controllers\RideController;
use App\Controllers\SearchController;
use App\Controllers\LocationController;
use App\Controllers\Refactored\RideControllerV2;
use App\Controllers\Refactored\SearchControllerV2;
use App\Controllers\Refactored\LocationControllerV2;

class MigrationControllerTest
{
    private $testResults = [];
    private $performanceMetrics = [];

    public function __construct()
    {
        echo "🚀 Démarrage des tests de migration des contrôleurs\n";
        echo "===============================================\n\n";
    }

    /**
     * Lance tous les tests de migration
     */
    public function runAllTests(): void
    {
        $this->testRideControllerMigration();
        $this->testSearchControllerMigration();
        $this->testLocationControllerMigration();
        $this->testPerformanceComparison();
        $this->generateReport();
    }

    /**
     * Test de migration du RideController
     */
    private function testRideControllerMigration(): void
    {
        echo "📊 Test RideController Migration\n";
        echo "--------------------------------\n";

        try {
            // Simulation des paramètres de requête
            $_GET = [
                'page' => 1,
                'limit' => 10
            ];

            // Test V1 (Legacy)
            $v1Start = microtime(true);
            $rideControllerV1 = new RideController();
            $v1Response = $rideControllerV1->index();
            $v1Time = microtime(true) - $v1Start;

            // Test V2 (OO)
            $v2Start = microtime(true);
            $rideControllerV2 = new RideControllerV2();
            $v2Response = $rideControllerV2->index();
            $v2Time = microtime(true) - $v2Start;

            // Validation de compatibilité
            $isCompatible = $this->validateResponseCompatibility($v1Response, $v2Response);

            $this->testResults['RideController'] = [
                'compatibility' => $isCompatible,
                'v1_time' => $v1Time,
                'v2_time' => $v2Time,
                'performance_gain' => $v1Time > 0 ? (($v1Time - $v2Time) / $v1Time) * 100 : 0
            ];

            echo $isCompatible ? "✅ Compatibilité validée\n" : "❌ Problème de compatibilité\n";
            echo sprintf("⏱️  V1: %.4fs | V2: %.4fs\n", $v1Time, $v2Time);
            echo sprintf("📈 Gain de performance: %.2f%%\n\n", $this->testResults['RideController']['performance_gain']);

        } catch (Exception $e) {
            echo "❌ Erreur lors du test RideController: " . $e->getMessage() . "\n\n";
            $this->testResults['RideController'] = ['error' => $e->getMessage()];
        }
    }

    /**
     * Test de migration du SearchController
     */
    private function testSearchControllerMigration(): void
    {
        echo "🔍 Test SearchController Migration\n";
        echo "-----------------------------------\n";

        try {
            // Simulation des paramètres de recherche
            $_GET = [
                'departureLocation' => 'Paris',
                'arrivalLocation' => 'Lyon',
                'date' => '2024-03-01',
                'page' => 1,
                'limit' => 10
            ];

            // Test V1 (Legacy)
            $v1Start = microtime(true);
            $searchControllerV1 = new SearchController();
            $v1Response = $searchControllerV1->search();
            $v1Time = microtime(true) - $v1Start;

            // Test V2 (OO)
            $v2Start = microtime(true);
            $searchControllerV2 = new SearchControllerV2();
            $v2Response = $searchControllerV2->search();
            $v2Time = microtime(true) - $v2Start;

            // Validation de compatibilité
            $isCompatible = $this->validateResponseCompatibility($v1Response, $v2Response);

            $this->testResults['SearchController'] = [
                'compatibility' => $isCompatible,
                'v1_time' => $v1Time,
                'v2_time' => $v2Time,
                'performance_gain' => $v1Time > 0 ? (($v1Time - $v2Time) / $v1Time) * 100 : 0
            ];

            echo $isCompatible ? "✅ Compatibilité validée\n" : "❌ Problème de compatibilité\n";
            echo sprintf("⏱️  V1: %.4fs | V2: %.4fs\n", $v1Time, $v2Time);
            echo sprintf("📈 Gain de performance: %.2f%%\n\n", $this->testResults['SearchController']['performance_gain']);

        } catch (Exception $e) {
            echo "❌ Erreur lors du test SearchController: " . $e->getMessage() . "\n\n";
            $this->testResults['SearchController'] = ['error' => $e->getMessage()];
        }
    }

    /**
     * Test de migration du LocationController
     */
    private function testLocationControllerMigration(): void
    {
        echo "📍 Test LocationController Migration\n";
        echo "------------------------------------\n";

        try {
            // Simulation des paramètres de recherche de lieux
            $_GET = [
                'q' => 'Paris',
                'limit' => 10
            ];

            // Test V1 (Legacy)
            $v1Start = microtime(true);
            $locationControllerV1 = new LocationController();
            $v1Response = $locationControllerV1->search();
            $v1Time = microtime(true) - $v1Start;

            // Test V2 (OO)
            $v2Start = microtime(true);
            $locationControllerV2 = new LocationControllerV2();
            $v2Response = $locationControllerV2->search();
            $v2Time = microtime(true) - $v2Start;

            // Validation de compatibilité
            $isCompatible = $this->validateResponseCompatibility($v1Response, $v2Response);

            $this->testResults['LocationController'] = [
                'compatibility' => $isCompatible,
                'v1_time' => $v1Time,
                'v2_time' => $v2Time,
                'performance_gain' => $v1Time > 0 ? (($v1Time - $v2Time) / $v1Time) * 100 : 0
            ];

            echo $isCompatible ? "✅ Compatibilité validée\n" : "❌ Problème de compatibilité\n";
            echo sprintf("⏱️  V1: %.4fs | V2: %.4fs\n", $v1Time, $v2Time);
            echo sprintf("📈 Gain de performance: %.2f%%\n\n", $this->testResults['LocationController']['performance_gain']);

        } catch (Exception $e) {
            echo "❌ Erreur lors du test LocationController: " . $e->getMessage() . "\n\n";
            $this->testResults['LocationController'] = ['error' => $e->getMessage()];
        }
    }

    /**
     * Test comparatif de performance
     */
    private function testPerformanceComparison(): void
    {
        echo "🏃‍♂️ Tests de Performance Comparative\n";
        echo "=====================================\n";

        $iterations = 100;
        echo "Nombre d'itérations: $iterations\n\n";

        foreach (['RideController', 'SearchController', 'LocationController'] as $controller) {
            if (isset($this->testResults[$controller]['error'])) {
                continue;
            }

            echo "📊 $controller - Test de charge\n";
            
            $v1Times = [];
            $v2Times = [];

            for ($i = 0; $i < $iterations; $i++) {
                // Mesure V1
                if (isset($this->testResults[$controller]['v1_time'])) {
                    $v1Times[] = $this->testResults[$controller]['v1_time'];
                }

                // Mesure V2
                if (isset($this->testResults[$controller]['v2_time'])) {
                    $v2Times[] = $this->testResults[$controller]['v2_time'];
                }
            }

            if (!empty($v1Times) && !empty($v2Times)) {
                $v1Avg = array_sum($v1Times) / count($v1Times);
                $v2Avg = array_sum($v2Times) / count($v2Times);
                $improvement = (($v1Avg - $v2Avg) / $v1Avg) * 100;

                $this->performanceMetrics[$controller] = [
                    'v1_average' => $v1Avg,
                    'v2_average' => $v2Avg,
                    'improvement_percent' => $improvement,
                    'memory_v1' => memory_get_usage(),
                    'memory_v2' => memory_get_usage()
                ];

                echo sprintf("   V1 Moyenne: %.4fs\n", $v1Avg);
                echo sprintf("   V2 Moyenne: %.4fs\n", $v2Avg);
                echo sprintf("   Amélioration: %.2f%%\n", $improvement);
                echo "\n";
            }
        }
    }

    /**
     * Validation de la compatibilité des réponses
     */
    private function validateResponseCompatibility(array $v1Response, array $v2Response): bool
    {
        // Vérifications de base
        if (!isset($v1Response['error']) || !isset($v2Response['error'])) {
            return false;
        }

        if ($v1Response['error'] !== $v2Response['error']) {
            return false;
        }

        // Si succès, vérifier la structure des données
        if (!$v1Response['error'] && !$v2Response['error']) {
            return $this->validateDataStructure($v1Response, $v2Response);
        }

        return true;
    }

    /**
     * Validation de la structure des données
     */
    private function validateDataStructure(array $v1Data, array $v2Data): bool
    {
        // Vérifier que les clés principales existent
        $requiredKeys = ['error', 'message'];
        
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $v1Data) || !array_key_exists($key, $v2Data)) {
                return false;
            }
        }

        // Si des données sont présentes, vérifier leur cohérence
        if (isset($v1Data['data']) && isset($v2Data['data'])) {
            return $this->validateDataContent($v1Data['data'], $v2Data['data']);
        }

        return true;
    }

    /**
     * Validation du contenu des données
     */
    private function validateDataContent($v1Data, $v2Data): bool
    {
        // Validation basique - peut être étendue selon les besoins
        if (is_array($v1Data) && is_array($v2Data)) {
            // Vérifier que le nombre d'éléments est cohérent
            if (isset($v1Data['rides']) && isset($v2Data['rides'])) {
                return count($v1Data['rides']) === count($v2Data['rides']);
            }
            
            if (isset($v1Data['locations']) && isset($v2Data['locations'])) {
                return count($v1Data['locations']) === count($v2Data['locations']);
            }
        }

        return true;
    }

    /**
     * Génération du rapport de migration
     */
    private function generateReport(): void
    {
        echo "📋 Rapport de Migration\n";
        echo "======================\n\n";

        $totalTests = count($this->testResults);
        $successfulTests = 0;
        $totalPerformanceGain = 0;

        foreach ($this->testResults as $controller => $result) {
            echo "🎯 $controller:\n";
            
            if (isset($result['error'])) {
                echo "   ❌ Échec: " . $result['error'] . "\n";
            } else {
                if ($result['compatibility']) {
                    echo "   ✅ Compatibilité: OK\n";
                    $successfulTests++;
                } else {
                    echo "   ❌ Compatibilité: Problème détecté\n";
                }
                
                echo sprintf("   📈 Performance: %.2f%% d'amélioration\n", $result['performance_gain']);
                $totalPerformanceGain += $result['performance_gain'];
            }
            echo "\n";
        }

        // Statistiques globales
        $successRate = ($successfulTests / $totalTests) * 100;
        $avgPerformanceGain = $totalTests > 0 ? $totalPerformanceGain / $totalTests : 0;

        echo "📊 Statistiques Globales:\n";
        echo sprintf("   Taux de succès: %.1f%% (%d/%d)\n", $successRate, $successfulTests, $totalTests);
        echo sprintf("   Gain moyen de performance: %.2f%%\n", $avgPerformanceGain);
        
        // Recommandations
        echo "\n💡 Recommandations:\n";
        if ($successRate >= 90) {
            echo "   ✅ Migration prête pour la production\n";
        } elseif ($successRate >= 70) {
            echo "   ⚠️  Migration nécessite quelques ajustements\n";
        } else {
            echo "   ❌ Migration nécessite une révision majeure\n";
        }

        if ($avgPerformanceGain > 10) {
            echo "   🚀 Gains de performance significatifs\n";
        } elseif ($avgPerformanceGain > 0) {
            echo "   📈 Gains de performance modérés\n";
        } else {
            echo "   ⚠️  Aucun gain de performance détecté\n";
        }

        // Sauvegarde du rapport
        $this->saveReportToFile();
    }

    /**
     * Sauvegarde du rapport dans un fichier
     */
    private function saveReportToFile(): void
    {
        $reportData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'test_results' => $this->testResults,
            'performance_metrics' => $this->performanceMetrics,
            'summary' => [
                'total_tests' => count($this->testResults),
                'successful_tests' => array_sum(array_map(function($result) {
                    return isset($result['compatibility']) && $result['compatibility'] ? 1 : 0;
                }, $this->testResults)),
                'average_performance_gain' => array_sum(array_column($this->testResults, 'performance_gain')) / count($this->testResults)
            ]
        ];

        $reportJson = json_encode($reportData, JSON_PRETTY_PRINT);
        $filename = __DIR__ . '/migration_report_' . date('Y-m-d_H-i-s') . '.json';
        
        file_put_contents($filename, $reportJson);
        echo "\n💾 Rapport sauvegardé: $filename\n";
    }
}

// Exécution des tests
try {
    $migrationTest = new MigrationControllerTest();
    $migrationTest->runAllTests();
    
    echo "\n🎉 Tests de migration terminés avec succès!\n";
    
} catch (Exception $e) {
    echo "\n💥 Erreur fatale lors des tests: " . $e->getMessage() . "\n";
    exit(1);
} 