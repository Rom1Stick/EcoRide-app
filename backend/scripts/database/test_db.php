<?php
// Ce script teste les connexions aux bases de données MySQL et MongoDB

// Charger les variables d'environnement depuis .env si disponible
if (file_exists(__DIR__ . '/../../.env')) {
    $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

// Définir le chemin de base
define('BASE_PATH', __DIR__ . '/../..');

// Récupérer les variables d'environnement
$dbHost = $_ENV['DB_HOST'] ?? 'mysql';
$dbPort = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_DATABASE'] ?? 'ecoride';
$dbUser = $_ENV['DB_USERNAME'] ?? 'ecorider';
$dbPass = $_ENV['DB_PASSWORD'] ?? 'securepass';

// Variables pour MongoDB
$mongoHost = $_ENV['MONGO_HOST'] ?? 'mongodb';
$mongoPort = $_ENV['MONGO_PORT'] ?? '27017';
$mongoUsername = $_ENV['MONGO_USERNAME'] ?? 'mongo';
$mongoPassword = $_ENV['MONGO_PASSWORD'] ?? 'changeme';
$mongoDbName = $_ENV['MONGO_DATABASE'] ?? 'ecoride_nosql';

// Inclure l'autoloader de Composer si disponible
$autoloadPath = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require $autoloadPath;
}

echo "🔍 Test des connexions aux bases de données\n";
echo "==========================================\n\n";

