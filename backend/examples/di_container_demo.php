<?php
/**
 * Démonstration du Container d'Injection de Dépendances EcoRide
 * 
 * Ce fichier montre comment utiliser le nouveau système DI dans EcoRide
 * pour une architecture moderne et testable.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\Container\ContainerBootstrap;
use App\Core\Container\Container;
use App\Controllers\Refactored\RideControllerV3;
use App\Domain\Services\RideManagementService;
use App\Core\Logger;

echo "=== DÉMONSTRATION CONTAINER DI ECORIDE ===\n\n";

// =============================================================================
// 1. INITIALISATION DU CONTAINER
// =============================================================================

echo "1. Initialisation du container DI...\n";

// Configuration pour l'environnement de développement
$config = ContainerBootstrap::getEnvironmentConfig('development');

// Bootstrap du container avec tous les services
$container = ContainerBootstrap::bootstrap($config);

echo "✅ Container initialisé avec succès!\n";
echo "📊 Services enregistrés: " . count($container->getBindings()) . "\n\n";

// =============================================================================
// 2. VALIDATION DE LA CONFIGURATION
// =============================================================================

echo "2. Validation de la configuration...\n";

$validation = ContainerBootstrap::validateConfiguration();

if ($validation['status'] === 'ok') {
    echo "✅ Configuration valide!\n";
    
    foreach ($validation['services'] as $alias => $info) {
        if ($info['status'] === 'ok') {
            echo "   ✓ {$alias}: " . basename($info['class']) . "\n";
        }
    }
} else {
    echo "❌ Erreurs de configuration:\n";
    foreach ($validation['errors'] as $error) {
        echo "   • {$error}\n";
    }
}

echo "\n";

// =============================================================================
// 3. UTILISATION BASIQUE DU CONTAINER
// =============================================================================

echo "3. Utilisation basique du container...\n";

try {
    // Récupération directe d'un service
    $logger = $container->get(Logger::class);
    echo "✅ Logger récupéré: " . get_class($logger) . "\n";
    
    // Utilisation des alias
    $database = $container->get('database');
    echo "✅ Database récupérée via alias: " . get_class($database) . "\n";
    
    // Résolution automatique avec dépendances
    $rideService = $container->get(RideManagementService::class);
    echo "✅ Service métier résolu: " . get_class($rideService) . "\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n";

// =============================================================================
// 4. CRÉATION DE CONTRÔLEURS V3 AVEC DI
// =============================================================================

echo "4. Création de contrôleurs avec injection automatique...\n";

try {
    // Création d'un contrôleur V3 avec toutes ses dépendances
    $rideController = ContainerBootstrap::createController(RideControllerV3::class);
    echo "✅ RideControllerV3 créé avec toutes ses dépendances!\n";
    
    // Test d'une méthode du contrôleur
    echo "📊 Test de la méthode stats()...\n";
    $stats = $rideController->stats();
    
    if ($stats['success']) {
        echo "✅ Statistiques récupérées avec succès!\n";
        if (isset($stats['data']['total_rides'])) {
            echo "   • Total trajets: " . $stats['data']['total_rides'] . "\n";
        }
    } else {
        echo "⚠️  Erreur lors de la récupération des stats (normal si pas de données)\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur création contrôleur: " . $e->getMessage() . "\n";
}

echo "\n";

// =============================================================================
// 5. UTILISATION DES SERVICES DE CACHE ET PROFILER
// =============================================================================

echo "5. Test des services de cache et profiler...\n";

try {
    // Test du cache
    $cache = $container->get('cache');
    $cache->set('test_key', 'test_value', 300);
    $cachedValue = $cache->get('test_key');
    
    echo "✅ Cache testé: " . ($cachedValue === 'test_value' ? 'OK' : 'ERREUR') . "\n";
    
    // Test du profiler
    $profiler = $container->get('profiler');
    $profiler->start('demo_test');
    
    // Simulation d'une opération
    usleep(1000); // 1ms
    
    $duration = $profiler->stop('demo_test');
    echo "✅ Profiler testé: {$duration}s\n";
    
} catch (Exception $e) {
    echo "❌ Erreur services additionnels: " . $e->getMessage() . "\n";
}

echo "\n";

// =============================================================================
// 6. DÉMONSTRATION HELPERS GLOBAUX
// =============================================================================

echo "6. Test des helpers globaux...\n";

try {
    // Intégration avec l'app legacy (simulation)
    global $app;
    if (!$app) {
        // Création d'une fausse app pour la démo
        $app = new stdClass();
        $app->getDatabase = function() {
            return new stdClass();
        };
    }
    
    ContainerBootstrap::integrateWithLegacyApp($app, $config);
    
    // Test du helper container()
    if (function_exists('container')) {
        $containerViaHelper = container();
        echo "✅ Helper container() disponible: " . get_class($containerViaHelper) . "\n";
        
        $loggerViaHelper = container(Logger::class);
        echo "✅ Service via helper: " . get_class($loggerViaHelper) . "\n";
    }
    
    // Test du helper resolve()
    if (function_exists('resolve')) {
        $resolvedService = resolve(RideManagementService::class);
        echo "✅ Helper resolve() fonctionne: " . get_class($resolvedService) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur helpers: " . $e->getMessage() . "\n";
}

echo "\n";

// =============================================================================
// 7. RAPPORT DE PERFORMANCE
// =============================================================================

echo "7. Rapport de performance du container...\n";

$report = ContainerBootstrap::getPerformanceReport();

if (isset($report['container_stats'])) {
    echo "📊 Statistiques du container:\n";
    echo "   • Services enregistrés: " . $report['container_stats']['bindings_count'] . "\n";
    echo "   • Instances créées: " . $report['container_stats']['instances_count'] . "\n";
    echo "   • Singletons: " . $report['container_stats']['singletons_count'] . "\n";
    echo "   • Alias: " . $report['container_stats']['aliases_count'] . "\n";
    
    if (isset($report['performance_metrics'])) {
        echo "\n📈 Métriques de performance:\n";
        echo "   • Ratio singletons: " . $report['performance_metrics']['singleton_ratio'] . "\n";
        echo "   • Usage mémoire: " . $report['performance_metrics']['memory_usage_mb'] . " MB\n";
        echo "   • Services/MB: " . $report['performance_metrics']['services_per_mb'] . "\n";
    }
    
    if (!empty($report['recommendations'])) {
        echo "\n💡 Recommandations:\n";
        foreach ($report['recommendations'] as $recommendation) {
            echo "   • {$recommendation}\n";
        }
    }
}

echo "\n";

// =============================================================================
// 8. DÉMONSTRATION DE LA MIGRATION PROGRESSIVE
// =============================================================================

echo "8. Configuration de la migration progressive...\n";

try {
    // Setup des routes V3
    ContainerBootstrap::setupProgressiveMigration([
        '/api/v3/rides/special' => RideControllerV3::class,
    ]);
    
    echo "✅ Migration progressive configurée!\n";
    
    // Test du middleware de migration
    $middleware = $container->get('migration.middleware');
    
    // Test avec une route V3
    $v3Controller = $middleware->handle('/api/v3/rides');
    if ($v3Controller) {
        echo "✅ Route V3 détectée: " . get_class($v3Controller) . "\n";
    }
    
    // Test avec une route legacy
    $legacyController = $middleware->handle('/api/v1/rides');
    if (!$legacyController) {
        echo "✅ Route legacy détectée (retourne null pour traitement classique)\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur migration: " . $e->getMessage() . "\n";
}

echo "\n";

// =============================================================================
// 9. SIMULATION D'UNE REQUÊTE COMPLÈTE
// =============================================================================

echo "9. Simulation d'une requête API complète...\n";

try {
    // Simulation des données de requête
    $_GET = [
        'page' => 1,
        'limit' => 5,
        'departure' => 'Paris',
        'arrival' => 'Lyon'
    ];
    
    // Création du contrôleur et appel de la méthode
    $controller = ContainerBootstrap::createController(RideControllerV3::class);
    
    echo "📡 Simulation GET /api/v3/rides...\n";
    
    // Appel de la méthode index (recherche de trajets)
    $response = $controller->index();
    
    if ($response['success']) {
        echo "✅ Requête réussie!\n";
        echo "   • Nombre de trajets: " . count($response['data']['rides'] ?? []) . "\n";
        
        if (isset($response['data']['meta']['execution_time'])) {
            echo "   • Temps d'exécution: " . $response['data']['meta']['execution_time'] . "s\n";
        }
    } else {
        echo "⚠️  Requête échouée (normal si pas de données): " . $response['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur simulation: " . $e->getMessage() . "\n";
}

echo "\n";

// =============================================================================
// 10. DEBUG ET DÉVELOPPEMENT
// =============================================================================

if ($config['environment'] === 'development') {
    echo "10. Informations de debug (mode développement)...\n";
    
    try {
        $debugController = ContainerBootstrap::createController(RideControllerV3::class);
        $debugInfo = $debugController->debugContainer();
        
        if ($debugInfo['success']) {
            echo "🔧 Informations de debug disponibles:\n";
            echo "   • Services: " . count($debugInfo['data']['services'] ?? []) . "\n";
            echo "   • Timers: " . count($debugInfo['data']['profiler_timers'] ?? []) . "\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Erreur debug: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// =============================================================================
// CONCLUSION
// =============================================================================

echo "=== DÉMONSTRATION TERMINÉE ===\n";
echo "\n✨ Le container d'injection de dépendances EcoRide est prêt!\n";
echo "\n🚀 Avantages obtenus:\n";
echo "   • Gestion automatique des dépendances\n";
echo "   • Architecture modulaire et testable\n";
echo "   • Services de cache et profiling intégrés\n";
echo "   • Migration progressive depuis l'architecture legacy\n";
echo "   • Debugging et monitoring avancés\n";
echo "\n📖 Prochaines étapes:\n";
echo "   1. Intégrer le container dans le bootstrap principal\n";
echo "   2. Migrer progressivement les routes vers les contrôleurs V3\n";
echo "   3. Ajouter des tests unitaires avec mocking facilité\n";
echo "   4. Optimiser les performances avec plus de singletons\n";
echo "\n🎯 Architecture EcoRide modernisée avec succès!\n"; 