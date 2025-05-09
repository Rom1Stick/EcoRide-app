FROM php:8.2-apache

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libssl-dev

# Activer le module rewrite pour .htaccess
RUN a2enmod rewrite

# Installation des extensions PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Installation de l'extension MongoDB
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuration du répertoire de travail
WORKDIR /var/www/html

# Générer un fichier .env par défaut si absent
RUN { \
    echo 'APP_ENV=development'; \
    echo 'APP_DEBUG=true'; \
    echo 'APP_TIMEZONE=Europe/Paris'; \
    echo 'JWT_SECRET=change_me'; \
    echo 'JWT_EXPIRATION=3600'; \
    echo 'DB_CONNECTION=mysql'; \
    echo 'DB_HOST=mysql'; \
    echo 'DB_PORT=3306'; \
    echo 'DB_DATABASE=ecoride'; \
    echo 'DB_USERNAME=ecorider'; \
    echo 'DB_PASSWORD=securepass'; \
} > .env

# Copie des fichiers de configuration
COPY ./docker/php/php.ini /usr/local/etc/php/conf.d/local.ini

# Définition des permissions
RUN chown -R www-data:www-data /var/www/html 