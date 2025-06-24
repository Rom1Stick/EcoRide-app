<?php

/**
 * Bootstrap configuration pour les tests sécurisés
 * Initialise l'environnement de base sans modification de données
 */

// Définir les constantes de base
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', ROOT_PATH);
}

if (!defined('APP_PATH')) {
    define('APP_PATH', ROOT_PATH . '/app');
}

if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', ROOT_PATH . '/config');
}

// Configuration de l'environnement
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Timezone par défaut
date_default_timezone_set('Europe/Paris');

// Configuration de base pour la DB
$_ENV['DB_HOST'] = $_ENV['DB_HOST'] ?? 'localhost';
$_ENV['DB_NAME'] = $_ENV['DB_NAME'] ?? 'ecoride';
$_ENV['DB_USER'] = $_ENV['DB_USER'] ?? 'root';
$_ENV['DB_PASS'] = $_ENV['DB_PASS'] ?? '';

// Configuration sécurisé pour les tests
$_ENV['SAFE_MODE'] = true;
$_ENV['READ_ONLY_MODE'] = true;

echo "🛡️ Bootstrap configuré en mode sécurisé\n"; 