#!/bin/bash
set -e

# Récupérer les variables d'environnement de Heroku et configurer le fichier .env
if [ -n "$DATABASE_URL" ]; then
  # Format de DATABASE_URL: mysql://username:password@hostname:port/database_name
  regex="^mysql://([^:]+):([^@]+)@([^:]+):([0-9]+)/(.+)$"
  if [[ $DATABASE_URL =~ $regex ]]; then
    echo "Configuration de la base de données à partir de DATABASE_URL"
    sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=mysql/" /var/www/html/backend/.env
    sed -i "s/DB_HOST=.*/DB_HOST=${BASH_REMATCH[3]}/" /var/www/html/backend/.env
    sed -i "s/DB_PORT=.*/DB_PORT=${BASH_REMATCH[4]}/" /var/www/html/backend/.env
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=${BASH_REMATCH[5]}/" /var/www/html/backend/.env
    sed -i "s/DB_USERNAME=.*/DB_USERNAME=${BASH_REMATCH[1]}/" /var/www/html/backend/.env
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=${BASH_REMATCH[2]}/" /var/www/html/backend/.env
  fi
fi

# Configuration de JWT_SECRET s'il est fourni
if [ -n "$JWT_SECRET" ]; then
  sed -i "s/JWT_SECRET=.*/JWT_SECRET=$JWT_SECRET/" /var/www/html/backend/.env
fi

# Configuration supplémentaire en fonction des variables d'environnement
if [ -n "$APP_ENV" ]; then
  sed -i "s/APP_ENV=.*/APP_ENV=$APP_ENV/" /var/www/html/backend/.env
fi

if [ -n "$APP_DEBUG" ]; then
  sed -i "s/APP_DEBUG=.*/APP_DEBUG=$APP_DEBUG/" /var/www/html/backend/.env
fi

# Créer un fichier .htaccess pour le routage frontend/backend
cat > /var/www/html/backend/public/.htaccess <<EOL
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Règle pour rediriger les requêtes API vers le backend PHP
    RewriteCond %{REQUEST_URI} ^/api/ [NC]
    RewriteRule ^(.*)$ index.php [L]
    
    # Règle pour servir les fichiers statiques
    RewriteCond %{REQUEST_FILENAME} -f
    RewriteRule ^ - [L]
    
    # Si le fichier requis n'existe pas, passer à l'index PHP
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [L]
</IfModule>
EOL

# Créer un lien symbolique pour les assets frontend dans le dossier public
ln -sf /var/www/html/frontend /var/www/html/backend/public/frontend

# Démarrer Apache en avant-plan
apache2-foreground 