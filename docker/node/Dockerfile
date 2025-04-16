# docker/node/Dockerfile

FROM node:18-alpine

# Créer l'utilisateur non-root (meilleure sécurité)
RUN addgroup -g 1001 -S app && adduser -S app -u 1001 -G app

WORKDIR /app

# Installer Vite et Husky globalement
RUN npm install -g vite husky

# Copier les fichiers package.json de la racine
COPY package*.json ./

# Désactiver temporairement le hook prepare pour éviter l'erreur
ENV HUSKY=0

# Nettoyer et installer les dépendances
RUN rm -rf node_modules package-lock.json
RUN npm cache clean --force && npm install

# Copier le reste du code source
COPY . .

# Réactiver Husky et l'initialiser manuellement
ENV HUSKY=1
RUN npx husky install

# Rendre les hooks exécutables
RUN chmod +x .husky/pre-commit .husky/pre-push .husky/_/husky.sh

# Exposer le port de Vite
EXPOSE 5173

# Lancer le serveur Vite depuis la racine
CMD ["npm", "run", "dev"]
