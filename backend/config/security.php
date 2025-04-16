<?php

/**
 * Configuration de sécurité de l'application
 */

return [
    /**
     * Paramètres des fichiers uploadés
     */
    'uploads' => [
        'max_size' => env('UPLOAD_MAX_SIZE', 5 * 1024 * 1024), // 5 Mo par défaut
        'allowed_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'text/plain',
            'text/csv'
        ],
        'directory' => env('UPLOAD_DIRECTORY', 'storage/uploads')
    ],
    
    /**
     * Paramètres des sessions
     */
    'session' => [
        'lifetime' => env('SESSION_LIFETIME', 3600), // 1 heure par défaut
        'secure' => env('APP_ENV', 'production') !== 'development',
        'http_only' => true,
        'same_site' => 'lax'
    ],
    
    /**
     * Paramètres des tokens JWT
     */
    'jwt' => [
        'expiration' => env('JWT_EXPIRATION', 3600), // 1 heure par défaut
        'refresh_expiration' => env('JWT_REFRESH_EXPIRATION', 86400), // 24 heures par défaut
        'algorithm' => 'HS256',
        'issuer' => env('APP_URL', 'http://localhost:8080')
    ],
    
    /**
     * Paramètres CORS (Cross-Origin Resource Sharing)
     */
    'cors' => [
        'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '*')),
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
        'exposed_headers' => [],
        'max_age' => 86400, // 24 heures
        'supports_credentials' => false
    ],
    
    /**
     * Protection contre les attaques par force brute
     */
    'rate_limiting' => [
        'enabled' => true,
        'login' => [
            'max_attempts' => env('RATE_LIMIT_LOGIN_MAX', 5),
            'decay_minutes' => env('RATE_LIMIT_LOGIN_DECAY', 5)
        ],
        'api' => [
            'max_attempts' => env('RATE_LIMIT_API_MAX', 60),
            'decay_minutes' => env('RATE_LIMIT_API_DECAY', 1)
        ]
    ],
    
    /**
     * En-têtes de sécurité HTTP
     */
    'headers' => [
        'content-security-policy' => env('SECURITY_CONTENT_SECURITY_POLICY', "default-src 'self'; script-src 'self'; object-src 'none'; style-src 'self'; img-src 'self' data:; font-src 'self'; connect-src 'self'"),
        'x-frame-options' => 'SAMEORIGIN',
        'x-content-type-options' => 'nosniff',
        'x-xss-protection' => '1; mode=block',
        'strict-transport-security' => 'max-age=31536000; includeSubDomains'
    ],
    
    /**
     * Options de validation des mots de passe
     */
    'password' => [
        'min_length' => 8,
        'require_special_char' => true,
        'require_number' => true,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'hash_cost' => 12 // Coût du hachage bcrypt
    ]
]; 