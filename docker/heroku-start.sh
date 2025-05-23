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
    
    # Pour le débogage, définir APP_DEBUG à true
    sed -i "s|APP_DEBUG=.*|APP_DEBUG=true|" /var/www/html/backend/.env
    
    # Définir également les variables MongoDB pour éviter les erreurs
    sed -i "s|NOSQL_URI=.*|NOSQL_URI=mongodb://fake:fake@fake:27017/admin|" /var/www/html/backend/.env
    sed -i "s|MONGO_DATABASE=.*|MONGO_DATABASE=ecoride_nosql|" /var/www/html/backend/.env

    echo "Base de données MySQL configurée avec succès à partir de JAWSDB_URL"
    
    # Exécuter un script PHP pour créer la structure de la base de données
    echo "Exécution du script d'initialisation de la base de données..."
    
    # Extraire les composants de l'URL JAWSDB
    DB_USER=${BASH_REMATCH[1]}
    DB_PASS=${BASH_REMATCH[2]}
    DB_HOST=${BASH_REMATCH[3]}
    DB_PORT=${BASH_REMATCH[4]}
    DB_NAME=${BASH_REMATCH[5]}
    
    # Créer un script temporaire
    cat > /tmp/init-db.php <<EOF
<?php
try {
    // Se connecter à la base de données avec une connexion TCP explicite
    \$dsn = "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME";
    \$options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    \$pdo = new PDO(\$dsn, "$DB_USER", "$DB_PASS", \$options);
    
    // Créer la table users si elle n'existe pas
    \$pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    echo "Table 'users' créée avec succès.\n";
    echo "Base de données initialisée avec succès.\n";
} catch (PDOException \$e) {
    die("Erreur lors de l'initialisation de la base de données : " . \$e->getMessage() . "\n");
}
EOF

    # Exécuter le script
    php /tmp/init-db.php
  fi
fi

# Configuration pour servir les fichiers frontend
echo "Configuration du frontend..."

# Créer le répertoire web à la racine
mkdir -p /var/www/html/web

