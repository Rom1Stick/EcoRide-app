#!/bin/bash
set -e

# Installer les dépendances PHP si elles ne sont pas déjà installées
if [ ! -d "/var/www/html/vendor" ] || [ ! -f "/var/www/html/vendor/bin/phpunit" ]; then
  echo "Installation des dépendances PHP..."
  composer install --no-interaction --no-progress
else
  echo "Les dépendances PHP sont déjà installées."
fi

# Assurer les bonnes permissions
chmod -R 777 /var/www/html

# Exécuter la commande passée en argument
exec "$@" 