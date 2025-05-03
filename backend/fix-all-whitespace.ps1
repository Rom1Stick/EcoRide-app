# Script PowerShell pour supprimer les espaces en fin de ligne dans tous les fichiers PHP
Write-Host "Recherche de tous les fichiers PHP dans le répertoire app..."

# Récupérer tous les fichiers PHP
$files = Get-ChildItem -Path "app" -Recurse -Filter "*.php" | Select-Object -ExpandProperty FullName

Write-Host "Nombre de fichiers trouvés: $($files.Count)"

foreach ($file in $files) {
    Write-Host "Traitement du fichier: $file"
    
    # Lire le contenu du fichier
    $content = Get-Content -Path $file -Raw
    
    # Vérifier si le fichier a des espaces en fin de ligne
    if ($content -match "[ \t]+`r`n") {
        # Remplacer les espaces en fin de ligne
        $newContent = $content -replace "[ \t]+`r`n", "`r`n"
        
        # Écrire le contenu modifié
        Set-Content -Path $file -Value $newContent -NoNewLine
        Add-Content -Path $file -Value ""
        
        Write-Host "Espaces en fin de ligne supprimés dans: $file" -ForegroundColor Green
    } else {
        Write-Host "Aucun espace en fin de ligne trouvé dans: $file" -ForegroundColor Yellow
    }
}

Write-Host "Traitement terminé!" -ForegroundColor Green 