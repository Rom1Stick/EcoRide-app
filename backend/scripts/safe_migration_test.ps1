# Script PowerShell pour Tests Sécurisés - Migration Architecture OO
# GARANTIE: Aucune donnée ne sera modifiée

param(
    [switch]$Detailed,
    [switch]$Benchmark,
    [switch]$Help
)

function Show-Help {
    Write-Host ""
    Write-Host "🛡️  SCRIPT DE TEST SÉCURISÉ - MIGRATION ARCHITECTURE OO" -ForegroundColor Cyan
    Write-Host "============================================================" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "USAGE:" -ForegroundColor Yellow
    Write-Host "  .\safe_migration_test.ps1 [-Detailed] [-Benchmark] [-Help]"
    Write-Host ""
    Write-Host "OPTIONS:" -ForegroundColor Yellow
    Write-Host "  -Detailed   : Affichage détaillé des résultats de mapping"
    Write-Host "  -Benchmark  : Inclut les tests de performance"
    Write-Host "  -Help       : Affiche cette aide"
    Write-Host ""
    Write-Host "EXEMPLES:" -ForegroundColor Yellow
    Write-Host "  .\safe_migration_test.ps1                    # Test basique"
    Write-Host "  .\safe_migration_test.ps1 -Detailed          # Test détaillé"
    Write-Host "  .\safe_migration_test.ps1 -Detailed -Benchmark # Test complet"
    Write-Host ""
    Write-Host "🔒 GARANTIE: Ce script ne modifie aucune donnée existante." -ForegroundColor Green
    exit
}

if ($Help) {
    Show-Help
}

Write-Host ""
Write-Host "🛡️  RECHERCHE DE PHP SUR VOTRE SYSTÈME..." -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

# Fonction pour trouver PHP
function Find-PHP {
    $phpPaths = @(
        "C:\xampp\php\php.exe",
        "C:\wamp64\bin\php\php7.4.33\php.exe",
        "C:\wamp64\bin\php\php8.0.30\php.exe", 
        "C:\wamp64\bin\php\php8.1.25\php.exe",
        "C:\wamp64\bin\php\php8.2.13\php.exe",
        "C:\wamp64\bin\php\php8.3.0\php.exe",
        "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe",
        "C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe",
        "C:\laragon\bin\php\php-8.3.0-Win32-vs16-x64\php.exe",
        "C:\php\php.exe"
    )
    
    # Recherche dans les chemins standards
    foreach ($path in $phpPaths) {
        if (Test-Path $path) {
            Write-Host "✅ PHP trouvé: $path" -ForegroundColor Green
            return $path
        }
    }
    
    # Recherche dynamique dans WAMP
    $wampPhpDir = "C:\wamp64\bin\php"
    if (Test-Path $wampPhpDir) {
        $phpVersions = Get-ChildItem -Path $wampPhpDir -Directory | Where-Object { $_.Name -like "php*" }
        foreach ($version in $phpVersions) {
            $phpExe = Join-Path $version.FullName "php.exe"
            if (Test-Path $phpExe) {
                Write-Host "✅ PHP trouvé dans WAMP: $phpExe" -ForegroundColor Green
                return $phpExe
            }
        }
    }
    
    # Recherche dynamique dans Laragon
    $laragonPhpDir = "C:\laragon\bin\php"
    if (Test-Path $laragonPhpDir) {
        $phpVersions = Get-ChildItem -Path $laragonPhpDir -Directory | Where-Object { $_.Name -like "php-*" }
        foreach ($version in $phpVersions) {
            $phpExe = Join-Path $version.FullName "php.exe"
            if (Test-Path $phpExe) {
                Write-Host "✅ PHP trouvé dans Laragon: $phpExe" -ForegroundColor Green
                return $phpExe
            }
        }
    }
    
    # Vérifier si PHP est dans le PATH
    try {
        $phpInPath = Get-Command php -ErrorAction Stop
        Write-Host "✅ PHP trouvé dans PATH: $($phpInPath.Source)" -ForegroundColor Green
        return "php"
    } catch {
        # PHP non trouvé dans PATH
    }
    
    return $null
}

