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

# Exécuter lint-staged pour ne traiter que les fichiers modifiés
npx lint-staged 