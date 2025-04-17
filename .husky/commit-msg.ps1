# PowerShell script pour commit-msg
Write-Host "Exécution du hook commit-msg PowerShell..."

# Le chemin du fichier commit message est passé comme premier argument
$commitMsgFile = $args[0]

# Exécution de commitlint
npx --no -- commitlint --edit $commitMsgFile 