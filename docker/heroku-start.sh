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

# Créer le lien symbolique pour les assets frontend
echo "Création du lien symbolique pour les assets frontend..."
ln -sf /var/www/html/frontend /var/www/html/backend/public/frontend

# Créer le répertoire pour les assets si nécessaire
echo "Création du répertoire pour les assets..."
mkdir -p /var/www/html/backend/public/assets
ln -sf /var/www/html/frontend/assets /var/www/html/backend/public/assets

# Afficher le contenu du répertoire public
echo "Contenu du répertoire public après création des liens symboliques :"
ls -la /var/www/html/backend/public

# Afficher la structure du dossier frontend/pages
echo "Structure du dossier frontend/pages :"
ls -la /var/www/html/frontend/pages
ls -la /var/www/html/frontend/pages/public

# Copier les fichiers index.html et autres HTML du frontend vers le dossier public
echo "Copie des fichiers HTML du frontend/pages/public vers le dossier public..."
cp -f /var/www/html/frontend/pages/public/index.html /var/www/html/backend/public/index.html 2>/dev/null || echo "index.html non trouvé"

# Vérifier la structure des assets
echo "Structure des assets:"
ls -la /var/www/html/frontend/assets || echo "Dossier assets non trouvé"
ls -la /var/www/html/frontend/assets/styles || echo "Dossier styles non trouvé"
ls -la /var/www/html/frontend/assets/js || echo "Dossier js non trouvé"

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