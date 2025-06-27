<?php
/**
 * Script de test de connexion à la base de données
 */

echo "🔧 Test de connexion depuis l'application PHP...\n";

// Chargement de l'autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Chargement des variables d'environnement
$envFiles = [
    '/var/www/html/.env',
    dirname(__DIR__) . '/.env',
    dirname(dirname(__DIR__)) . '/.env'
];

foreach ($envFiles as $envFile) {
    if (file_exists($envFile)) {
        echo "📄 Fichier .env trouvé : $envFile\n";
        $dotenv = new \App\Core\DotEnv($envFile);
        $dotenv->load();
        break;
    } else {
        echo "❌ Fichier .env non trouvé : $envFile\n";
    }
}

// Test des variables d'environnement
echo "\n🔍 Variables d'environnement :\n";
echo "- JAWSDB_URL: " . (getenv('JAWSDB_URL') ? 'définie (' . substr(getenv('JAWSDB_URL'), 0, 30) . '...)' : 'non définie') . "\n";
echo "- DATABASE_URL: " . (getenv('DATABASE_URL') ? 'définie' : 'non définie') . "\n";
echo "- DB_HOST: " . (getenv('DB_HOST') ?: 'non définie') . "\n";
echo "- DB_DATABASE: " . (getenv('DB_DATABASE') ?: 'non définie') . "\n";
echo "- DB_USERNAME: " . (getenv('DB_USERNAME') ?: 'non définie') . "\n";

// Test de la classe Database
echo "\n🔧 Test de la classe Database :\n";
try {
    $database = new \App\Core\Database();
    $connection = $database->getMysqlConnection();
    echo "✅ Connexion réussie !\n";
    
    // Test d'une requête simple
    $stmt = $connection->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✅ Requête test réussie - Nombre d'utilisateurs : " . $result['count'] . "\n";
    
} catch (Exception $e) {
    echo "❌ Erreur de connexion : " . $e->getMessage() . "\n";
    echo "📋 Trace : " . $e->getTraceAsString() . "\n";
}

echo "\n🎉 Test terminé\n";
?> 