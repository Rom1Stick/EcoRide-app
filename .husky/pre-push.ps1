# PowerShell script pour pre-push
Write-Host "Exécution du hook pre-push PowerShell..."

# Exécuter le build avant de pousser les modifications
npm run build 