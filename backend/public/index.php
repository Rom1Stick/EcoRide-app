<?php

/**
 * Point d'entrée principal de l'API EcoRide
 * 
 * Ce fichier initialise l'application, charge les dépendances,
 * configure l'environnement et gère les requêtes entrantes.
 */

// Définir le chemin de base
define('BASE_PATH', dirname(__DIR__));

// Charger l'autoloader de Composer
require BASE_PATH . '/vendor/autoload.php';

// Charger les variables d'environnement
$dotenv = new \App\Core\DotEnv(BASE_PATH . '/.env');
$dotenv->load();

// Configurer les erreurs en fonction de l'environnement
if (env('APP_DEBUG', false) === true) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

// Configurer le fuseau horaire par défaut
date_default_timezone_set(env('APP_TIMEZONE', 'Europe/Paris'));

// Initialiser l'application
$app = new \App\Core\Application();

// Charger les routes de l'API
require BASE_PATH . '/routes/api.php';

// Gérer la requête entrante
$app->run(); 