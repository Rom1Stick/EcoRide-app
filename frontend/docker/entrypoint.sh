#!/bin/sh
# Entry point pour Nginx sur Heroku : substitution de la variable PORT et lancement de Nginx

# Substituer ${PORT} dans le template de configuration
envsubst '${PORT}' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf

# Démarrer Nginx
exec nginx -g 'daemon off;' 