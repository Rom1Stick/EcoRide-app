# Documentation des connexions aux bases de données 🗄️

## Configuration des bases de données

EcoRide utilise deux systèmes de base de données :

1. **MySQL** : Pour toutes les données relationnelles (utilisateurs, trajets, réservations, logs, etc.)
2. **MongoDB** : Pour les données non-relationnelles (documents, statistiques avancées, etc.)

## Chaînes de connexion

### MySQL

```php
// Connexion MySQL PDO
$host = 'mysql';  // Nom du service dans docker-compose
$port = '3306';   // Port standard de MySQL
$database = 'ecoride';
$username = 'ecorider';
$password = 'securepass';

$dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";

$pdo = new PDO(
    $dsn,
    $username,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);
```

### MongoDB

```php
// Connexion MongoDB
require 'vendor/autoload.php'; // Inclure l'autoloader de Composer

$mongoUri = 'mongodb://mongo:changeme@mongodb:27017/ecoride_nosql';
$client = new MongoDB\Client($mongoUri);
$database = $client->selectDatabase('ecoride_nosql');
$collection = $database->selectCollection('rides');
```

## Variables d'environnement

Ces valeurs peuvent être configurées via les variables d'environnement suivantes dans le fichier `.env` :

```dotenv
# MySQL
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=ecoride
DB_USERNAME=ecorider
DB_PASSWORD=securepass

# MongoDB
NOSQL_URI=mongodb://mongo:changeme@mongodb:27017/ecoride_nosql
```

## Script de test de connexion

Voici un script PHP pour tester les connexions aux deux bases de données :

```php
<?php
// test_db.php
require 'vendor/autoload.php';

// Récupérer les variables d'environnement
$dbHost = $_ENV['DB_HOST'] ?? 'mysql';
$dbPort = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_DATABASE'] ?? 'ecoride';
$dbUser = $_ENV['DB_USERNAME'] ?? 'ecorider';
$dbPass = $_ENV['DB_PASSWORD'] ?? 'securepass';
$mongoUri = $_ENV['NOSQL_URI'] ?? 'mongodb://mongo:changeme@mongodb:27017/ecoride_nosql';

// Test MySQL
try {
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✅ MySQL : Connexion réussie\n";
    
    // Tester une requête simple
    $stmt = $pdo->query('SELECT 1');
    echo "✅ MySQL : Requête test réussie\n";
} catch (Exception $e) {
    echo "❌ MySQL : Erreur de connexion : " . $e->getMessage() . "\n";
}

// Test MongoDB
try {
    $client = new MongoDB\Client($mongoUri);
    echo "✅ MongoDB : Connexion réussie\n";
    
    // Tester une requête simple
    $db = $client->selectDatabase('ecoride_nosql');
    $result = $db->command(['ping' => 1]);
    echo "✅ MongoDB : Requête test réussie\n";
} catch (Exception $e) {
    echo "❌ MongoDB : Erreur de connexion : " . $e->getMessage() . "\n";
}
```

## Prérequis PHP

Pour utiliser ces connexions, les extensions PHP suivantes sont nécessaires :

### Pour MySQL
- `pdo`
- `pdo_mysql`

### Pour MongoDB
- `mongodb`

Ces extensions sont déjà installées dans l'image Docker du backend. Si vous exécutez l'application en dehors de Docker, vous devrez installer ces extensions :

```bash
# Pour MySQL
apt-get update && apt-get install -y php-mysql

# Pour MongoDB
pecl install mongodb
docker-php-ext-enable mongodb
```

## Ports exposés

Les services de base de données ont les ports suivants exposés :

- **MySQL** : Port 3306 (non exposé en dehors de Docker par défaut)
- **MongoDB** : Port 27017 (exposé sur localhost:27017)
- **phpMyAdmin** : Port 8081 (http://localhost:8081)
- **mongo-express** : Port 8082 (http://localhost:8082)

## Volumes Docker

Les données sont persistantes grâce aux volumes Docker suivants :

- **MySQL** : Volume `db_data` monté sur `/var/lib/mysql`
- **MongoDB** : Volume `mongo_data` monté sur `/data/db`

## Accès en ligne de commande

### MySQL

```bash
# Se connecter à MySQL depuis le conteneur MySQL
docker exec -it mysql mysql -u ecorider -p

# Ou depuis le conteneur backend
docker exec -it backend bash -c 'mysql -h mysql -u ecorider -p'
```

### MongoDB

```bash
# Se connecter à MongoDB depuis le conteneur MongoDB
docker exec -it mongodb mongosh -u mongo -p changeme

# Exécuter une commande MongoDB
docker exec -it mongodb mongosh -u mongo -p changeme --eval "db.adminCommand('listDatabases')"
``` 