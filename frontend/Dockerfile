# Étape 1: Compiler le SCSS en CSS
FROM node:18-alpine as builder

WORKDIR /app

# Copier les fichiers nécessaires pour installer les dépendances
COPY package*.json ./
RUN npm install

# Copier les sources SCSS et compiler en CSS
COPY assets/scss ./assets/scss
RUN npm run build:scss

# Copie et minification des fichiers JavaScript
COPY assets/js ./assets/js/src
RUN npx eslint "assets/js/src/**/*.js" --fix || true \
 && mkdir -p assets/js/dist \
 && cp -r assets/js/src/. assets/js/dist/ \
 && find assets/js/dist -type f -name "*.js" -exec sh -c 'terser "$1" --compress --mangle --output "$1"' _ {} \;

# Étape 2: Image finale avec Nginx pour Heroku
FROM nginx:alpine

# Ajouter un script d'entrée pour configurer Nginx avec le PORT dynamique de Heroku
RUN echo '#!/bin/sh\necho "server { \n\
    listen \$PORT;\n\
    server_name localhost;\n\
    root /usr/share/nginx/html;\n\
    index pages/public/index.html;\n\
\n\
    # Redirection de / vers /pages/public/index.html\n\
    location = / {\n\
        return 301 /pages/public/index.html;\n\
    }\n\
\n\
    # Configuration pour les fichiers statiques\n\
    location /assets/ {\n\
        access_log off;\n\
        expires 30d;\n\
    }\n\
\n\
    # Règle fallback pour les routes frontend\n\
    location /pages/ {\n\
        try_files \$uri \$uri/ /pages/public/index.html;\n\
    }\n\
\n\
    # Gestion des erreurs\n\
    error_page 404 /pages/public/error.html;\n\
}" > /etc/nginx/conf.d/default.conf' > /docker-entrypoint.sh && chmod +x /docker-entrypoint.sh

# Copier les fichiers statiques depuis la première étape
COPY --from=builder /app/assets/styles /usr/share/nginx/html/assets/styles
COPY --from=builder /app/assets/js/dist /usr/share/nginx/html/assets/js

# Copier les autres fichiers statiques
COPY pages/ /usr/share/nginx/html/pages/
COPY assets/images/ /usr/share/nginx/html/assets/images/
COPY assets/fonts/ /usr/share/nginx/html/assets/fonts/
COPY assets/img/ /usr/share/nginx/html/assets/img/

# Démarrer Nginx avec la configuration dynamique
CMD /docker-entrypoint.sh && nginx -g 'daemon off;' 