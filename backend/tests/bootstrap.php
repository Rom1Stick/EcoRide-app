<?php
/**
 * Bootstrap pour tests PHPUnit
 */

// Chargement de l'autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Configuration des variables d'environnement pour les tests
$_ENV['APP_ENV'] = 'testing';
$_ENV['APP_DEBUG'] = 'true';

// Configuration MySQL
$_ENV['DB_CONNECTION'] = 'mysql';
$_ENV['DB_HOST'] = 'mysql';
$_ENV['DB_PORT'] = '3306';
$_ENV['DB_DATABASE'] = 'ecoride_test';
$_ENV['DB_USERNAME'] = 'ecorider';
$_ENV['DB_PASSWORD'] = 'securepass';

// Configuration MongoDB
$_ENV['MONGO_HOST'] = 'mongodb';
$_ENV['MONGO_PORT'] = '27017';
$_ENV['MONGO_USERNAME'] = 'mongo';
$_ENV['MONGO_PASSWORD'] = 'changeme';
$_ENV['MONGO_DATABASE'] = 'ecoride_test';

// Définition des constantes pour les tests
if (!defined('TEST_ROOT')) {
    define('TEST_ROOT', __DIR__);
}

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Chargement des fonctions utilitaires pour les tests
require_once __DIR__ . '/TestUtils.php';

// Fonction d'autoloading pour les mocks et les stubs
spl_autoload_register(function ($class) {
    // Charger les mocks et stubs dans le dossier tests/Mocks
    if (strpos($class, 'Tests\\Mocks\\') === 0) {
        $path = str_replace('\\', '/', substr($class, 12));
        $file = TEST_ROOT . '/Mocks/' . $path . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    return false;
}); 