# Copier les fichiers frontend (pages, assets, etc.) vers le répertoire web
cp -r /var/www/html/frontend/* /var/www/html/web/

# Créer un fichier .htaccess pour la racine qui gère à la fois le frontend et l'API
cat > /var/www/html/backend/public/.htaccess <<'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Si la requête commence par /api, il s'agit d'une requête API
    RewriteCond %{REQUEST_URI} ^/api/.*
    RewriteRule ^ index.php [L]

    # Pour toutes les autres requêtes, vérifier si le fichier existe
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    # Vérifier si la requête se termine par .html
    RewriteCond %{REQUEST_URI} !\.html$
    # Vérifier si la requête correspond à un fichier .html sans l'extension
    RewriteCond %{DOCUMENT_ROOT}%{REQUEST_URI}.html -f
    # Rediriger vers la version avec .html
    RewriteRule ^(.*)$ $1.html [L]
    
    # Si toutes les conditions échouent et que c'est n'est ni un fichier ni un répertoire existant
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    # Rediriger vers index.html uniquement si toutes les tentatives précédentes ont échoué
    RewriteRule ^ /index.html [L]
</IfModule>
EOF

# Copier toutes les pages HTML du répertoire pages/public vers le répertoire public
echo "Copie des pages HTML..."
mkdir -p /var/www/html/backend/public/pages
cp -r /var/www/html/web/pages/public/* /var/www/html/backend/public/

# Corriger les chemins dans tous les fichiers HTML
echo "Correction des chemins relatifs dans les fichiers HTML..."
find /var/www/html/backend/public -name "*.html" -exec sed -i 's|../../assets|/assets|g' {} \;

# Créer un lien symbolique pour les assets
ln -sf /var/www/html/web/assets /var/www/html/backend/public/assets

# Modifier le fichier VirtualHost d'Apache pour servir à la fois le frontend et l'API
cat > /etc/apache2/sites-available/000-default.conf <<EOF
<VirtualHost *:${PORT}>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html/backend/public
    
    <Directory /var/www/html/backend/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

echo "Frontend configuré avec succès."

# Modifier le fichier index.php pour remplacer toutes les classes et dépendances non disponibles
echo "Modification du fichier index.php..."
cat > /var/www/html/backend/public/api_handler.php <<'EOF'
<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Définir le chemin de base
define('ROOT_PATH', realpath(__DIR__ . '/..'));

// Charger l'autoloader de Composer
require_once ROOT_PATH . '/vendor/autoload.php';

// Fonction helper pour éviter la redéclaration
if (!function_exists('env')) {
    function env($key, $default = null) {
        $value = getenv($key);
        if ($value === false) {
            // Lire depuis le fichier .env
            $envFile = ROOT_PATH . '/.env';
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos($line, '=') !== false) {
                        list($envKey, $envValue) = explode('=', $line, 2);
                        if (trim($envKey) === $key) {
                            $value = trim($envValue);
                            break;
                        }
                    }
                }
            }
        }
        return $value !== false ? $value : $default;
    }
}

// Inclure les routes
require_once ROOT_PATH . '/routes/api.php';
EOF

# S'assurer que le fichier de routes existe et contient notre code
echo "Création du fichier de routes personnalisé..."
cat > /var/www/html/backend/routes/api.php <<'EOF'
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
        
        // Utiliser directement l'URL JAWSDB pour la connexion à la base de données
        $jawsdb_url = getenv('JAWSDB_URL');
        if (!$jawsdb_url) {
            // Mode fallback si JAWSDB_URL n'est pas défini
            $dbHost = env('DB_HOST', 'localhost');
            $dbPort = env('DB_PORT', '3306');
            $dbName = env('DB_DATABASE', 'ecoride');
            $dbUser = env('DB_USERNAME', 'root');
            $dbPass = env('DB_PASSWORD', '');
            
            $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName";
            $pdo = new PDO($dsn, $dbUser, $dbPass);
        } else {
            // Extraire les composants de l'URL JAWSDB pour créer une connexion TCP explicite
            $regex = "/^mysql:\/\/([^:]+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/";
            if (preg_match($regex, $jawsdb_url, $matches)) {
                $dbUser = $matches[1];
                $dbPass = $matches[2];
                $dbHost = $matches[3];
                $dbPort = $matches[4];
                $dbName = $matches[5];
                
                $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
            } else {
                throw new Exception("Format d'URL JAWSDB invalide");
            }
        }
        
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
        
        // Utiliser directement l'URL JAWSDB pour la connexion à la base de données
        $jawsdb_url = getenv('JAWSDB_URL');
        if (!$jawsdb_url) {
            // Mode fallback si JAWSDB_URL n'est pas défini
            $dbHost = env('DB_HOST', 'localhost');
            $dbPort = env('DB_PORT', '3306');
            $dbName = env('DB_DATABASE', 'ecoride');
            $dbUser = env('DB_USERNAME', 'root');
            $dbPass = env('DB_PASSWORD', '');
            
            $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName";
            $pdo = new PDO($dsn, $dbUser, $dbPass);
        } else {
            // Extraire les composants de l'URL JAWSDB pour créer une connexion TCP explicite
            $regex = "/^mysql:\/\/([^:]+):([^@]+)@([^:]+):([0-9]+)\/(.+)$/";
            if (preg_match($regex, $jawsdb_url, $matches)) {
                $dbUser = $matches[1];
                $dbPass = $matches[2];
                $dbHost = $matches[3];
                $dbPort = $matches[4];
                $dbName = $matches[5];
                
                $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
            } else {
                throw new Exception("Format d'URL JAWSDB invalide");
            }
        }
        
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
    // Si la requête commence par /api/ mais n'est pas gérée, renvoyer une erreur 404
    if (strpos($requestUri, '/api/') === 0) {
        http_response_code(404);
        echo json_encode(['error' => true, 'message' => 'Route API non trouvée']);
        exit;
    }
    
    // Pour les autres requêtes, API en laisse le contrôle au fichier index.php principal
    exit;
}
EOF

# Créer un fichier index.php amélioré qui gère correctement les fichiers HTML
cat > /var/www/html/backend/public/index.php <<'EOF'
<?php
// Vérifier si la requête est une requête API
if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
    // Si c'est une requête API, inclure le gestionnaire d'API
    include_once 'api_handler.php';
    exit;
}

// Obtenir le chemin de la requête sans les paramètres de requête
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Normaliser le chemin
$request_path = ltrim($request_path, '/');
if (empty($request_path)) {
    $request_path = 'index.html';
}

// Vérifier si le fichier existe avec ou sans extension .html
$file_path = __DIR__ . '/' . $request_path;
$html_file_path = $file_path . (substr($file_path, -5) === '.html' ? '' : '.html');

if (file_exists($file_path) && !is_dir($file_path)) {
    // Si le fichier existe exactement comme demandé, le servir directement
    return false; // Laisse Apache gérer le fichier
} else if (file_exists($html_file_path) && !is_dir($html_file_path)) {
    // Si nous avons trouvé un fichier HTML correspondant, le servir
    include $html_file_path;
    exit;
} else {
    // Page non trouvée, vérifier si c'est une page spéciale comme "login" ou "register"
    $special_pages = [
        'login' => 'login.html',
        'register' => 'register.html',
        'profile' => 'profile.html',
        'covoiturages' => 'covoiturages.html',
        'contact' => 'contact.html'
    ];
    
    // Vérifier si le chemin de requête correspond à l'une de nos pages spéciales
    $base_path = explode('/', $request_path)[0];
    if (array_key_exists($base_path, $special_pages) && file_exists(__DIR__ . '/' . $special_pages[$base_path])) {
        include __DIR__ . '/' . $special_pages[$base_path];
        exit;
    }
    
    // Si rien ne correspond, servir la page d'accueil
    include __DIR__ . '/index.html';
    exit;
}
EOF

# Désactiver tous les modules Apache MPM puis activer uniquement mpm_prefork
a2dismod mpm_event
a2dismod mpm_worker
a2enmod mpm_prefork
a2enmod rewrite

# Démarrer Apache en avant-plan
apache2-foreground 