#!/bin/bash
set -e

# Afficher le contenu initial du fichier .env
echo "Contenu du fichier .env avant modifications :"
cat /var/www/html/backend/.env

# Récupérer les variables d'environnement de Heroku et configurer le fichier .env
if [ -n "$JAWSDB_URL" ]; then
  # Format de JAWSDB_URL: mysql://username:password@hostname:port/database_name
  echo "Configuration de la base de données à partir de JAWSDB_URL"
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

# Vérification de la base de données et du fichier .env
echo "Vérification de l'accès à la base de données..."
cd /var/www/html/backend
php -r "
\$host = getenv('DB_HOST') ?: 'localhost';
\$port = getenv('DB_PORT') ?: '3306';
\$database = getenv('DB_DATABASE') ?: 'ecoride';
\$username = getenv('DB_USERNAME') ?: 'root';
\$password = getenv('DB_PASSWORD') ?: '';

echo \"Trying to connect to MySQL: host=\$host, port=\$port, db=\$database, user=\$username\\n\";

\$dsn = \"mysql:host=\$host;port=\$port;dbname=\$database\";
try {
    \$pdo = new PDO(\$dsn, \$username, \$password);
    echo \"Connexion à la base de données réussie!\\n\";
    
    // Vérifier si les tables existent
    \$tables = \$pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo \"Tables trouvées: \" . implode(', ', \$tables) . \"\\n\";
    
    // Vérifier si la table users existe
    \$userTableExists = in_array('users', \$tables);
    if (!\$userTableExists) {
        echo \"La table 'users' n'existe pas. Création...\\n\";
        \$sql = 'CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )';
        \$pdo->exec(\$sql);
        echo \"Table 'users' créée avec succès!\\n\";
    }
} catch (PDOException \$e) {
    echo \"Erreur de connexion à la base de données: \" . \$e->getMessage() . \"\\n\";
}
"

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

# Création des tables de base de données si nécessaires
echo "Vérification et création des tables de base de données..."
cd /var/www/html/backend
php artisan migrate --force || echo "Erreur lors de la migration de la base de données"

# Créer un fichier index.php de redirection si besoin
echo "Création d'un fichier index.php de redirection..."
cat > /var/www/html/backend/public/redirect.php << 'EOF'
<?php
// Redirection simple vers index.html si on accède directement à index.php
header('Location: /index.html');
exit;
EOF

# Démarrer Apache avec la configuration corrigée
echo "Démarrage d'Apache..."
apache2-foreground 