# Script de test local Docker pour EcoRide (PowerShell)
# Usage: .\scripts\test-docker.ps1

$ErrorActionPreference = "Stop"

Write-Host "🧪 Test de l'image Docker EcoRide en local..." -ForegroundColor Green

# Variables
$ImageName = "ecoride-local"
$ContainerName = "ecoride-test"
$Port = 3000

# Cleanup des conteneurs/images existants
Write-Host "🧹 Nettoyage des containers existants..." -ForegroundColor Yellow
try {
    docker stop $ContainerName 2>$null
    docker rm $ContainerName 2>$null
    docker rmi $ImageName 2>$null
} catch {
    # Ignorer les erreurs de nettoyage
}

# Build de l'image
Write-Host "🔨 Build de l'image Docker..." -ForegroundColor Yellow
docker build -t $ImageName .

# Lancement du conteneur
Write-Host "🚀 Lancement du conteneur de test..." -ForegroundColor Yellow
docker run -d `
  --name $ContainerName `
  -p ${Port}:80 `
  -e PORT=80 `
  -e NODE_ENV=production `
  -e PHP_ENV=production `
  $ImageName

# Attendre que le conteneur démarre
Write-Host "⏳ Attente du démarrage..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

# Test de santé
Write-Host "🩺 Test de santé de l'application..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://localhost:$Port" -UseBasicParsing -TimeoutSec 5
    Write-Host "✅ Application accessible sur http://localhost:$Port" -ForegroundColor Green
    
    # Test de l'API
    try {
        $apiResponse = Invoke-WebRequest -Uri "http://localhost:$Port/api" -UseBasicParsing -TimeoutSec 5
        Write-Host "✅ API accessible sur http://localhost:$Port/api" -ForegroundColor Green
    } catch {
        Write-Host "⚠️  API non accessible (normal si pas d'endpoint racine)" -ForegroundColor Yellow
    }
} catch {
    Write-Host "❌ Application non accessible" -ForegroundColor Red
    docker logs $ContainerName
    exit 1
}

Write-Host "📊 Informations du conteneur:" -ForegroundColor Cyan
docker stats --no-stream $ContainerName

Write-Host "`n📋 Pour interagir avec le conteneur:" -ForegroundColor Yellow
Write-Host "   docker logs $ContainerName           # Voir les logs" -ForegroundColor White
Write-Host "   docker exec -it $ContainerName bash  # Se connecter au conteneur" -ForegroundColor White
Write-Host "   docker stop $ContainerName           # Arrêter le conteneur" -ForegroundColor White
Write-Host "   docker rm $ContainerName             # Supprimer le conteneur" -ForegroundColor White

Write-Host "`n🌐 Application de test disponible sur: http://localhost:$Port" -ForegroundColor Green
Write-Host "🛑 Pour arrêter: docker stop $ContainerName" -ForegroundColor Red 