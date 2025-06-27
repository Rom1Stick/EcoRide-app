<?php

/**
 * Feature Flags - Migration Progressive Architecture OO
 * 
 * Ce système permet de contrôler finement quelles fonctionnalités 
 * utilisent la nouvelle architecture orientée objet.
 */

return [
    // ==========================================
    // CONTRÔLE DU TRAFIC GLOBAL
    // ==========================================
    
    'traffic_split' => [
        'v1_percentage' => (int) env('TRAFFIC_V1_PERCENTAGE', 90),
        'v2_percentage' => (int) env('TRAFFIC_V2_PERCENTAGE', 10),
        'enabled' => env('TRAFFIC_SPLIT_ENABLED', true)
    ],

    // ==========================================
    // ACTIVATION PAR FONCTIONNALITÉ
    // ==========================================
    
    'features' => [
        // Recherche et filtrage
        'oo_search_advanced' => env('FEATURE_OO_SEARCH_ADVANCED', true),
        'oo_search_suggestions' => env('FEATURE_OO_SEARCH_SUGGESTIONS', true),
        'oo_search_map' => env('FEATURE_OO_SEARCH_MAP', false),
        
        // Gestion des trajets
        'oo_ride_management' => env('FEATURE_OO_RIDE_MANAGEMENT', true),
        'oo_ride_booking' => env('FEATURE_OO_RIDE_BOOKING', false),
        'oo_ride_validation' => env('FEATURE_OO_RIDE_VALIDATION', true),
        
        // Fonctionnalités utilisateur
        'oo_user_profile' => env('FEATURE_OO_USER_PROFILE', false),
        'oo_user_rating' => env('FEATURE_OO_USER_RATING', false),
        
        // Paiements (critique)
        'oo_payments' => env('FEATURE_OO_PAYMENTS', false),
        'oo_payment_validation' => env('FEATURE_OO_PAYMENT_VALIDATION', false),
        
        // Notifications
        'oo_notifications' => env('FEATURE_OO_NOTIFICATIONS', false),
        
        // Analytics et métriques
        'oo_analytics' => env('FEATURE_OO_ANALYTICS', true),
        'oo_monitoring' => env('FEATURE_OO_MONITORING', true)
    ],

    // ==========================================
    // CONTRÔLE PAR UTILISATEUR/GROUPE
    // ==========================================
    
    'user_groups' => [
        // Utilisateurs beta (early adopters)
        'beta_users' => env('BETA_USERS_OO_ENABLED', true),
        'beta_user_ids' => explode(',', env('BETA_USER_IDS', '')),
        
        // Staff interne
        'staff_users' => env('STAFF_OO_ENABLED', true),
        'staff_domains' => ['@ecoride.fr', '@admin.ecoride.fr'],
        
        // Nouveaux utilisateurs
        'new_users_threshold' => env('NEW_USERS_OO_THRESHOLD', 30), // jours
        'new_users_enabled' => env('NEW_USERS_OO_ENABLED', true)
    ],

    // ==========================================
    // MONITORING ET SÉCURITÉ
    // ==========================================
    
    'safety' => [
        // Seuils d'alerte automatique
        'max_error_rate' => env('OO_MAX_ERROR_RATE', 1.0), // %
        'max_response_time' => env('OO_MAX_RESPONSE_TIME', 500), // ms
        'min_success_rate' => env('OO_MIN_SUCCESS_RATE', 99.0), // %
        
        // Rollback automatique
        'auto_rollback_enabled' => env('AUTO_ROLLBACK_ENABLED', true),
        'rollback_threshold_minutes' => env('ROLLBACK_THRESHOLD_MINUTES', 5),
        
        // Circuit breaker
        'circuit_breaker_enabled' => env('CIRCUIT_BREAKER_ENABLED', true),
        'circuit_breaker_threshold' => env('CIRCUIT_BREAKER_THRESHOLD', 5) // erreurs consécutives
    ],

    // ==========================================
    // ENVIRONNEMENTS SPÉCIFIQUES
    // ==========================================
    
    'environments' => [
        'development' => [
            'force_v2' => true,
            'debug_routing' => true,
            'log_feature_usage' => true
        ],
        'staging' => [
            'force_v2' => false,
            'traffic_split_enabled' => true,
            'monitoring_enhanced' => true
        ],
        'production' => [
            'force_v2' => false,
            'gradual_rollout' => true,
            'safety_checks_enabled' => true
        ]
    ],

    // ==========================================
    // CONFIGURATION DE DÉPLOIEMENT
    // ==========================================
    
    'deployment' => [
        'canary_release' => [
            'enabled' => env('CANARY_RELEASE_ENABLED', false),
            'percentage' => env('CANARY_PERCENTAGE', 5),
            'duration_hours' => env('CANARY_DURATION_HOURS', 24)
        ],
        
        'blue_green' => [
            'enabled' => env('BLUE_GREEN_ENABLED', false),
            'switch_threshold' => env('SWITCH_THRESHOLD_PERCENTAGE', 100)
        ],
        
        'progressive_rollout' => [
            'enabled' => env('PROGRESSIVE_ROLLOUT_ENABLED', true),
            'step_percentage' => env('ROLLOUT_STEP_PERCENTAGE', 25),
            'step_duration_hours' => env('ROLLOUT_STEP_DURATION', 48)
        ]
    ],

    // ==========================================
    // MÉTRIQUES ET OBSERVABILITÉ
    // ==========================================
    
    'metrics' => [
        'track_usage' => env('TRACK_FEATURE_USAGE', true),
        'track_performance' => env('TRACK_PERFORMANCE', true),
        'track_errors' => env('TRACK_ERRORS', true),
        'track_business_metrics' => env('TRACK_BUSINESS_METRICS', true),
        
        'retention_days' => env('METRICS_RETENTION_DAYS', 30),
        'aggregation_interval' => env('METRICS_AGGREGATION_INTERVAL', 300) // 5 minutes
    ]
]; 