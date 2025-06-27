<?php

/**
 * Point d'entrée principal de l'API EcoRide
 * 
 * Ce fichier initialise l'application, charge les dépendances,
 * configure l'environnement et gère les requêtes entrantes.
 */

// Démarrer la session pour CSRF
session_start();

// Définir le chemin de base
define('BASE_PATH', dirname(__DIR__));

// Charger l'autoloader de Composer
require BASE_PATH . '/vendor/autoload.php';

// Charger les variables d'environnement depuis .env si disponible
$envFiles = [
    '/var/www/html/.env',           // Fichier généré par le script de démarrage
    BASE_PATH . '/.env',            // Fichier .env du backend
    dirname(BASE_PATH) . '/.env'    // Fichier .env à la racine du projet
];

foreach ($envFiles as $envFile) {
    if (file_exists($envFile)) {
        $dotenv = new \App\Core\DotEnv($envFile);
        $dotenv->load();
        break; // Utiliser le premier fichier trouvé
    }
}

// En-têtes HTTP de sécurité
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer-when-downgrade');
header('Permissions-Policy: geolocation=(), microphone=()');
header("Content-Security-Policy: default-src 'self';");

// Configurer les erreurs en fonction de l'environnement
// Utiliser getenv() directement car env() nécessite l'autoload qui est déjà fait
$appDebug = getenv('APP_DEBUG') === 'true' || $_ENV['APP_DEBUG'] ?? false;
if ($appDebug) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

// Configurer le fuseau horaire par défaut
$timezone = getenv('APP_TIMEZONE') ?: $_ENV['APP_TIMEZONE'] ?? 'Europe/Paris';
date_default_timezone_set($timezone);

// Initialiser l'application
$app = new \App\Core\Application();

// Charger les routes de l'API
require BASE_PATH . '/routes/api.php';

// Générer et exposer le token CSRF via cookie lisible par JS
$csrfToken = \App\Core\Security::generateCsrfToken();
// En dev, on n'impose pas Secure pour le cookie CSRF
setcookie('XSRF-TOKEN', $csrfToken, 0, '/', '', false, false);

// Gérer la requête entrante
$app->run(); 