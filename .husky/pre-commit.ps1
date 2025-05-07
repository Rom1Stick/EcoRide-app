# PowerShell script pour pre-commit
Write-Host "Exécution du hook pre-commit PowerShell..."

# Exécuter les outils de la racine du projet
npm run lint

# Exécuter lint-staged à la place des commandes de format individuelles
npm run lint-staged

# Succès
exit 0 