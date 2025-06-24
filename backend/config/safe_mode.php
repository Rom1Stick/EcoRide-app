<?php

/**
 * Configuration Mode Sécurisé - Migration OO
 * 
 * Ce mode garantit qu'aucune donnée existante ne sera modifiée pendant
 * la phase de tests et de validation de l'architecture orientée objet.
 */

return [
    // ==========================================
    // PROTECTION DES DONNÉES
    // ==========================================
    
    'data_protection' => [
        // Mode lecture seule strict
        'read_only_mode' => env('MIGRATION_READ_ONLY', true),
        
        // Blocage des opérations d'écriture dangereuses
        'block_write_operations' => env('BLOCK_WRITES', true),
        
        // Whitelist des opérations autorisées
        'allowed_operations' => [
            'SELECT',
            'DESCRIBE',
            'SHOW',
            'EXPLAIN'
        ],
        
        // Tables à protéger absolument
        'protected_tables' => [
            'utilisateur',
            'covoiturage', 
            'lieu',
            'voiture',
            'passager',
            'evaluation',
            'conversation',
            'message'
        ]
    ],

    // ==========================================
    // ENVIRONNEMENTS DE TEST
    // ==========================================
    
    'test_environments' => [
        // Base de données de test isolée
        'test_database' => [
            'enabled' => env('USE_TEST_DB', true),
            'host' => env('TEST_DB_HOST', 'localhost'),
            'database' => env('TEST_DB_NAME', 'ecoride_test'),
            'username' => env('TEST_DB_USER', 'test_user'),
            'password' => env('TEST_DB_PASS', 'test_pass'),
            'auto_seed' => true
        ],
        
        // Données de démonstration
        'demo_data' => [
            'use_fixtures' => true,
            'fixture_path' => BASE_PATH . '/tests/fixtures/',
            'reset_after_test' => true
        ]
    ],

    // ==========================================
    // VALIDATIONS ET GARDE-FOUS
    // ==========================================
    
    'safety_guards' => [
        // Validation avant exécution
        'pre_execution_checks' => [
            'verify_read_only' => true,
            'check_environment' => true,
            'validate_permissions' => true
        ],
        
        // Logging de sécurité
        'security_logging' => [
            'log_all_queries' => true,
            'log_file' => BASE_PATH . '/logs/migration_security.log',
            'alert_on_write_attempt' => true
        ],
        
        // Circuit breakers pour protection
        'circuit_breakers' => [
            'max_query_execution_time' => 30, // secondes
            'max_concurrent_connections' => 5,
            'emergency_stop_enabled' => true
        ]
    ],

    // ==========================================
    // TESTS SÉCURISÉS
    // ==========================================
    
    'safe_testing' => [
        // Tests en lecture seule uniquement
        'read_only_tests' => [
            'repository_queries' => true,
            'data_mapping' => true,
            'performance_benchmarks' => true
        ],
        
        // Transactions rollback automatique
        'transaction_safety' => [
            'auto_rollback' => true,
            'explicit_commit_required' => true,
            'max_transaction_time' => 10 // secondes
        ],
        
        // Isolation des tests
        'test_isolation' => [
            'separate_connection_pool' => true,
            'dedicated_schema' => true,
            'cleanup_after_each_test' => true
        ]
    ],

    // ==========================================
    // MONITORING SPÉCIAL
    // ==========================================
    
    'safety_monitoring' => [
        // Surveillance des accès
        'monitor_database_access' => true,
        'track_query_patterns' => true,
        'alert_thresholds' => [
            'suspicious_query_count' => 100,
            'unexpected_write_attempt' => 1,
            'long_running_query' => 15 // secondes
        ],
        
        // Reporting automatique
        'automated_reports' => [
            'daily_safety_report' => true,
            'real_time_alerts' => true,
            'escalation_enabled' => true
        ]
    ],

    // ==========================================
    // CONFIGURATION PAR ENVIRONNEMENT
    // ==========================================
    
    'environment_configs' => [
        'development' => [
            'strict_mode' => false,
            'allow_test_writes' => true,
            'detailed_logging' => true
        ],
        'testing' => [
            'strict_mode' => true,
            'allow_test_writes' => false,
            'isolated_database' => true
        ],
        'staging' => [
            'strict_mode' => true,
            'allow_test_writes' => false,
            'production_data_clone' => true
        ],
        'production' => [
            'strict_mode' => true,
            'allow_test_writes' => false,
            'maximum_protection' => true
        ]
    ]
]; 