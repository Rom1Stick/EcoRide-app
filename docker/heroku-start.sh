#!/bin/bash
set -e

# Afficher les modules Apache chargés pour le débogage
echo "Modules Apache chargés avant désactivation :"
apache2ctl -M | grep mpm || true

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
echo "Création du fichier .htaccess pour le routage..."
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

# Créer un lien symbolique pour les assets frontend dans le dossier public et vérifier qu'il a été créé
echo "Création du lien symbolique pour les assets frontend..."
ln -sf /var/www/html/frontend /var/www/html/backend/public/frontend

# Vérifier si le lien symbolique a été créé et afficher le contenu du répertoire public
echo "Contenu du répertoire public après création du lien symbolique :"
ls -la /var/www/html/backend/public/

# Vérifier si le contenu du frontend est accessible
echo "Contenu du répertoire frontend :"
ls -la /var/www/html/frontend/

# Créer un lien symbolique pour index.html et les autres fichiers HTML à la racine du répertoire public
echo "Copie des fichiers HTML du frontend vers le dossier public..."
cp /var/www/html/frontend/*.html /var/www/html/backend/public/

# Vérifier si les fichiers HTML ont été copiés
echo "Contenu du répertoire public après copie des fichiers HTML :"
ls -la /var/www/html/backend/public/

# Correction du problème MPM d'Apache
echo "Désactivation des modules MPM en conflit..."
# Désactiver tous les modules MPM possibles
a2dismod mpm_event || true
a2dismod mpm_worker || true
a2dismod mpm_prefork || true
a2dismod mpm_itk || true

# Ensuite activer seulement mpm_prefork
echo "Activation du module mpm_prefork uniquement..."
a2enmod mpm_prefork

# Activer le module rewrite pour le .htaccess
echo "Activation du module rewrite..."
a2enmod rewrite

# Vérifier que seul mpm_prefork est activé
echo "Modules Apache chargés après reconfiguration :"
apache2ctl -M | grep mpm || true

# Démarrer Apache en avant-plan
echo "Démarrage d'Apache..."
apache2-foreground 