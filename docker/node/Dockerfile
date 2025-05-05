# docker/node/Dockerfile

FROM node:18-slim

# Créer un utilisateur non-root
RUN addgroup -g 1001 -S app && adduser -S app -u 1001 -G app

# Définir le dossier de travail dans le frontend
WORKDIR /app/frontend

# Copier les fichiers de dépendances du frontend et installer
COPY frontend/package*.json ./
RUN npm install --legacy-peer-deps

# Rebuild esbuild pour correspondre à l'OS du container
RUN npm rebuild esbuild --force --platform=linux --arch=x64

# Copier le code source du frontend
COPY frontend/ ./

# Exposer le port Vite
EXPOSE 5173

# Lancer le serveur Vite en dev
CMD ["npm", "run", "dev"]
