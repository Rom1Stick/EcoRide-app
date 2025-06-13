#!/bin/bash

# Script de test local Docker pour EcoRide
# Usage: ./scripts/test-docker.sh

set -e

echo "🧪 Test de l'image Docker EcoRide en local..."

# Variables
IMAGE_NAME="ecoride-local"
CONTAINER_NAME="ecoride-test"
PORT=3000

# Cleanup des conteneurs/images existants
echo "🧹 Nettoyage des containers existants..."
docker stop $CONTAINER_NAME 2>/dev/null || true
docker rm $CONTAINER_NAME 2>/dev/null || true
docker rmi $IMAGE_NAME 2>/dev/null || true

# Build de l'image
echo "🔨 Build de l'image Docker..."
docker build -t $IMAGE_NAME .

# Lancement du conteneur
echo "🚀 Lancement du conteneur de test..."
docker run -d \
  --name $CONTAINER_NAME \
  -p $PORT:80 \
  -e PORT=80 \
  -e NODE_ENV=production \
  -e PHP_ENV=production \
  $IMAGE_NAME

# Attendre que le conteneur démarre
echo "⏳ Attente du démarrage..."
sleep 10

# Test de santé
echo "🩺 Test de santé de l'application..."
if curl -f http://localhost:$PORT > /dev/null 2>&1; then
    echo "✅ Application accessible sur http://localhost:$PORT"
    
    # Test de l'API
    if curl -f http://localhost:$PORT/api > /dev/null 2>&1; then
        echo "✅ API accessible sur http://localhost:$PORT/api"
    else
        echo "⚠️  API non accessible (normal si pas d'endpoint racine)"
    fi
else
    echo "❌ Application non accessible"
    docker logs $CONTAINER_NAME
    exit 1
fi

echo "📊 Informations du conteneur:"
docker stats --no-stream $CONTAINER_NAME

echo "📋 Pour interagir avec le conteneur:"
echo "   docker logs $CONTAINER_NAME           # Voir les logs"
echo "   docker exec -it $CONTAINER_NAME bash  # Se connecter au conteneur"
echo "   docker stop $CONTAINER_NAME           # Arrêter le conteneur"
echo "   docker rm $CONTAINER_NAME             # Supprimer le conteneur"

echo ""
echo "🌐 Application de test disponible sur: http://localhost:$PORT"
echo "🛑 Pour arrêter: docker stop $CONTAINER_NAME" 