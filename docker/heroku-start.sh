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
    
    # Créer un script temporaire
    cat > /tmp/init-db.php <<'EOF'
<?php
try {
    // Se connecter à la base de données en utilisant l'URL JAWSDB
    $jawsdb_url = getenv('JAWSDB_URL');
    $dbh = new PDO($jawsdb_url);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Créer la table users si elle n'existe pas
    $dbh->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    echo "Table 'users' créée avec succès.\n";
    echo "Base de données initialisée avec succès.\n";
} catch (PDOException $e) {
    die("Erreur lors de l'initialisation de la base de données : " . $e->getMessage() . "\n");
}
EOF

    # Exécuter le script
    php /tmp/init-db.php
    
    # S'assurer que notre fichier api.php personnalisé est utilisé
    echo "Copie du fichier api.php personnalisé..."
    cp -f /var/www/html/backend/routes/api.php /var/www/html/backend/routes/api.php.backup
  fi
fi

# Modifier le fichier index.php pour remplacer toutes les classes et dépendances non disponibles
echo "Modification du fichier index.php..."
cat > /var/www/html/backend/public/index.php <<'EOF'
<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Définir le chemin de base
define('ROOT_PATH', realpath(__DIR__ . '/..'));

// Fonction env helper
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

// Charger l'autoloader de Composer
require_once ROOT_PATH . '/vendor/autoload.php';

// Inclure les routes
require_once ROOT_PATH . '/routes/api.php';
EOF

# Désactiver tous les modules Apache MPM puis activer uniquement mpm_prefork
a2dismod mpm_event
a2dismod mpm_worker
a2enmod mpm_prefork
a2enmod rewrite

# Démarrer Apache en avant-plan
apache2-foreground 