# --- Stage 1: Build du frontend ---
FROM node:18-alpine AS build-frontend
WORKDIR /app
COPY frontend/package*.json ./
RUN npm install
COPY frontend/ ./
RUN npm run build:scss

# --- Stage 2: Image PHP-FPM avec Nginx pour production ---
FROM php:8.1-fpm AS production

# Installation des extensions PHP nécessaires pour MySQL
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    nginx \
    gettext-base \
    && docker-php-ext-install zip pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier le frontend buildé
COPY --from=build-frontend /app/assets /var/www/html/assets
COPY --from=build-frontend /app/pages /var/www/html/pages
COPY --from=build-frontend /app/bin /var/www/html/bin

# Copier le backend PHP
COPY backend/ /var/www/html/api/

# Configuration du répertoire de travail
WORKDIR /var/www/html

# Installer les dépendances PHP
COPY backend/composer.json /var/www/html/api/
RUN cd /var/www/html/api && composer install --no-dev --optimize-autoloader --no-cache

# Créer un fichier index.php à la racine pour rediriger vers le bon endroit
RUN echo '<?php header("Location: /pages/public/"); exit; ?>' > /var/www/html/index.php

# Configuration des permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Script de démarrage avec configuration Nginx simplifiée
RUN echo '#!/bin/bash\n\
echo "🚀 Démarrage de EcoRide..."\n\
\n\
# Configuration de la base de données\n\
php /var/www/html/api/scripts/heroku-db-config.php\n\
\n\
# Créer un fichier d environnement pour PHP-FPM\n\
echo "📝 Création du fichier d environnement PHP..."\n\
cat > /var/www/html/.env << EOF\n\
APP_DEBUG=false\n\
APP_TIMEZONE=Europe/Paris\n\
DB_CONNECTION=mysql\n\
EOF\n\
\n\
# Ajouter les variables de base de données si disponibles\n\
if [ -n "$JAWSDB_URL" ]; then\n\
  echo "JAWSDB_URL=$JAWSDB_URL" >> /var/www/html/.env\n\
  echo "DATABASE_URL=$JAWSDB_URL" >> /var/www/html/.env\n\
elif [ -n "$DATABASE_URL" ]; then\n\
  echo "DATABASE_URL=$DATABASE_URL" >> /var/www/html/.env\n\
fi\n\
\n\
echo "🌐 Configuration du port Heroku: ${PORT:-80}"\n\
\n\
# Créer la configuration Nginx dynamiquement\n\
cat > /etc/nginx/sites-available/default << EOF\n\
server {\n\
    listen ${PORT:-80};\n\
    server_name _;\n\
    root /var/www/html;\n\
    index index.html index.php;\n\
\n\
    # Configuration proxy pour éviter exposition du port\n\
    port_in_redirect off;\n\
    absolute_redirect off;\n\
\n\
    # Servir les assets statiques\n\
    location /assets/ {\n\
        try_files \\$uri \\$uri/ =404;\n\
        expires 1y;\n\
        add_header Cache-Control "public";\n\
    }\n\
\n\
    # Servir les pages frontend\n\
    location /pages/ {\n\
        try_files \\$uri \\$uri/ =404;\n\
        expires 30d;\n\
        add_header Cache-Control "public";\n\
    }\n\
\n\
    # API backend PHP\n\
    location /api/ {\n\
        try_files \\$uri /api/public/index.php\\$is_args\\$args;\n\
    }\n\
\n\
    # Traitement des fichiers PHP\n\
    location ~ \\.php$ {\n\
        include fastcgi_params;\n\
        fastcgi_pass 127.0.0.1:9000;\n\
        fastcgi_param SCRIPT_FILENAME \\$document_root\\$fastcgi_script_name;\n\
        fastcgi_param SCRIPT_NAME \\$fastcgi_script_name;\n\
        fastcgi_read_timeout 300;\n\
    }\n\
\n\
    # Redirection racine vers frontend\n\
    location = / {\n\
        return 302 /pages/public/;\n\
    }\n\
\n\
    # Sécurité - refuser accès aux fichiers sensibles\n\
    location ~ /\\. {\n\
        deny all;\n\
    }\n\
\n\
    location ~ \\.(env|git|md|json|yml|yaml)$ {\n\
        deny all;\n\
    }\n\
}\n\
EOF\n\
\n\
echo "🌐 Démarrage de PHP-FPM..."\n\
php-fpm -D\n\
\n\
echo "🌐 Démarrage de Nginx..."\n\
nginx -g "daemon off;"' > /startup.sh && chmod +x /startup.sh

# Exposer le port standard (sera remplacé par $PORT sur Heroku)
EXPOSE 80

# Utiliser le script de démarrage
CMD ["/startup.sh"]