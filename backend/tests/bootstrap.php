<?php

/**
 * Fichier d'initialisation pour les tests
 * Chargé avant l'exécution des tests
 */

// Chargement de l'autoloader
$autoloaderPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloaderPath)) {
    require_once $autoloaderPath;
} else {
    die("L'autoloader Composer n'a pas été trouvé. Veuillez exécuter 'composer install'.\n");
}

// Définition des constantes
define('APP_ROOT', dirname(__DIR__));
define('APP_ENV', 'testing');

// Chargement des variables d'environnement pour les tests
$envPath = APP_ROOT . '/.env.testing';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorer les commentaires
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Extraire la variable et sa valeur
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        // Supprimer les guillemets si présents
        if (preg_match('/^(["\']).*\1$/', $value)) {
            $value = substr($value, 1, -1);
        }
        
        // Définir la variable d'environnement
        putenv("$name=$value");
    }
} else {
    // Valeurs par défaut pour les tests si le fichier .env.testing n'existe pas
    putenv('DB_HOST=localhost');
    putenv('DB_TEST_DATABASE=ecoride_test');
    putenv('DB_TEST_USERNAME=ecoride_test');
    putenv('DB_TEST_PASSWORD=password');
}

// Initialisation de l'application pour les tests
require_once APP_ROOT . '/app/Core/Application.php';

// Configuration spécifique pour les tests
\App\Core\Application::$env = 'testing';

// Informations pour le débogage des tests
echo "=== Configuration de test ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Environment: " . APP_ENV . "\n";
echo "Database: " . getenv('DB_TEST_DATABASE') . "@" . getenv('DB_HOST') . "\n";
echo "========================\n\n"; 