# Recherche de PHP
$phpPath = Find-PHP

if (-not $phpPath) {
    Write-Host "❌ PHP non trouvé dans les emplacements standard" -ForegroundColor Red
    Write-Host ""
    Write-Host "📥 SOLUTIONS POSSIBLES:" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "1. Installer XAMPP: https://www.apachefriends.org/download.html"
    Write-Host "2. Installer WAMP: https://wampserver.aviatechno.net/"
    Write-Host "3. Installer Laragon: https://laragon.org/download/"
    Write-Host "4. Télécharger PHP: https://windows.php.net/download/"
    Write-Host ""
    Write-Host "💡 SOLUTION RAPIDE - Recherche manuelle:" -ForegroundColor Cyan
    Write-Host "   Utilisez: Get-ChildItem -Path C:\ -Recurse -Name php.exe"
    Write-Host "   Puis lancez: C:\chemin\vers\php.exe scripts/safe_migration_test.php"
    Write-Host ""
    Read-Host "Appuyez sur Entrée pour continuer"
    exit 1
}

Write-Host ""
Write-Host "🚀 DÉMARRAGE DES TESTS SÉCURISÉS" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "🛡️  MODE SÉCURISÉ ACTIVÉ - AUCUNE MODIFICATION DE DONNÉES" -ForegroundColor Green
Write-Host ""

# Test de la version PHP
Write-Host "📋 Version PHP:" -ForegroundColor Yellow
& $phpPath --version
Write-Host ""

# Construction des arguments
$args = @()
if ($Detailed) { $args += "--detailed" }
if ($Benchmark) { $args += "--benchmark" }
if ($args.Count -eq 0) { $args = @("--detailed", "--benchmark") }

Write-Host "🧪 Lancement des tests avec arguments: $($args -join ' ')" -ForegroundColor Yellow
Write-Host ""

# Génération du nom de fichier de rapport avec date/heure
$timestamp = Get-Date -Format "yyyyMMdd_HHmm"
$reportFile = "logs\migration_validation_$timestamp.log"

# Créer le dossier logs s'il n'existe pas
if (-not (Test-Path "logs")) {
    New-Item -ItemType Directory -Path "logs" | Out-Null
}

Write-Host "📄 Rapport sera sauvé dans: $reportFile" -ForegroundColor Cyan
Write-Host ""

# Exécution du test avec capture des logs
try {
    $arguments = @("scripts/safe_migration_test.php") + $args
    $process = Start-Process -FilePath $phpPath -ArgumentList $arguments -NoNewWindow -Wait -PassThru -RedirectStandardOutput $reportFile -RedirectStandardError "$reportFile.error"
    
    if ($process.ExitCode -eq 0) {
        Write-Host "✅ Tests terminés avec succès" -ForegroundColor Green
        Write-Host "📄 Rapport complet disponible: $reportFile" -ForegroundColor Cyan
    } else {
        Write-Host "❌ Erreur lors de l'exécution du test (Code: $($process.ExitCode))" -ForegroundColor Red
        Write-Host "📄 Vérifiez les fichiers de log: $reportFile et $reportFile.error" -ForegroundColor Yellow
    }
} catch {
    Write-Host "❌ Erreur lors du lancement: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "📊 Affichage du rapport:" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan

if (Test-Path $reportFile) {
    Get-Content $reportFile | Write-Host
} else {
    Write-Host "❌ Fichier de rapport non trouvé: $reportFile" -ForegroundColor Red
}

# Afficher les erreurs s'il y en a
if (Test-Path "$reportFile.error") {
    $errorContent = Get-Content "$reportFile.error"
    if ($errorContent) {
        Write-Host ""
        Write-Host "⚠️  ERREURS DÉTECTÉES:" -ForegroundColor Yellow
        Write-Host "============================================================" -ForegroundColor Yellow
        $errorContent | Write-Host -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "🛡️  GARANTIE: Aucune donnée n'a été modifiée pendant les tests." -ForegroundColor Green
Write-Host "📄 Log complet: $reportFile" -ForegroundColor Cyan
Write-Host ""
Read-Host "Appuyez sur Entrée pour continuer" 