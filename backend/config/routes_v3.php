<?php

/**
 * Configuration des routes Phase 4 - Architecture Orientée Objet Complète
 * Toutes les routes utilisent les contrôleurs V2/V3
 */

use App\Controllers\Refactored\{
    RideControllerV4,
    BookingControllerV2,
    UserControllerV2,
    LocationControllerV2,
    SearchControllerV3
};

return [
    // === ROUTES DE RECHERCHE (SearchControllerV3) ===
    'GET /api/v3/search' => [SearchControllerV3::class, 'search'],
    'GET /api/v3/search/suggestions' => [SearchControllerV3::class, 'suggestions'],
    'GET /api/v3/search/quick' => [SearchControllerV3::class, 'quickSearch'],
    'GET /api/v3/search/map' => [SearchControllerV3::class, 'searchByMap'],
    'GET /api/v3/search/filters' => [SearchControllerV3::class, 'getFilters'],
    'POST /api/v3/search/advanced' => [SearchControllerV3::class, 'advancedSearch'],
    'GET /api/v3/search/history' => [SearchControllerV3::class, 'searchHistory'],
    'POST /api/v3/search/save' => [SearchControllerV3::class, 'saveSearch'],
    
    // === ROUTES DE TRAJETS (RideControllerV4) ===
    'GET /api/v3/rides' => [RideControllerV4::class, 'index'],
    'GET /api/v3/rides/{id}' => [RideControllerV4::class, 'show'],
    'POST /api/v3/rides' => [RideControllerV4::class, 'store'],
    'PUT /api/v3/rides/{id}' => [RideControllerV4::class, 'update'],
    'DELETE /api/v3/rides/{id}' => [RideControllerV4::class, 'destroy'],
    'GET /api/v3/rides/{id}/details' => [RideControllerV4::class, 'getDetails'],
    'POST /api/v3/rides/{id}/join' => [RideControllerV4::class, 'joinRide'],
    'POST /api/v3/rides/{id}/cancel' => [RideControllerV4::class, 'cancelRide'],
    
    // === ROUTES DE RÉSERVATIONS (BookingControllerV2) ===
    'GET /api/v3/bookings' => [BookingControllerV2::class, 'index'],
    'GET /api/v3/bookings/{id}' => [BookingControllerV2::class, 'show'],
    'POST /api/v3/bookings' => [BookingControllerV2::class, 'store'],
    'POST /api/v3/bookings/{id}/confirm' => [BookingControllerV2::class, 'confirm'],
    'POST /api/v3/bookings/{id}/cancel' => [BookingControllerV2::class, 'cancel'],
    'GET /api/v3/bookings/history' => [BookingControllerV2::class, 'history'],
    
    // === ROUTES UTILISATEURS (UserControllerV2) ===
    'GET /api/v3/users/me' => [UserControllerV2::class, 'me'],
    'PUT /api/v3/users/profile' => [UserControllerV2::class, 'updateProfile'],
    'POST /api/v3/users/roles' => [UserControllerV2::class, 'addRole'],
    'POST /api/v3/users/request-role' => [UserControllerV2::class, 'requestRole'],
    'GET /api/v3/users/stats' => [UserControllerV2::class, 'stats'],
    'GET /api/v3/users/search' => [UserControllerV2::class, 'search'],
    
    // === ROUTES DE LIEUX (LocationControllerV2) ===
    'GET /api/v3/locations' => [LocationControllerV2::class, 'index'],
    'GET /api/v3/locations/search' => [LocationControllerV2::class, 'search'],
    'GET /api/v3/locations/{id}' => [LocationControllerV2::class, 'show'],
    'POST /api/v3/locations' => [LocationControllerV2::class, 'store'],
    'PUT /api/v3/locations/{id}/coordinates' => [LocationControllerV2::class, 'updateCoordinates'],
    'POST /api/v3/locations/distance' => [LocationControllerV2::class, 'calculateDistance'],
    
    // === ROUTES DE COMPATIBILITÉ (redirection vers V3) ===
    'GET /api/search' => function() {
        header('Location: /api/v3/search', true, 301);
        exit;
    },
    'GET /api/rides' => function() {
        header('Location: /api/v3/rides', true, 301);
        exit;
    },
    'GET /api/bookings' => function() {
        header('Location: /api/v3/bookings', true, 301);
        exit;
    },
    
    // === ROUTES D'INFORMATION API ===
    'GET /api/v3/info' => function() {
        return json_encode([
            'name' => 'EcoRide API v3',
            'version' => '3.0.0',
            'architecture' => 'Orientée Objet',
            'phase' => 'Phase 4 - Finalisation',
            'endpoints' => [
                'search' => '/api/v3/search',
                'rides' => '/api/v3/rides',
                'bookings' => '/api/v3/bookings',
                'users' => '/api/v3/users',
                'locations' => '/api/v3/locations'
            ],
            'features' => [
                'dependency_injection',
                'repository_pattern',
                'service_layer',
                'value_objects',
                'caching',
                'advanced_search'
            ]
        ]);
    },
    
    'GET /api/v3/health' => function() {
        return json_encode([
            'status' => 'healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '3.0.0',
            'phase' => 'Phase 4',
            'services' => [
                'search_service' => 'active',
                'booking_service' => 'active',
                'user_service' => 'active',
                'location_service' => 'active',
                'cache_service' => 'active'
            ]
        ]);
    }
]; 