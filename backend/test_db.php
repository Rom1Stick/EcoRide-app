<?php
// Charger les variables d'environnement depuis .env si disponible
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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
define('BASE_PATH', __DIR__);

// Récupérer les variables d'environnement
$dbHost = $_ENV['DB_HOST'] ?? 'mysql';
$dbPort = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_DATABASE'] ?? 'ecoride';
$dbUser = $_ENV['DB_USERNAME'] ?? 'ecorider';
$dbPass = $_ENV['DB_PASSWORD'] ?? 'securepass';

// Inclure l'autoloader de Composer si disponible
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require $autoloadPath;
}

echo "📝 Test des connexions aux bases de données\n";
echo "==========================================\n\n";

// Test MySQL
echo "🔌 Test de la connexion MySQL...\n";
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

echo "\n✨ Tests terminés ✨\n"; 