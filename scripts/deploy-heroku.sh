#!/bin/bash

# Script de déploiement automatisé pour Heroku
# Usage: ./scripts/deploy-heroku.sh [app-name]

set -e

APP_NAME=${1:-ecoride-application}
echo "🚀 Déploiement de l'application EcoRide sur Heroku..."
echo "📱 Application: $APP_NAME"

# Vérification des pré-requis
echo "🔍 Vérification des pré-requis..."

if ! command -v heroku &> /dev/null; then
    echo "❌ Heroku CLI n'est pas installé. Installez-le depuis https://devcenter.heroku.com/articles/heroku-cli"
    exit 1
fi

if ! command -v docker &> /dev/null; then
    echo "❌ Docker n'est pas installé. Installez-le depuis https://docker.com"
    exit 1
fi

# Connexion à Heroku
echo "🔐 Connexion à Heroku..."
heroku auth:whoami || heroku login
heroku container:login

# Vérification de l'application
echo "🔍 Vérification de l'application Heroku..."
if ! heroku apps:info -a $APP_NAME &> /dev/null; then
    echo "❌ L'application $APP_NAME n'existe pas. Créez-la d'abord avec:"
    echo "   heroku create $APP_NAME"
    exit 1
fi

# Configuration de la stack container
echo "📦 Configuration de la stack container..."
heroku stack:set container -a $APP_NAME

# Build et push de l'image
echo "🔨 Build et push de l'image Docker..."
heroku container:push web -a $APP_NAME

# Release de l'application
echo "🚀 Release de l'application..."
heroku container:release web -a $APP_NAME

# Affichage des informations
echo "✅ Déploiement terminé !"
echo "🌐 URL de l'application: https://$APP_NAME.herokuapp.com"
echo "📊 Logs en temps réel: heroku logs --tail -a $APP_NAME"

# Ouverture optionnelle dans le navigateur
read -p "🌐 Voulez-vous ouvrir l'application dans votre navigateur ? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    heroku open -a $APP_NAME
fi

echo "📋 Commandes utiles:"
echo "   heroku logs --tail -a $APP_NAME     # Voir les logs en temps réel"
echo "   heroku ps -a $APP_NAME              # Voir le statut des dynos"
echo "   heroku config -a $APP_NAME          # Voir les variables d'environnement" 