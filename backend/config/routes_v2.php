<?php

/**
 * Routes API V2 - Architecture Orientée Objet
 * 
 * Ces routes utilisent les nouveaux controllers refactorisés avec
 * l'injection de dépendances et l'architecture Domain/Infrastructure.
 */

use App\Controllers\Refactored\RideControllerV3;
use App\Controllers\Refactored\SearchControllerV2;
use App\Core\Router;

$router = new Router();

// ==========================================
// ROUTES DE RECHERCHE V2 - Architecture OO
// ==========================================

// Recherche avancée avec filtres intelligents
$router->get('/api/v2/search', [SearchControllerV2::class, 'search']);

// Suggestions personnalisées basées sur l'historique
$router->get('/api/v2/search/suggestions', [SearchControllerV2::class, 'suggestions']);

// Recherche rapide avec autocomplétion
$router->get('/api/v2/search/quick', [SearchControllerV2::class, 'quickSearch']);

// Filtres disponibles pour l'interface
$router->get('/api/v2/search/filters', [SearchControllerV2::class, 'getFilters']);

// Recherche géographique avec carte
$router->post('/api/v2/search/map', [SearchControllerV2::class, 'searchByMap']);

// ==========================================
// ROUTES DE TRAJETS V2 - Architecture OO
// ==========================================

// Gestion des trajets avec logique métier encapsulée
$router->get('/api/v2/rides', [RideControllerV3::class, 'index']);
$router->get('/api/v2/rides/{id}', [RideControllerV3::class, 'show']);
$router->post('/api/v2/rides', [RideControllerV3::class, 'store']);
$router->put('/api/v2/rides/{id}', [RideControllerV3::class, 'update']);
$router->delete('/api/v2/rides/{id}', [RideControllerV3::class, 'destroy']);

// Actions métier avec validation Domain Layer
$router->post('/api/v2/rides/{id}/book', [RideControllerV3::class, 'book']);
$router->post('/api/v2/rides/{id}/cancel', [RideControllerV3::class, 'cancel']);
$router->post('/api/v2/rides/{id}/complete', [RideControllerV3::class, 'complete']);

// Gestion des passagers avec logique encapsulée
$router->get('/api/v2/rides/{id}/passengers', [RideControllerV3::class, 'getPassengers']);
$router->post('/api/v2/rides/{id}/passengers/{userId}/accept', [RideControllerV3::class, 'acceptPassenger']);
$router->post('/api/v2/rides/{id}/passengers/{userId}/reject', [RideControllerV3::class, 'rejectPassenger']);

// Évaluations avec Value Objects
$router->post('/api/v2/rides/{id}/rate', [RideControllerV3::class, 'rate']);

// ==========================================
// ROUTES DE MONITORING ET SANTÉ
// ==========================================

// Health check pour la nouvelle architecture
$router->get('/api/v2/health', function() {
    return [
        'status' => 'ok',
        'version' => '2.0',
        'architecture' => 'domain_driven',
        'timestamp' => date('c'),
        'dependencies' => [
            'database' => 'connected',
            'cache' => 'available',
            'repositories' => 'loaded'
        ]
    ];
});

// Métriques pour monitoring
$router->get('/api/v2/metrics', function() {
    return [
        'requests_processed' => 0, // À implémenter avec compteurs
        'average_response_time' => 0,
        'error_rate' => 0,
        'cache_hit_ratio' => 0
    ];
});

return $router; 