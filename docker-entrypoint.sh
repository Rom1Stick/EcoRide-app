#!/bin/sh
set -e

# Configuration du port pour Heroku
PORT=${PORT:-80}

# Remplacer la variable PORT dans la configuration Apache
sed -i "s/\${PORT}/$PORT/g" /etc/apache2/sites-available/000-default.conf

# Créer le fichier ports.conf avec le bon port
echo "Listen $PORT" > /etc/apache2/ports.conf

# Démarrer Apache
exec apache2-foreground 