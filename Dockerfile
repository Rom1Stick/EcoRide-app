# syntax = docker/dockerfile:1

# Adjust NODE_VERSION as desired
ARG NODE_VERSION=20.18.0
FROM node:${NODE_VERSION}-slim as base

LABEL fly_launch_runtime="NodeJS"

# NodeJS app lives here
WORKDIR /app

# Set production environment
ENV NODE_ENV=production


# Throw-away build stage to reduce size of final image
FROM base as build

# Install packages needed to build node modules
RUN apt-get update -qq && \
    apt-get install -y python-is-python3 pkg-config build-essential 

# Install node modules
COPY --link package.json package-lock.json .
RUN npm install --production=false

# Copy application code
COPY --link . .

# Build application
RUN npm run build

# Remove development dependencies
RUN npm prune --production


# Final stage for app image
FROM base

# Copy built application
COPY --from=build /app /app

# Start the server by default, this can be overwritten at runtime
CMD [ "npm", "run", "start" ]

FROM nginx:alpine

# Copier les fichiers frontend dans le répertoire de service Nginx
COPY frontend/dist /usr/share/nginx/html

# Copier une configuration Nginx personnalisée si nécessaire
RUN mkdir -p /etc/nginx/templates
COPY nginx.conf.template /etc/nginx/templates/default.conf.template

# Définir le port comme variable d'environnement (requis par Heroku)
ENV PORT=8080

# Script d'entrée pour utiliser le PORT dynamique attribué par Heroku
COPY docker-entrypoint.sh /
RUN chmod +x /docker-entrypoint.sh

# Commande de démarrage
CMD ["/docker-entrypoint.sh"]
