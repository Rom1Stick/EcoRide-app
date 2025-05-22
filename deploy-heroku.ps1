#!/usr/bin/env pwsh
# Script de déploiement pour Heroku

Write-Host "Déploiement de l'application EcoRide sur Heroku..." -ForegroundColor Green

# Vérifier que l'application Heroku existe
$appName = "ecoride-frontend-alex"
Write-Host "Vérification de l'application Heroku $appName..." -ForegroundColor Yellow

# 1. S'assurer que Heroku CLI est installé et que l'utilisateur est connecté
heroku auth:whoami
if ($LASTEXITCODE -ne 0) {
    Write-Host "Veuillez vous connecter à Heroku avec 'heroku login'" -ForegroundColor Red
    exit 1
}

# 2. Configurer l'application pour utiliser des conteneurs
heroku stack:set container --app $appName
if ($LASTEXITCODE -ne 0) {
    Write-Host "Erreur lors de la configuration du stack container" -ForegroundColor Red
    exit 1
}

# 3. Supprimer les anciens buildpacks si nécessaire
heroku buildpacks:clear --app $appName

# 4. Construire le frontend si nécessaire
Write-Host "Construction du frontend..." -ForegroundColor Yellow
if (-not (Test-Path "frontend/dist")) {
    cd frontend
    npm ci
    npm run build
    cd ..
}

# 5. Commit les modifications si nécessaire
Write-Host "Commit des modifications..." -ForegroundColor Yellow
git add .
git commit -m "Préparation pour déploiement Heroku" --no-verify

# 6. Pousser les modifications vers Heroku
Write-Host "Déploiement sur Heroku..." -ForegroundColor Yellow
git push heroku fix/Heroku:main -f

# 7. Ouvrir l'application dans le navigateur
Write-Host "Ouverture de l'application dans le navigateur..." -ForegroundColor Yellow
heroku open --app $appName

Write-Host "Déploiement terminé !" -ForegroundColor Green 