// Test MySQL
echo "📊 Test de la connexion MySQL...\n";
try {
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✅ MySQL : Connexion réussie\n";
    
    // Tester la structure de la base (vérifier si une table existe)
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "📋 Tables MySQL existantes : " . implode(', ', $tables) . "\n";
    } catch (Exception $e) {
        echo "⚠️ Impossible de lister les tables : " . $e->getMessage() . "\n";
    }
    
    // Tester CRUD
    echo "\n🔄 Test des opérations CRUD MySQL...\n";
    
    // Création d'une table de test si elle n'existe pas
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS test_table (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo "✅ Création de table : OK\n";
    } catch (Exception $e) {
        echo "❌ Création de table : ERREUR - " . $e->getMessage() . "\n";
    }
    
    // Test Create
    try {
        $stmt = $pdo->prepare("INSERT INTO test_table (name) VALUES (?)");
        $stmt->execute(['Test item ' . date('Y-m-d H:i:s')]);
        $lastId = $pdo->lastInsertId();
        echo "✅ Insertion (Create) : OK (ID: $lastId)\n";
    } catch (Exception $e) {
        echo "❌ Insertion (Create) : ERREUR - " . $e->getMessage() . "\n";
    }
    
    // Test Read
    try {
        $stmt = $pdo->query("SELECT * FROM test_table ORDER BY id DESC LIMIT 1");
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Lecture (Read) : OK - Dernier enregistrement : " . json_encode($record) . "\n";
    } catch (Exception $e) {
        echo "❌ Lecture (Read) : ERREUR - " . $e->getMessage() . "\n";
    }
    
    // Test Update
    try {
        $stmt = $pdo->prepare("UPDATE test_table SET name = ? WHERE id = ?");
        $stmt->execute(['Updated: ' . date('Y-m-d H:i:s'), $lastId]);
        echo "✅ Mise à jour (Update) : OK\n";
    } catch (Exception $e) {
        echo "❌ Mise à jour (Update) : ERREUR - " . $e->getMessage() . "\n";
    }
    
    // Vérifier l'update
    try {
        $stmt = $pdo->prepare("SELECT * FROM test_table WHERE id = ?");
        $stmt->execute([$lastId]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Vérification après Update : " . json_encode($record) . "\n";
    } catch (Exception $e) {
        echo "❌ Vérification après Update : ERREUR - " . $e->getMessage() . "\n";
    }
    
    // Test Delete
    try {
        $stmt = $pdo->prepare("DELETE FROM test_table WHERE id = ?");
        $stmt->execute([$lastId]);
        echo "✅ Suppression (Delete) : OK\n";
    } catch (Exception $e) {
        echo "❌ Suppression (Delete) : ERREUR - " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ MySQL : Erreur de connexion : " . $e->getMessage() . "\n";
}

// Vérifier si l'extension MongoDB est installée
echo "\n📊 Test de la connexion MongoDB...\n";
if (!extension_loaded('mongodb')) {
    echo "❌ L'extension MongoDB n'est pas installée ou activée.\n";
    echo "   Pour l'installer : pecl install mongodb && docker-php-ext-enable mongodb\n";
} else {
    try {
        // Vérifier si la classe MongoDB\Client existe (package mongodb/mongodb installé)
        if (!class_exists('MongoDB\Client')) {
            echo "⚠️ La librairie MongoDB n'est pas installée. Essai avec MongoClient natif...\n";
            // Essayer avec l'extension MongoDB native
            $mongoUri = "mongodb://{$mongoUsername}:{$mongoPassword}@{$mongoHost}:{$mongoPort}";
            $mongoClient = new MongoDB\Driver\Manager($mongoUri);
            echo "✅ MongoDB : Connexion réussie (extension native)\n";
            
            // Tester une commande simple
            $command = new MongoDB\Driver\Command(['ping' => 1]);
            $result = $mongoClient->executeCommand('admin', $command);
            echo "✅ MongoDB : Commande ping réussie\n";
            
            echo "⚠️ Pour les tests CRUD complets, veuillez installer la librairie : composer require mongodb/mongodb\n";
        } else {
            // Utiliser la librairie mongodb/mongodb
            $mongoUri = "mongodb://{$mongoUsername}:{$mongoPassword}@{$mongoHost}:{$mongoPort}";
            $client = new MongoDB\Client($mongoUri);
            echo "✅ MongoDB : Connexion réussie\n";
            
            // Tester CRUD
            echo "\n🔄 Test des opérations CRUD MongoDB...\n";
            
            $db = $client->selectDatabase($mongoDbName);
            $collection = $db->selectCollection('test_collection');
            
            // Test Create
            try {
                $result = $collection->insertOne([
                    'name' => 'Test document ' . date('Y-m-d H:i:s'),
                    'timestamp' => new MongoDB\BSON\UTCDateTime(time() * 1000)
                ]);
                $id = $result->getInsertedId();
                echo "✅ Insertion (Create) : OK (ID: $id)\n";
            } catch (Exception $e) {
                echo "❌ Insertion (Create) : ERREUR - " . $e->getMessage() . "\n";
            }
            
            // Test Read
            try {
                $document = $collection->findOne(['_id' => $id]);
                echo "✅ Lecture (Read) : OK - Document : " . json_encode($document) . "\n";
            } catch (Exception $e) {
                echo "❌ Lecture (Read) : ERREUR - " . $e->getMessage() . "\n";
            }
            
            // Test Update
            try {
                $result = $collection->updateOne(
                    ['_id' => $id],
                    ['$set' => ['name' => 'Updated: ' . date('Y-m-d H:i:s')]]
                );
                echo "✅ Mise à jour (Update) : OK (Modifié: " . $result->getModifiedCount() . ")\n";
            } catch (Exception $e) {
                echo "❌ Mise à jour (Update) : ERREUR - " . $e->getMessage() . "\n";
            }
            
            // Vérifier l'update
            try {
                $document = $collection->findOne(['_id' => $id]);
                echo "✅ Vérification après Update : " . json_encode($document) . "\n";
            } catch (Exception $e) {
                echo "❌ Vérification après Update : ERREUR - " . $e->getMessage() . "\n";
            }
            
            // Test Delete
            try {
                $result = $collection->deleteOne(['_id' => $id]);
                echo "✅ Suppression (Delete) : OK (Supprimé: " . $result->getDeletedCount() . ")\n";
            } catch (Exception $e) {
                echo "❌ Suppression (Delete) : ERREUR - " . $e->getMessage() . "\n";
            }
        }
    } catch (Exception $e) {
        echo "❌ MongoDB : Erreur de connexion : " . $e->getMessage() . "\n";
    }
}

echo "\n✨ Tests terminés ✨\n"; 