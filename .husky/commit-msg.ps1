#!/usr/bin/env pwsh
# PowerShell script pour le hook commit-msg

$ErrorActionPreference = "Stop"
Write-Host "Vérification du message de commit par commitlint..."

# Lancer commitlint sur le fichier de message passé en paramètre
npx --no -- commitlint --edit $args[0]

# Si commitlint échoue, il retourne un code d'erreur non-zéro
# qui est automatiquement propagé par PowerShell 