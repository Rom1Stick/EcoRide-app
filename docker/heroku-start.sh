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
    RewriteRule ^(.*)$ index.php/$1 [L]
</IfModule>
EOF

# Démarrer Apache en premier plan
exec apache2-foreground 