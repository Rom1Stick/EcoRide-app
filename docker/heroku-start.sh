#!/bin/bash
set -e

# Afficher le contenu initial du fichier .env
echo "Contenu du fichier .env avant modifications :"
cat /var/www/html/backend/.env

# Récupérer les variables d'environnement de Heroku et configurer le fichier .env
if [ -n "$JAWSDB_URL" ]; then
  # Format de JAWSDB_URL: mysql://username:password@hostname:port/database_name
  echo "Configuration de la base de données à partir de JAWSDB_URL: $JAWSDB_URL"
  regex="^mysql://([^:]+):([^@]+)@([^:]+):([0-9]+)/(.+)$"
  if [[ $JAWSDB_URL =~ $regex ]]; then
    echo "Correspondance trouvée pour JAWSDB_URL, mise à jour des paramètres DB_*"
    sed -i "s|DB_CONNECTION=.*|DB_CONNECTION=mysql|" /var/www/html/backend/.env
    sed -i "s|DB_HOST=.*|DB_HOST=${BASH_REMATCH[3]}|" /var/www/html/backend/.env
    sed -i "s|DB_PORT=.*|DB_PORT=${BASH_REMATCH[4]}|" /var/www/html/backend/.env
    sed -i "s|DB_DATABASE=.*|DB_DATABASE=${BASH_REMATCH[5]}|" /var/www/html/backend/.env
    sed -i "s|DB_USERNAME=.*|DB_USERNAME=${BASH_REMATCH[1]}|" /var/www/html/backend/.env
    sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${BASH_REMATCH[2]}|" /var/www/html/backend/.env
    
    # Activer le mode debug pour voir les erreurs
    sed -i "s|APP_DEBUG=.*|APP_DEBUG=true|" /var/www/html/backend/.env
    
    # Ajouter les variables MongoDB (factices pour éviter les erreurs)
    echo "MONGO_HOST=localhost" >> /var/www/html/backend/.env
    echo "MONGO_PORT=27017" >> /var/www/html/backend/.env
    echo "MONGO_DATABASE=ecoride" >> /var/www/html/backend/.env
    echo "MONGO_USERNAME=" >> /var/www/html/backend/.env
    echo "MONGO_PASSWORD=" >> /var/www/html/backend/.env
    
    echo "Base de données MySQL configurée avec succès à partir de JAWSDB_URL"
    
    # Créer un script PHP temporaire pour initialiser la base de données
    echo "Exécution du script d'initialisation de la base de données..."
    cat > /tmp/init_db.php << 'EOF'
<?php
try {
    // Utiliser directement la variable d'environnement JAWSDB_URL
    $jawsdb_url = getenv('JAWSDB_URL');
    if (!$jawsdb_url) {
        throw new Exception("JAWSDB_URL n'est pas définie");
    }
    
    // Analyser l'URL pour créer un DSN PDO
    $url = parse_url($jawsdb_url);
    $host = $url["host"];
    $username = $url["user"];
    $password = $url["pass"];
    $database = substr($url["path"], 1);
    $port = $url["port"];
    
    // Créer la connexion PDO
    $dsn = "mysql:host=$host;port=$port;dbname=$database";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Créer la table des utilisateurs si elle n'existe pas
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    echo "Table 'users' créée avec succès.\n";
    
    // Créer d'autres tables nécessaires ici si besoin
    
    echo "Base de données initialisée avec succès.\n";
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}
EOF
    
    php /tmp/init_db.php
  else
    echo "Format de JAWSDB_URL non reconnu: $JAWSDB_URL"
    exit 1
  fi
else
  echo "Variable JAWSDB_URL non définie, utilisation des paramètres par défaut"
fi

# Créer des classes factices pour MongoDB pour éviter les erreurs
mkdir -p /var/www/html/backend/src/DataAccess/NoSql/Mock
cat > /var/www/html/backend/src/DataAccess/NoSql/Mock/MongoConnectionMock.php << 'EOF'
<?php
namespace App\DataAccess\NoSql;

class MongoConnection {
    public static function getInstance() {
        return new self();
    }
    
    public function getDatabase() {
        return new MockDatabase();
    }
}

class MockDatabase {
    public function __call($name, $arguments) {
        return new MockCollection();
    }
}

class MockCollection {
    public function insertOne($document) {
        return (object)['insertedId' => new \MongoDB\BSON\ObjectId()];
    }
    
    public function findOne($filter) {
        return null;
    }
    
    public function find($filter = []) {
        return [];
    }
    
    public function __call($name, $arguments) {
        return null;
    }
}
EOF

# Modifier directement le fichier index.php pour utiliser les helpers existants
echo "Modification du fichier index.php..."
cat > /var/www/html/backend/public/index.php << 'EOF'
<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Définir le chemin de base pour l'application
define('BASE_PATH', dirname(__DIR__));

// Charger l'autoloader
require_once BASE_PATH . '/vendor/autoload.php';

// Charger les helpers
require_once BASE_PATH . '/app/Core/helpers.php';

// Configuration de base
date_default_timezone_set('Europe/Paris');

// Inclure les routes API directement
require_once BASE_PATH . '/routes/api.php';
EOF

# Vérifier si le dossier routes existe
if [ ! -d "/var/www/html/backend/routes" ]; then
    echo "Création du dossier routes manquant..."
    mkdir -p /var/www/html/backend/routes
