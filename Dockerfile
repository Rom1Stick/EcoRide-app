# syntax = docker/dockerfile:1

# Image de base pour PHP et Apache
FROM php:8.2-apache as base

LABEL maintainer="Équipe EcoRide"

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libssl-dev \
    nodejs \
    npm

# Activer le module rewrite pour .htaccess
RUN a2enmod rewrite

# Installation des extensions PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Installation de l'extension MongoDB
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définition des variables d'environnement
ENV NODE_ENV=production
ENV PORT=8080

# Stage de build pour le frontend
FROM base as frontend-build

WORKDIR /app/frontend

# Copie des fichiers package.json et installation des dépendances
COPY --link frontend/package.json frontend/package-lock.json ./
RUN npm install --production=false

# Copie du code source frontend
COPY --link frontend ./

# Build du frontend (utiliser le script disponible)
RUN npm run build:scss

# Stage de build pour le backend
FROM base as backend-build

WORKDIR /app/backend

# Copie des fichiers composer.json et installation des dépendances
COPY --link backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# Copie du code source backend
COPY --link backend ./

# Image finale
FROM base

# Configuration d'Apache pour servir à la fois le frontend et l'API
COPY docker/apache-config.conf /etc/apache2/sites-available/000-default.conf

# Copie du frontend depuis l'étape de build
COPY --from=frontend-build /app/frontend /var/www/html/frontend

# Copie du backend depuis l'étape de build
COPY --from=backend-build /app/backend /var/www/html/backend

# Copie du fichier serve.json pour la configuration
COPY serve.json /var/www/html/

# Installation de serve pour le frontend
RUN npm install -g serve

# Définir le répertoire de travail
WORKDIR /var/www/html

# Script pour configurer le backend et démarrer le serveur
RUN echo '#!/bin/bash\n\
setup_backend_env() {\n\
  # Création du fichier .env pour PHP\n\
  cat > /var/www/html/backend/.env << EOL\n\
APP_ENV=production\n\
APP_DEBUG=false\n\
APP_TIMEZONE=Europe/Paris\n\
\n\
# Configuration de la base de données\n\
DB_CONNECTION=mysql\n\
\n\
# Utilisation de DATABASE_URL si fourni par Heroku\n\
if [ -n "$DATABASE_URL" ]; then\n\
  # Extraction des informations de DATABASE_URL\n\
  DB_HOST=$(echo $DATABASE_URL | sed -e "s/.*@\\(.*\\):\\(.*\\)\\/.*/\\1/")\n\
  DB_PORT=$(echo $DATABASE_URL | sed -e "s/.*@.*:\\(.*\\)\\/.*/\\1/")\n\
  DB_DATABASE=$(echo $DATABASE_URL | sed -e "s/.*\\/\\(.*\\).*/\\1/")\n\
  DB_USERNAME=$(echo $DATABASE_URL | sed -e "s/.*:\\/\\/\\(.*\\):.*/\\1/")\n\
  DB_PASSWORD=$(echo $DATABASE_URL | sed -e "s/.*:\\/\\/.*:\\(.*\\)@.*/\\1/")\n\
fi\n\
\n\
# Configuration JWT\n\
JWT_SECRET=${JWT_SECRET:-$(cat /dev/urandom | tr -dc "a-zA-Z0-9" | fold -w 32 | head -n 1)}\n\
JWT_EXPIRATION=3600\n\
\n\
# Configuration MongoDB si MONGODB_URI est fourni\n\
if [ -n "$MONGODB_URI" ]; then\n\
  MONGO_CONNECTION=mongodb\n\
  MONGO_HOST=$(echo $MONGODB_URI | sed -e "s/.*@\\(.*\\):\\(.*\\)\\/.*/\\1/")\n\
  MONGO_PORT=$(echo $MONGODB_URI | sed -e "s/.*@.*:\\(.*\\)\\/.*/\\1/")\n\
  MONGO_DATABASE=$(echo $MONGODB_URI | sed -e "s/.*\\/\\(.*\\).*/\\1/")\n\
  MONGO_USERNAME=$(echo $MONGODB_URI | sed -e "s/.*:\\/\\/\\(.*\\):.*/\\1/")\n\
  MONGO_PASSWORD=$(echo $MONGODB_URI | sed -e "s/.*:\\/\\/.*:\\(.*\\)@.*/\\1/")\n\
fi\n\
\n\
# Configuration API\n\
API_BASE_URL=${APP_URL:-https://ecoride-application.herokuapp.com}/api\n\
EOL\n\
\n\
  # Définir les permissions\n\
  chown -R www-data:www-data /var/www/html/backend\n\
  chmod -R 755 /var/www/html/backend/storage\n\
}\n\
\n\
if [ "$SERVE_FRONTEND_ONLY" = "true" ]; then\n\
  serve -c /var/www/html/serve.json -l $PORT\n\
else\n\
  # Configurer le backend avant de démarrer Apache\n\
  setup_backend_env\n\
  apache2-foreground\n\
fi' > /usr/local/bin/start-server.sh && \
chmod +x /usr/local/bin/start-server.sh

# Exposer le port
EXPOSE $PORT

# Commande de démarrage
CMD ["/usr/local/bin/start-server.sh"]
