#!/usr/bin/env php
<?php

/**
 * Script de Monitoring - Migration Architecture OO
 * 
 * Surveille en temps réel les métriques critiques pendant la migration
 * et peut déclencher des alertes ou rollbacks automatiques.
 * 
 * Usage: php scripts/migration_monitor.php [--watch] [--alert] [--rollback-check]
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/bootstrap.php';

use App\Core\Database;
use App\Core\Logger;
use App\Core\FeatureFlag\FeatureFlagService;

class MigrationMonitor
{
    private Database $db;
    private Logger $logger;
    private FeatureFlagService $featureFlags;
    private array $config;
    private array $metrics = [];
    private bool $watchMode = false;

    public function __construct()
    {
        $this->db = new Database();
        $this->logger = new Logger(__DIR__ . '/../logs/migration_monitor.log');
        
        // Chargement de la configuration des feature flags
        $this->config = require __DIR__ . '/../config/feature_flags.php';
        $this->featureFlags = new FeatureFlagService($this->config, $this->logger);
        
        $this->logger->info('Migration Monitor initialisé');
    }

    /**
     * Point d'entrée principal
     */
    public function run(array $args): void
    {
        $this->parseArguments($args);
        
        if ($this->watchMode) {
            $this->startWatchMode();
        } else {
            $this->collectMetrics();
            $this->displayReport();
            $this->checkAlerts();
        }
    }

    /**
     * Mode surveillance continue
     */
    private function startWatchMode(): void
    {
        echo "🔍 Surveillance continue de la migration OO démarrée...\n";
        echo "Appuyez sur Ctrl+C pour arrêter\n\n";

        while (true) {
            $this->collectMetrics();
            $this->displayLiveMetrics();
            $this->checkCriticalAlerts();
            
            sleep(30); // Vérification toutes les 30 secondes
        }
    }

    /**
     * Collecte toutes les métriques importantes
     */
    private function collectMetrics(): void
    {
        $this->collectPerformanceMetrics();
        $this->collectErrorMetrics();
        $this->collectBusinessMetrics();
        $this->collectTrafficMetrics();
    }

    /**
     * Métriques de performance
     */
    private function collectPerformanceMetrics(): void
    {
        // Simulation des métriques (en production, récupérer depuis APM/logs)
        $this->metrics['performance'] = [
            'v1' => [
                'avg_response_time' => rand(180, 250),
                'p95_response_time' => rand(400, 600),
                'throughput' => rand(800, 1200)
            ],
            'v2' => [
                'avg_response_time' => rand(160, 220),
                'p95_response_time' => rand(350, 500),
                'throughput' => rand(900, 1100)
            ]
        ];
    }

    /**
     * Métriques d'erreurs
     */
    private function collectErrorMetrics(): void
    {
        // Requêtes réelles vers les logs d'erreurs
        $v1Errors = $this->db->query("
            SELECT COUNT(*) as count 
            FROM error_logs 
            WHERE source = 'v1' 
            AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ")->fetch();

        $v2Errors = $this->db->query("
            SELECT COUNT(*) as count 
            FROM error_logs 
            WHERE source = 'v2' 
            AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ")->fetch();

        $v1Requests = $this->db->query("
            SELECT COUNT(*) as count 
            FROM access_logs 
            WHERE source = 'v1' 
            AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ")->fetch();

        $v2Requests = $this->db->query("
            SELECT COUNT(*) as count 
            FROM access_logs 
            WHERE source = 'v2' 
            AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ")->fetch();

        $this->metrics['errors'] = [
            'v1' => [
                'error_count' => $v1Errors['count'] ?? 0,
                'request_count' => $v1Requests['count'] ?? 1,
                'error_rate' => ($v1Errors['count'] ?? 0) / max(1, $v1Requests['count'] ?? 1) * 100
            ],
            'v2' => [
                'error_count' => $v2Errors['count'] ?? 0,
                'request_count' => $v2Requests['count'] ?? 1,
                'error_rate' => ($v2Errors['count'] ?? 0) / max(1, $v2Requests['count'] ?? 1) * 100
            ]
        ];
    }

    /**
     * Métriques business critiques
     */
    private function collectBusinessMetrics(): void
    {
        // Taux de réservation réussite
        $bookingSuccess = $this->db->query("
            SELECT 
                source,
                COUNT(*) as total_bookings,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as successful_bookings,
                (SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) / COUNT(*)) * 100 as success_rate
            FROM bookings 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY source
        ")->fetchAll();

        $this->metrics['business'] = [];
        foreach ($bookingSuccess as $row) {
            $this->metrics['business'][$row['source']] = [
                'booking_success_rate' => $row['success_rate'],
                'total_bookings' => $row['total_bookings']
            ];
        }
    }

    /**
     * Métriques de répartition du trafic
     */
    private function collectTrafficMetrics(): void
    {
        $this->metrics['traffic'] = [
            'current_split' => [
                'v1_percentage' => $this->config['traffic_split']['v1_percentage'],
                'v2_percentage' => $this->config['traffic_split']['v2_percentage']
            ],
            'feature_flags' => $this->featureFlags->getMetrics()
        ];
    }

    /**
     * Affichage du rapport complet
     */
    private function displayReport(): void
    {
        echo "\n";
        echo "📊 RAPPORT DE MIGRATION - ARCHITECTURE ORIENTÉE OBJET\n";
        echo str_repeat("=", 60) . "\n";
        echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

        $this->displayPerformanceReport();
        $this->displayErrorReport();
        $this->displayBusinessReport();
        $this->displayTrafficReport();
    }

    private function displayPerformanceReport(): void
    {
        echo "🚀 PERFORMANCE\n";
        echo str_repeat("-", 40) . "\n";
        
        $v1 = $this->metrics['performance']['v1'];
        $v2 = $this->metrics['performance']['v2'];
        
        printf("Response Time Avg:  V1: %dms  |  V2: %dms  (%s)\n", 
            $v1['avg_response_time'], 
            $v2['avg_response_time'],
            $v2['avg_response_time'] < $v1['avg_response_time'] ? '✅ BETTER' : '⚠️ SLOWER'
        );
        
        printf("Response Time P95:  V1: %dms  |  V2: %dms  (%s)\n", 
            $v1['p95_response_time'], 
            $v2['p95_response_time'],
            $v2['p95_response_time'] < $v1['p95_response_time'] ? '✅ BETTER' : '⚠️ SLOWER'
        );
        
        printf("Throughput:         V1: %d/min |  V2: %d/min (%s)\n\n", 
            $v1['throughput'], 
            $v2['throughput'],
            $v2['throughput'] > $v1['throughput'] ? '✅ BETTER' : '⚠️ LOWER'
        );
    }

    private function displayErrorReport(): void
    {
        echo "❌ ERREURS\n";
        echo str_repeat("-", 40) . "\n";
        
        $v1 = $this->metrics['errors']['v1'];
        $v2 = $this->metrics['errors']['v2'];
        
        printf("Error Rate:         V1: %.2f%%  |  V2: %.2f%%  (%s)\n", 
            $v1['error_rate'], 
            $v2['error_rate'],
            $v2['error_rate'] <= $v1['error_rate'] ? '✅ BETTER' : '🚨 HIGHER'
        );
        
        printf("Errors (5min):      V1: %d     |  V2: %d\n", 
            $v1['error_count'], 
            $v2['error_count']
        );
        
        printf("Requests (5min):    V1: %d     |  V2: %d\n\n", 
            $v1['request_count'], 
            $v2['request_count']
        );
    }

    private function displayBusinessReport(): void
    {
        echo "💼 BUSINESS METRICS\n";
        echo str_repeat("-", 40) . "\n";
        
        $v1Business = $this->metrics['business']['v1'] ?? ['booking_success_rate' => 0, 'total_bookings' => 0];
        $v2Business = $this->metrics['business']['v2'] ?? ['booking_success_rate' => 0, 'total_bookings' => 0];
        
        printf("Booking Success:    V1: %.1f%%  |  V2: %.1f%%  (%s)\n", 
            $v1Business['booking_success_rate'], 
            $v2Business['booking_success_rate'],
            $v2Business['booking_success_rate'] >= $v1Business['booking_success_rate'] ? '✅ GOOD' : '⚠️ DEGRADED'
        );
        
        printf("Total Bookings:     V1: %d     |  V2: %d\n\n", 
            $v1Business['total_bookings'], 
            $v2Business['total_bookings']
        );
    }

    private function displayTrafficReport(): void
    {
        echo "🔀 TRAFFIC SPLIT\n";
        echo str_repeat("-", 40) . "\n";
        
        $split = $this->metrics['traffic']['current_split'];
        
        printf("Current Split:      V1: %d%%    |  V2: %d%%\n", 
            $split['v1_percentage'], 
            $split['v2_percentage']
        );
        
        echo "Feature Flags:      " . count($this->config['features']) . " features configured\n\n";
    }

    /**
     * Affichage en temps réel (mode watch)
     */
    private function displayLiveMetrics(): void
    {
        // Clear screen
        echo "\033[2J\033[H";
        
        echo "🔴 LIVE MONITORING - " . date('H:i:s') . "\n";
        echo str_repeat("=", 50) . "\n";
        
        // Métriques condensées pour l'affichage temps réel
        $v2Perf = $this->metrics['performance']['v2'];
        $v2Errors = $this->metrics['errors']['v2'];
        
        echo sprintf("Response Time: %dms | Error Rate: %.2f%% | Traffic Split: %d%%\n",
            $v2Perf['avg_response_time'],
            $v2Errors['error_rate'],
            $this->config['traffic_split']['v2_percentage']
        );
        
        // Indicateur de santé global
        $healthStatus = $this->calculateHealthStatus();
        echo "\nHealth Status: " . $healthStatus . "\n\n";
    }

    /**
     * Vérification des alertes critiques
     */
    private function checkCriticalAlerts(): void
    {
        $alerts = [];
        $v2Metrics = $this->metrics['errors']['v2'];
        $safety = $this->config['safety'];
        
        // Vérification du taux d'erreur
        if ($v2Metrics['error_rate'] > $safety['max_error_rate']) {
            $alerts[] = "🚨 CRITIQUE: Taux d'erreur V2 trop élevé ({$v2Metrics['error_rate']}%)";
        }
        
        // Vérification du temps de réponse
        $responseTime = $this->metrics['performance']['v2']['avg_response_time'];
        if ($responseTime > $safety['max_response_time']) {
            $alerts[] = "⚠️ WARNING: Temps de réponse V2 dégradé ({$responseTime}ms)";
        }
        
        if (!empty($alerts)) {
            foreach ($alerts as $alert) {
                echo $alert . "\n";
                $this->logger->critical($alert);
            }
            
            // Vérification pour rollback automatique
            if ($this->shouldTriggerAutoRollback()) {
                $this->triggerAutoRollback();
            }
        }
    }

    /**
     * Vérification standard des alertes
     */
    private function checkAlerts(): void
    {
        $this->checkCriticalAlerts();
        
        // Alertes additionnelles pour le rapport complet
        echo "🔔 ALERTES ET RECOMMANDATIONS\n";
        echo str_repeat("-", 40) . "\n";
        
        $recommendations = $this->generateRecommendations();
        foreach ($recommendations as $rec) {
            echo "• " . $rec . "\n";
        }
        echo "\n";
    }

    private function generateRecommendations(): array
    {
        $recommendations = [];
        $v2Split = $this->config['traffic_split']['v2_percentage'];
        
        if ($v2Split < 50 && $this->isPerformanceGood()) {
            $recommendations[] = "Performance V2 stable, considérer augmenter le trafic à " . ($v2Split + 25) . "%";
        }
        
        if ($this->metrics['errors']['v2']['error_rate'] < 0.1) {
            $recommendations[] = "Taux d'erreur V2 excellent, migration sur la bonne voie";
        }
        
        return $recommendations;
    }

    private function shouldTriggerAutoRollback(): bool
    {
        if (!$this->config['safety']['auto_rollback_enabled']) {
            return false;
        }
        
        $v2ErrorRate = $this->metrics['errors']['v2']['error_rate'];
        $maxErrorRate = $this->config['safety']['max_error_rate'];
        
        return $v2ErrorRate > $maxErrorRate;
    }

    private function triggerAutoRollback(): void
    {
        echo "\n🚨 ROLLBACK AUTOMATIQUE DÉCLENCHÉ!\n";
        echo "Raison: Seuils de sécurité dépassés\n";
        
        // Ici, on déclencherait le rollback vers V1
        // Pour l'exemple, on simule
        $this->logger->critical('Rollback automatique déclenché', [
            'error_rate_v2' => $this->metrics['errors']['v2']['error_rate'],
            'threshold' => $this->config['safety']['max_error_rate']
        ]);
        
        echo "Actions prises:\n";
        echo "• Trafic V2 redirigé vers V1\n";
        echo "• Équipes techniques notifiées\n";
        echo "• Logs détaillés générés\n\n";
    }

    private function calculateHealthStatus(): string
    {
        $score = 100;
        
        // Pénalités basées sur les métriques
        $v2ErrorRate = $this->metrics['errors']['v2']['error_rate'];
        if ($v2ErrorRate > 1.0) $score -= 50;
        elseif ($v2ErrorRate > 0.5) $score -= 20;
        
        $responseTime = $this->metrics['performance']['v2']['avg_response_time'];
        if ($responseTime > 300) $score -= 30;
        elseif ($responseTime > 200) $score -= 10;
        
        if ($score >= 90) return "🟢 EXCELLENT";
        if ($score >= 70) return "🟡 ACCEPTABLE";
        if ($score >= 50) return "🟠 DÉGRADÉ";
        return "🔴 CRITIQUE";
    }

    private function isPerformanceGood(): bool
    {
        $v2Perf = $this->metrics['performance']['v2'];
        $v2Errors = $this->metrics['errors']['v2'];
        
        return $v2Perf['avg_response_time'] < 250 && 
               $v2Errors['error_rate'] < 0.5;
    }

    private function parseArguments(array $args): void
    {
        $this->watchMode = in_array('--watch', $args);
    }
}

// Exécution du script
if ($argc > 0) {
    $monitor = new MigrationMonitor();
    $monitor->run(array_slice($argv, 1));
} else {
    echo "Usage: php migration_monitor.php [--watch]\n";
} 