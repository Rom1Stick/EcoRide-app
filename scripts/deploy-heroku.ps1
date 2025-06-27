# Script de déploiement automatisé pour Heroku (PowerShell)
# Usage: .\scripts\deploy-heroku.ps1 [app-name]

param(
    [string]$AppName = "ecoride-application"
)

$ErrorActionPreference = "Stop"

Write-Host "🚀 Déploiement de l'application EcoRide sur Heroku..." -ForegroundColor Green
Write-Host "📱 Application: $AppName" -ForegroundColor Cyan

# Vérification des pré-requis
Write-Host "🔍 Vérification des pré-requis..." -ForegroundColor Yellow

# Vérifier Heroku CLI
try {
    heroku --version | Out-Null
} catch {
    Write-Host "❌ Heroku CLI n'est pas installé. Installez-le depuis https://devcenter.heroku.com/articles/heroku-cli" -ForegroundColor Red
    exit 1
}

# Vérifier Docker
try {
    docker --version | Out-Null
} catch {
    Write-Host "❌ Docker n'est pas installé. Installez-le depuis https://docker.com" -ForegroundColor Red
    exit 1
}

# Connexion à Heroku
Write-Host "🔐 Connexion à Heroku..." -ForegroundColor Yellow
try {
    heroku auth:whoami | Out-Null
} catch {
    heroku login
}
heroku container:login

# Vérification de l'application
Write-Host "🔍 Vérification de l'application Heroku..." -ForegroundColor Yellow
try {
    heroku apps:info -a $AppName | Out-Null
} catch {
    Write-Host "❌ L'application $AppName n'existe pas. Créez-la d'abord avec:" -ForegroundColor Red
    Write-Host "   heroku create $AppName" -ForegroundColor White
    exit 1
}

# Configuration de la stack container
Write-Host "📦 Configuration de la stack container..." -ForegroundColor Yellow
heroku stack:set container -a $AppName

# Build et push de l'image
Write-Host "🔨 Build et push de l'image Docker..." -ForegroundColor Yellow
heroku container:push web -a $AppName

# Release de l'application
Write-Host "🚀 Release de l'application..." -ForegroundColor Yellow
heroku container:release web -a $AppName

# Affichage des informations
Write-Host "✅ Déploiement terminé !" -ForegroundColor Green
Write-Host "🌐 URL de l'application: https://$AppName.herokuapp.com" -ForegroundColor Cyan
Write-Host "📊 Logs en temps réel: heroku logs --tail -a $AppName" -ForegroundColor Cyan

# Ouverture optionnelle dans le navigateur
$choice = Read-Host "🌐 Voulez-vous ouvrir l'application dans votre navigateur ? (y/N)"
if ($choice -match "^[Yy]$") {
    heroku open -a $AppName
}

Write-Host "`n📋 Commandes utiles:" -ForegroundColor Yellow
Write-Host "   heroku logs --tail -a $AppName     # Voir les logs en temps réel" -ForegroundColor White
Write-Host "   heroku ps -a $AppName              # Voir le statut des dynos" -ForegroundColor White
Write-Host "   heroku config -a $AppName          # Voir les variables d'environnement" -ForegroundColor White 