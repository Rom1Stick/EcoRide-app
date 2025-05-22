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
    echo "Base de données MySQL configurée avec succès à partir de JAWSDB_URL"
    
    # Initialisation de la base de données
    DB_HOST=${BASH_REMATCH[3]}
    DB_PORT=${BASH_REMATCH[4]}
    DB_DATABASE=${BASH_REMATCH[5]}
    DB_USERNAME=${BASH_REMATCH[1]}
    DB_PASSWORD=${BASH_REMATCH[2]}
    
    # Créer un fichier PHP pour initialiser la base de données
    cat > /tmp/init_db.php << 'EOL'
<?php
// Récupérer les variables d'environnement
$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$database = getenv('DB_DATABASE');
$username = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');

// Connexion à la base de données
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$database";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connexion à la base de données réussie!\n";
    
    // Créer les tables nécessaires au fonctionnement de base
    // Adresse
    $pdo->exec('CREATE TABLE IF NOT EXISTS Adresse (
        adresse_id INT AUTO_INCREMENT PRIMARY KEY,
        rue VARCHAR(100) NOT NULL,
        ville VARCHAR(50) NOT NULL,
        code_postal VARCHAR(10) NOT NULL,
        pays VARCHAR(50) DEFAULT "France",
        coordonnees_gps VARCHAR(50)
    )');
    echo "Table 'Adresse' créée avec succès!\n";
    
    // Role
    $pdo->exec('CREATE TABLE IF NOT EXISTS Role (
        role_id INT AUTO_INCREMENT PRIMARY KEY,
        libelle VARCHAR(50) NOT NULL UNIQUE
    )');
    echo "Table 'Role' créée avec succès!\n";
    
    // Insertion des rôles de base s'ils n'existent pas déjà
    $roles = ['visiteur', 'passager', 'chauffeur', 'admin'];
    $stmt = $pdo->prepare('INSERT IGNORE INTO Role (libelle) VALUES (?)');
    foreach ($roles as $role) {
        $stmt->execute([$role]);
    }
    echo "Rôles ajoutés avec succès!\n";
    
    // Utilisateur
    $pdo->exec('CREATE TABLE IF NOT EXISTS Utilisateur (
        utilisateur_id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(50) NOT NULL,
        prenom VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        mot_passe VARCHAR(255) NOT NULL,
        telephone VARCHAR(20),
        adresse_id INT,
        date_naissance DATE,
        photo_path VARCHAR(255),
        pseudo VARCHAR(50) UNIQUE,
        date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
        derniere_connexion DATETIME,
        FOREIGN KEY (adresse_id) REFERENCES Adresse(adresse_id)
    )');
    echo "Table 'Utilisateur' créée avec succès!\n";
    
    // Index pour la table Utilisateur
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_utilisateur_email ON Utilisateur(email)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_utilisateur_pseudo ON Utilisateur(pseudo)');
    echo "Index ajoutés avec succès!\n";
    
    // Association Utilisateur-Rôle
    $pdo->exec('CREATE TABLE IF NOT EXISTS Possede (
        utilisateur_id INT NOT NULL,
        role_id INT NOT NULL,
        PRIMARY KEY (utilisateur_id, role_id),
        FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur(utilisateur_id) ON DELETE CASCADE,
        FOREIGN KEY (role_id) REFERENCES Role(role_id)
    )');
    echo "Table 'Possede' créée avec succès!\n";
    
    // Table pour les tokens de confirmation
    $pdo->exec('CREATE TABLE IF NOT EXISTS user_confirmations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        utilisateur_id INT NOT NULL,
        token VARCHAR(100) NOT NULL,
        expires_at DATETIME NOT NULL,
        FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur(utilisateur_id) ON DELETE CASCADE
    )');
    echo "Table 'user_confirmations' créée avec succès!\n";
    
    // Balance de crédits
    $pdo->exec('CREATE TABLE IF NOT EXISTS CreditBalance (
        utilisateur_id INT PRIMARY KEY,
        solde DECIMAL(8,2) NOT NULL DEFAULT 0 CHECK (solde >= 0),
        FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur(utilisateur_id) ON DELETE CASCADE
    )');
    echo "Table 'CreditBalance' créée avec succès!\n";
    
    // Types de transaction (normalisation des valeurs)
    $pdo->exec('CREATE TABLE IF NOT EXISTS TypeTransaction (
        type_id INT AUTO_INCREMENT PRIMARY KEY,
        libelle VARCHAR(30) NOT NULL UNIQUE
    )');
    echo "Table 'TypeTransaction' créée avec succès!\n";
    
    // Insertion des types de transaction de base s'ils n'existent pas déjà
    $types = ['initial', 'achat', 'vente', 'remboursement', 'correction'];
    $stmt = $pdo->prepare('INSERT IGNORE INTO TypeTransaction (libelle) VALUES (?)');
    foreach ($types as $type) {
        $stmt->execute([$type]);
    }
    echo "Types de transaction ajoutés avec succès!\n";
    
    // Transactions de crédits
    $pdo->exec('CREATE TABLE IF NOT EXISTS CreditTransaction (
        transaction_id INT AUTO_INCREMENT PRIMARY KEY,
        utilisateur_id INT NOT NULL,
        montant DECIMAL(8,2) NOT NULL,
        type_id INT NOT NULL,
        date_transaction DATETIME DEFAULT CURRENT_TIMESTAMP,
        description VARCHAR(255),
        FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur(utilisateur_id) ON DELETE CASCADE,
        FOREIGN KEY (type_id) REFERENCES TypeTransaction(type_id)
    )');
    echo "Table 'CreditTransaction' créée avec succès!\n";
    
    // Créer la table auth_logs pour le suivi des tentatives d'authentification
    $pdo->exec('CREATE TABLE IF NOT EXISTS auth_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(50) NOT NULL,
        success TINYINT(1) NOT NULL,
        email VARCHAR(255),
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        INDEX (ip_address),
        INDEX (created_at)
    )');
    echo "Table 'auth_logs' créée avec succès!\n";
    
    // Afficher les tables existantes
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables existantes dans la base de données: " . implode(', ', $tables) . "\n";
    
} catch (PDOException $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
EOL

    # Exécuter le script PHP d'initialisation
    echo "Exécution du script d'initialisation de la base de données..."
    cd /var/www/html/backend
    php /tmp/init_db.php
    
  else
    echo "AVERTISSEMENT: JAWSDB_URL ne correspond pas au format attendu: $JAWSDB_URL"
  fi
elif [ -n "$DATABASE_URL" ]; then
  # Format de DATABASE_URL: mysql://username:password@hostname:port/database_name
  regex="^mysql://([^:]+):([^@]+)@([^:]+):([0-9]+)/(.+)$"
  if [[ $DATABASE_URL =~ $regex ]]; then
    echo "Configuration de la base de données à partir de DATABASE_URL"
    sed -i "s|DB_CONNECTION=.*|DB_CONNECTION=mysql|" /var/www/html/backend/.env
    sed -i "s|DB_HOST=.*|DB_HOST=${BASH_REMATCH[3]}|" /var/www/html/backend/.env
    sed -i "s|DB_PORT=.*|DB_PORT=${BASH_REMATCH[4]}|" /var/www/html/backend/.env
    sed -i "s|DB_DATABASE=.*|DB_DATABASE=${BASH_REMATCH[5]}|" /var/www/html/backend/.env
    sed -i "s|DB_USERNAME=.*|DB_USERNAME=${BASH_REMATCH[1]}|" /var/www/html/backend/.env
    sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${BASH_REMATCH[2]}|" /var/www/html/backend/.env
  fi
fi

# Afficher le contenu du fichier .env après modifications
echo "Contenu du fichier .env après modifications :"
cat /var/www/html/backend/.env

# Configuration de JWT_SECRET s'il est fourni
if [ -n "$JWT_SECRET" ]; then
  sed -i "s|JWT_SECRET=.*|JWT_SECRET=$JWT_SECRET|" /var/www/html/backend/.env
else
  # Générer un JWT_SECRET aléatoire si non fourni
  RANDOM_SECRET=$(openssl rand -base64 32)
  sed -i "s|JWT_SECRET=.*|JWT_SECRET=$RANDOM_SECRET|" /var/www/html/backend/.env
  echo "JWT_SECRET généré aléatoirement"
fi

# Configuration supplémentaire en fonction des variables d'environnement
if [ -n "$APP_ENV" ]; then
  sed -i "s|APP_ENV=.*|APP_ENV=$APP_ENV|" /var/www/html/backend/.env
fi

if [ -n "$APP_DEBUG" ]; then
  sed -i "s|APP_DEBUG=.*|APP_DEBUG=$APP_DEBUG|" /var/www/html/backend/.env
fi

# Configuration des variables MongoDB (pour éviter les erreurs)
echo "Configuration des variables d'environnement MongoDB..."
echo "MONGO_HOST=localhost" >> /var/www/html/backend/.env
echo "MONGO_PORT=27017" >> /var/www/html/backend/.env
echo "MONGO_USERNAME=ecoride" >> /var/www/html/backend/.env
echo "MONGO_PASSWORD=ecoride" >> /var/www/html/backend/.env
echo "MONGO_DATABASE=ecoride" >> /var/www/html/backend/.env
echo "NOSQL_URI=mongodb://localhost:27017/ecoride" >> /var/www/html/backend/.env

# Patch pour les erreurs MongoDB (création d'une classe fictive)
echo "Patch de la classe MongoConnection pour éviter les erreurs..."
mkdir -p /var/www/html/app/DataAccess/NoSql
cat > /var/www/html/app/DataAccess/NoSql/MongoConnection.php << 'EOF'
<?php
namespace App\DataAccess\NoSql;

/**
 * Classe MongoConnection factice pour éviter les erreurs
 * lorsque MongoDB n'est pas disponible
 */
class MongoConnection
{
    public function __construct()
    {
        // Ne rien faire
    }
    
    public function getCollection()
    {
        return null;
    }
    
    public function getDatabase()
    {
        return null;
    }
    
    public function getClient()
    {
        return null;
    }
}
EOF

mkdir -p /var/www/html/app/DataAccess/NoSql/Service
cat > /var/www/html/app/DataAccess/NoSql/Service/ActivityLogService.php << 'EOF'
<?php
namespace App\DataAccess\NoSql\Service;

use App\DataAccess\NoSql\MongoConnection;

/**
 * Service ActivityLogService factice pour éviter les erreurs
 */
class ActivityLogService
{
    public function __construct(MongoConnection $connection)
    {
        // Ne rien faire
    }
    
    public function create($log)
    {
        // Ne rien faire, retourner un ID factice
        return "dummy_id_" . uniqid();
    }
}
EOF

mkdir -p /var/www/html/app/DataAccess/NoSql/Model
cat > /var/www/html/app/DataAccess/NoSql/Model/ActivityLog.php << 'EOF'
<?php
namespace App\DataAccess\NoSql\Model;

/**
 * Classe ActivityLog factice pour éviter les erreurs
 */
class ActivityLog
{
    private $userId;
    private $eventType;
    private $level;
    private $description;
    private $data;
    private $source;
    private $ipAddress;
    
    public function setUserId($userId) { $this->userId = $userId; return $this; }
    public function setEventType($eventType) { $this->eventType = $eventType; return $this; }
    public function setLevel($level) { $this->level = $level; return $this; }
    public function setDescription($description) { $this->description = $description; return $this; }
    public function setData($data) { $this->data = $data; return $this; }
    public function setSource($source) { $this->source = $source; return $this; }
    public function setIpAddress($ipAddress) { $this->ipAddress = $ipAddress; return $this; }
    
    public function toArray()
    {
        return [
            'userId' => $this->userId,
            'eventType' => $this->eventType,
            'level' => $this->level,
            'description' => $this->description,
            'data' => $this->data,
            'source' => $this->source,
            'ipAddress' => $this->ipAddress
        ];
    }
}
EOF

# Correction des conflits de modules MPM d'Apache
echo "Correction des conflits de modules MPM..."
# Stopper Apache pour pouvoir modifier les modules
service apache2 stop || true

# Supprimer les fichiers de configuration des modules MPM qui pourraient être en conflit
rm -f /etc/apache2/mods-enabled/mpm_*.conf
rm -f /etc/apache2/mods-enabled/mpm_*.load

# Activer uniquement mpm_prefork
echo "Activation du module mpm_prefork uniquement..."
ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/
ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/

# Activer les modules nécessaires pour les types MIME
echo "Activation des modules nécessaires pour le traitement des fichiers statiques..."
a2enmod mime headers

# Vérifier les modules chargés
echo "Modules Apache après reconfiguration :"
ls -la /etc/apache2/mods-enabled/mpm_*

# Créer le fichier .htaccess pour le routage
echo "Création du fichier .htaccess pour le routage..."
cat > /var/www/html/backend/public/.htaccess << 'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Définir les types MIME corrects
    <IfModule mod_mime.c>
        AddType text/css .css
        AddType application/javascript .js
        AddType image/svg+xml .svg
        AddType image/png .png
        AddType image/jpeg .jpg .jpeg
        AddType image/gif .gif
    </IfModule>

    # Activer CORS pour les ressources statiques
    <IfModule mod_headers.c>
        <FilesMatch "\.(css|js|svg|jpg|jpeg|png|gif)$">
            Header set Access-Control-Allow-Origin "*"
        </FilesMatch>
    </IfModule>

    # Rediriger les assets vers le bon dossier
    RewriteCond %{REQUEST_URI} ^/assets/(.*)$
    RewriteRule ^assets/(.*)$ /frontend/assets/$1 [L]

    # Servir les fichiers statiques directement s'ils existent
    RewriteCond %{REQUEST_FILENAME} -f
    RewriteRule ^ - [L]

    # Rediriger les requêtes vers les fichiers frontend si le fichier existe
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{DOCUMENT_ROOT}/frontend/pages/public%{REQUEST_URI} -f
    RewriteRule ^(.*)$ frontend/pages/public/$1 [L]

    # Pour les requêtes d'API, rediriger vers index.php
    RewriteCond %{REQUEST_URI} ^/api/ [NC]
    RewriteRule ^ index.php [QSA,L]

    # Si on demande /, servir index.html
    RewriteRule ^$ frontend/pages/public/index.html [L]

    # Pour toute autre requête, essayer index.php
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [QSA,L]
</IfModule>
EOF

# Lister les répertoires et fichiers pour comprendre la structure
echo "Structure des répertoires:"
ls -la /var/www/html
ls -la /var/www/html/frontend
ls -la /var/www/html/frontend/pages || echo "Le répertoire frontend/pages n'existe pas"
ls -la /var/www/html/frontend/pages/public || echo "Le répertoire frontend/pages/public n'existe pas"

# Créer le lien symbolique pour les assets frontend
echo "Création du lien symbolique pour les assets frontend..."
ln -sf /var/www/html/frontend /var/www/html/backend/public/frontend

# Créer le répertoire pour les assets si nécessaire
echo "Création du répertoire pour les assets..."
mkdir -p /var/www/html/backend/public/assets
ln -sf /var/www/html/frontend/assets /var/www/html/backend/public/assets

# Vérifier si le répertoire frontend/pages/public existe et contient des fichiers HTML
if [ -d "/var/www/html/frontend/pages/public" ]; then
  echo "Copie des fichiers HTML du répertoire frontend/pages/public vers le dossier public..."
  find /var/www/html/frontend/pages/public -name "*.html" -exec cp -f {} /var/www/html/backend/public/ \; 2>/dev/null || echo "Aucun fichier HTML trouvé dans frontend/pages/public"
else
  echo "Le répertoire frontend/pages/public n'existe pas"
fi

# Si nous n'avons pas de fichier index.html, créons-en un de base
if [ ! -f "/var/www/html/backend/public/index.html" ]; then
  echo "Création d'un fichier index.html de base..."
  cat > /var/www/html/backend/public/index.html << 'EOF'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Location de véhicules écologiques</title>
    <link rel="stylesheet" href="/assets/styles/main.min.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <h1>EcoRide</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="/">Accueil</a></li>
                    <li><a href="/vehicles.html">Véhicules</a></li>
                    <li><a href="/login.html">Connexion</a></li>
                    <li><a href="/register.html">Inscription</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main>
        <section class="hero">
            <div class="container">
                <h1>Déplacez-vous de façon écologique</h1>
                <p>Louez un véhicule électrique pour vos déplacements quotidiens ou occasionnels</p>
            </div>
        </section>
    </main>
    <footer>
        <div class="container">
            <p>&copy; 2025 EcoRide - Tous droits réservés</p>
        </div>
    </footer>
    <script src="/assets/js/common/api.js"></script>
    <script src="/assets/js/common/menu.js"></script>
    <script src="/assets/js/common/auth.js"></script>
    <script src="/assets/js/common/menu-auth.js"></script>
    <script src="/assets/js/common/userProfile.js"></script>
    <script src="/assets/js/pages/index.js"></script>
</body>
</html>
EOF
fi

# Vérifier la structure des assets
echo "Structure des assets:"
ls -la /var/www/html/frontend/assets || echo "Dossier assets non trouvé"
ls -la /var/www/html/frontend/assets/styles || echo "Dossier styles non trouvé"
ls -la /var/www/html/frontend/assets/js || echo "Dossier js non trouvé"

# Démarrer Apache avec la configuration corrigée
echo "Démarrage d'Apache..."
apache2-foreground 