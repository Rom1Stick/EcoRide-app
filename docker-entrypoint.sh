#!/bin/sh
set -e

# Remplacer le port dans le fichier de configuration Nginx
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

# Démarrer Nginx
exec nginx -g 'daemon off;' 