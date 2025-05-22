# syntax = docker/dockerfile:1

# Base PHP-Apache avec Node.js
FROM php:8.2-apache as base

# Installation de Node.js
RUN apt-get update && apt-get install -y \
    curl \
    git \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libssl-dev \
    gnupg

# Installer Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs

# Activer le module rewrite pour .htaccess
RUN a2enmod rewrite

# Installation des extensions PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Installation de l'extension MongoDB
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuration de l'environnement
ENV NODE_ENV=production
ENV PORT=8080
ENV APACHE_DOCUMENT_ROOT=/var/www/html/backend/public

# Configuration d'Apache pour servir depuis le bon répertoire
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configuration du répertoire de travail
WORKDIR /var/www/html

# Étape de build
FROM base as build

# Copier le code source
COPY . .

# Installer les dépendances frontend et builder
WORKDIR /var/www/html
RUN npm install --production=false
RUN npm run build

# Installer les dépendances backend
WORKDIR /var/www/html/backend
RUN composer install --no-dev --optimize-autoloader

# Configuration pour la production
RUN { \
    echo 'APP_ENV=production'; \
    echo 'APP_DEBUG=false'; \
    echo 'APP_TIMEZONE=Europe/Paris'; \
    echo 'JWT_SECRET='${JWT_SECRET:-changeme_in_production}; \
    echo 'JWT_EXPIRATION=3600'; \
    echo 'DB_CONNECTION='${DB_CONNECTION:-mysql}; \
    echo 'DB_HOST='${DB_HOST:-localhost}; \
    echo 'DB_PORT='${DB_PORT:-3306}; \
    echo 'DB_DATABASE='${DB_DATABASE:-ecoride}; \
    echo 'DB_USERNAME='${DB_USERNAME:-root}; \
    echo 'DB_PASSWORD='${DB_PASSWORD:-}; \
} > .env

# Étape finale
FROM base

# Copier les fichiers nécessaires
COPY --from=build /var/www/html /var/www/html

# Configuration du serveur pour Heroku
RUN echo "Listen \${PORT}" > /etc/apache2/ports.conf
RUN sed -i "s/80/\${PORT}/g" /etc/apache2/sites-available/000-default.conf

# Définir les permissions
RUN chown -R www-data:www-data /var/www/html

# Exposer le port
EXPOSE $PORT

# Script de démarrage pour configurer et démarrer Apache
COPY ./docker/heroku-start.sh /usr/local/bin/heroku-start.sh
RUN chmod +x /usr/local/bin/heroku-start.sh

# Commande de démarrage
CMD ["/usr/local/bin/heroku-start.sh"]
