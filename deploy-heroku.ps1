#!/usr/bin/env pwsh
# Script de déploiement simplifié pour Heroku

Write-Host "Déploiement de l'application EcoRide sur Heroku..." -ForegroundColor Green

# Nom de l'application Heroku
$appName = "ecoride-frontend-alex"
Write-Host "Application Heroku cible: $appName" -ForegroundColor Yellow

# 1. Construire le frontend
Write-Host "Construction du frontend..." -ForegroundColor Yellow
cd frontend
npm ci
npm run build
cd ..

# 2. Configurer l'application pour utiliser des conteneurs
Write-Host "Configuration du stack container..." -ForegroundColor Yellow
heroku stack:set container --app $appName

# 3. Déployer sur Heroku
Write-Host "Déploiement sur Heroku..." -ForegroundColor Yellow
git add .
git commit -m "Déploiement sur Heroku" --no-verify
git push heroku HEAD:main -f

# 4. Ouvrir l'application dans le navigateur
Write-Host "Ouverture de l'application dans le navigateur..." -ForegroundColor Yellow
heroku open --app $appName

Write-Host "Déploiement terminé !" -ForegroundColor Green 