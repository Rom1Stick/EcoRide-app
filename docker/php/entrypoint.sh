#!/bin/sh
# Entry point pour configurer Apache sur Heroku avec le port dynamique

# Désactiver mpm_event et mpm_worker, activer mpm_prefork
a2dismod mpm_event mpm_worker
a2enmod mpm_prefork
# Redémarrer Apache pour prendre en compte les modules MPM
service apache2 restart

# Si la variable PORT est définie, ajuster la configuration Apache
if [ -n "$PORT" ]; then
  sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
  sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/" /etc/apache2/sites-available/000-default.conf
fi

# Lancer à l'origine l'entrypoint officiel puis Apache
exec docker-php-entrypoint apache2-foreground 