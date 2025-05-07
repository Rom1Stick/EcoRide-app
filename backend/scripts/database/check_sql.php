<?php
/**
 * Script de vérification de la connexion MySQL
 * Exécuter avec: docker-compose run tests php scripts/database/check_sql.php
 */

echo "== Script de vérification MySQL ==\n\n";

try {
    // Configuration de la connexion
    $host = getenv('DB_HOST') ?: 'mysql';
    $port = getenv('DB_PORT') ?: '3306';
    $dbname = getenv('DB_DATABASE') ?: 'ecoride';
    $username = getenv('DB_USERNAME') ?: 'ecorider';
    $password = getenv('DB_PASSWORD') ?: 'securepass';

    echo "Connexion à MySQL...\n";
    echo "Host: $host:$port\n";
    echo "Database: $dbname\n";
    
    // Connexion à MySQL avec PDO
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo "✅ Connexion à MySQL réussie!\n\n";
    
    // Vérification du schéma
    echo "Vérification des tables dans la base de données...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "Tables trouvées: " . implode(', ', $tables) . "\n";
        echo "✅ Base de données contient des tables\n\n";
    } else {
        echo "❌ Aucune table trouvée dans la base de données\n\n";
    }
    
    // Test de lecture simple
    echo "Test de requête SQL...\n";
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    
    if ($result && $result['test'] == 1) {
        echo "✅ Test de requête SQL réussi\n";
    } else {
        echo "❌ Échec du test de requête SQL\n";
    }
    
    echo "\n✅ Tous les tests MySQL sont réussis!\n";
    
} catch (\PDOException $e) {
    echo "❌ Erreur de connexion MySQL: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
    exit(1);
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
} 