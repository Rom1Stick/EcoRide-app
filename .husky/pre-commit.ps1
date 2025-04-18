# PowerShell script pour pre-commit
Write-Host "Exécution du hook pre-commit PowerShell..."

# Exécuter les outils de la racine du projet
npm run lint
npm run format

# Exécuter les outils spécifiques à frontend/  
Push-Location frontend
npm run lint
npm run format
Pop-Location

# Exécution de lint-staged pour les fichiers modifiés uniquement
npm run lint-staged 