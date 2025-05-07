# Étape 1: Compiler le SCSS en CSS
FROM node:18-alpine as builder

WORKDIR /app

# Copier les fichiers nécessaires pour installer les dépendances
COPY package*.json ./
RUN npm install

# Copier les sources SCSS
COPY assets/scss ./assets/scss

# Compiler le SCSS en CSS
RUN npm run build:scss

# Optimisation des fichiers JavaScript
RUN mkdir -p ./assets/js/dist
COPY assets/js ./assets/js/src
RUN npx eslint assets/js/src --fix || true
RUN npx terser assets/js/src/*.js --compress --mangle --output assets/js/dist

# Étape 2: Servir les fichiers statiques avec Nginx
FROM nginx:alpine

# Copier la configuration Nginx
COPY docker/nginx-frontend.conf /etc/nginx/conf.d/default.conf

# Copier les fichiers statiques depuis la première étape
COPY --from=builder /app/assets/css /usr/share/nginx/html/assets/css
COPY --from=builder /app/assets/js/dist /usr/share/nginx/html/assets/js

# Copier les fichiers statiques directement
COPY pages/ /usr/share/nginx/html/pages/
COPY assets/images/ /usr/share/nginx/html/assets/images/
COPY assets/fonts/ /usr/share/nginx/html/assets/fonts/
COPY assets/img/ /usr/share/nginx/html/assets/img/

# Headers de sécurité et optimisation
RUN echo 'add_header X-Content-Type-Options "nosniff";' >> /etc/nginx/conf.d/security.conf
RUN echo 'add_header X-Frame-Options "SAMEORIGIN";' >> /etc/nginx/conf.d/security.conf
RUN echo 'add_header X-XSS-Protection "1; mode=block";' >> /etc/nginx/conf.d/security.conf

# Exposer le port 80
EXPOSE 80

# Démarrer Nginx
CMD ["nginx", "-g", "daemon off;"] 