fi

# Vérifier si le fichier api.php existe
if [ ! -f "/var/www/html/backend/routes/api.php" ]; then
    echo "Création du fichier api.php manquant..."
    cat > /var/www/html/backend/routes/api.php << 'EOF'
<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fichier de routes API simplifié sans routeur
$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Répondre avec un statut 200 OK pour les requêtes OPTIONS (CORS)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Définir le header de réponse comme JSON par défaut
header('Content-Type: application/json');

// Route d'inscription simplifiée
if ($requestUri === '/api/auth/register' && $method === 'POST') {
    try {
        // Récupérer les données de la requête
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Valider les données
        if (!isset($data['name']) || !isset($data['email']) || !isset($data['password']) || !isset($data['password_confirmation'])) {
            http_response_code(400);
            echo json_encode(['error' => true, 'message' => 'Données incomplètes']);
            exit;
        }
        
        if ($data['password'] !== $data['password_confirmation']) {
            http_response_code(400);
            echo json_encode(['error' => true, 'message' => 'Les mots de passe ne correspondent pas']);
            exit;
        }
        
        // Connexion à la base de données MySQL
        $dbHost = env('DB_HOST', 'localhost');
        $dbPort = env('DB_PORT', '3306');
        $dbName = env('DB_DATABASE', 'ecoride');
        $dbUser = env('DB_USERNAME', 'root');
        $dbPass = env('DB_PASSWORD', '');
        
        $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName";
        $pdo = new PDO($dsn, $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Vérifier si l'email existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetchColumn()) {
            http_response_code(400);
            echo json_encode(['error' => true, 'message' => 'Cet email est déjà utilisé']);
            exit;
        }
        
        // Hasher le mot de passe
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Insérer l'utilisateur
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$data['name'], $data['email'], $hashedPassword]);
        
        // Générer un token JWT factice
        $token = bin2hex(random_bytes(32));
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Inscription réussie',
            'user' => [
                'id' => $pdo->lastInsertId(),
                'name' => $data['name'],
                'email' => $data['email']
            ],
            'token' => $token
        ]);
    } catch (Exception $e) {
        // Journaliser l'erreur
        error_log("Erreur d'inscription: " . $e->getMessage());
        
        // Renvoyer une réponse d'erreur détaillée en mode DEBUG
        if (env('APP_DEBUG', false) === true) {
            http_response_code(500);
            echo json_encode([
                'error' => true, 
                'message' => 'Erreur interne lors de l\'inscription', 
                'debug' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => true, 'message' => 'Erreur interne lors de l\'inscription']);
        }
    }
    exit;
} elseif (preg_match('/^\/api\/auth\/login(\/)?$/', $requestUri) && $method === 'POST') {
    // Route de connexion simplifiée
    try {
        // Récupérer les données de la requête
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Valider les données
        if (!isset($data['email']) || !isset($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => true, 'message' => 'Données incomplètes']);
            exit;
        }
        
        // Connexion à la base de données MySQL
        $dbHost = env('DB_HOST', 'localhost');
        $dbPort = env('DB_PORT', '3306');
        $dbName = env('DB_DATABASE', 'ecoride');
        $dbUser = env('DB_USERNAME', 'root');
        $dbPass = env('DB_PASSWORD', '');
        
        $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName";
        $pdo = new PDO($dsn, $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Vérifier si l'utilisateur existe
        $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($data['password'], $user['password'])) {
            http_response_code(401);
            echo json_encode(['error' => true, 'message' => 'Identifiants invalides']);
            exit;
        }
        
        // Générer un token JWT factice
        $token = bin2hex(random_bytes(32));
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Connexion réussie',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email']
            ],
            'token' => $token
        ]);
    } catch (Exception $e) {
        // Journaliser l'erreur
        error_log("Erreur de connexion: " . $e->getMessage());
        
        // Renvoyer une réponse d'erreur détaillée en mode DEBUG
        if (env('APP_DEBUG', false) === true) {
            http_response_code(500);
            echo json_encode([
                'error' => true, 
                'message' => 'Erreur interne lors de la connexion', 
                'debug' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => true, 'message' => 'Erreur interne lors de la connexion']);
        }
    }
    exit;
} else {
    // Route par défaut pour les autres endpoints
    http_response_code(404);
    echo json_encode(['error' => true, 'message' => 'Route non trouvée']);
    exit;
}
EOF
fi

# Configuration d'Apache
a2dismod mpm_event
a2dismod mpm_worker
a2enmod mpm_prefork
a2enmod rewrite

# Créer un .htaccess pour permettre aux routes de fonctionner correctement
cat > /var/www/html/.htaccess << 'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Rediriger les requêtes API vers le backend
    RewriteCond %{REQUEST_URI} ^/api/.*
    RewriteRule ^api/(.*)$ /backend/public/api/$1 [L]
    
    # Autoriser les types MIME corrects pour JS et CSS
    <FilesMatch "\.js$">
        ForceType application/javascript
    </FilesMatch>
    
    <FilesMatch "\.css$">
        ForceType text/css
    </FilesMatch>
</IfModule>
EOF

# Créer un .htaccess pour le dossier backend/public
mkdir -p /var/www/html/backend/public
cat > /var/www/html/backend/public/.htaccess << 'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [L,QSA]
</IfModule>
EOF

# Démarrer Apache en premier plan
exec apache2-foreground 