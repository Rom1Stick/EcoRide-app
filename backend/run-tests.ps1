# Script PowerShell pour exécuter les tests d'intégration EcoRide sous Windows

# Couleurs pour les messages
$GREEN = [ConsoleColor]::Green
$RED = [ConsoleColor]::Red
$BLUE = [ConsoleColor]::Blue
$YELLOW = [ConsoleColor]::Yellow

Write-Host "=== Tests d'intégration EcoRide ===" -ForegroundColor $BLUE
Write-Host ""

# Vérifier que MySQL est disponible
Write-Host "Vérification de MySQL..." -ForegroundColor $YELLOW
try {
    $mysqlPath = (Get-Command mysql -ErrorAction Stop).Source
    Write-Host "MySQL est disponible." -ForegroundColor $GREEN
} catch {
    Write-Host "MySQL n'est pas disponible. Veuillez installer MySQL ou vous assurer qu'il est dans votre PATH." -ForegroundColor $RED
    exit 1
}
Write-Host ""

# Vérifier que les dépendances sont installées
Write-Host "Vérification des dépendances..." -ForegroundColor $YELLOW
if (-not (Test-Path -Path "vendor")) {
    Write-Host "Installation des dépendances..." -ForegroundColor $YELLOW
    try {
        composer install
    } catch {
        Write-Host "Impossible d'installer les dépendances." -ForegroundColor $RED
        exit 1
    }
}
Write-Host "Les dépendances sont installées." -ForegroundColor $GREEN
Write-Host ""

# Configurer la base de données de test
Write-Host "Configuration de la base de données de test..." -ForegroundColor $YELLOW
try {
    # Essayer d'abord sans mot de passe
    $setupScript = Get-Content -Path "database/scripts/setup_test_db.sql" -Raw
    mysql -u root -e "$setupScript" 2>$null
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Essai avec un mot de passe..." -ForegroundColor $YELLOW
        $rootpw = Read-Host "Entrez le mot de passe root MySQL" -AsSecureString
        $BSTR = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($rootpw)
        $plainPassword = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto($BSTR)
        [System.Runtime.InteropServices.Marshal]::ZeroFreeBSTR($BSTR)
        
        mysql -u root -p"$plainPassword" -e "$setupScript" 2>$null
        if ($LASTEXITCODE -ne 0) {
            Write-Host "Impossible de configurer la base de données de test." -ForegroundColor $RED
            exit 1
        }
    }
    Write-Host "Base de données de test configurée." -ForegroundColor $GREEN
} catch {
    Write-Host "Erreur lors de la configuration de la base de données: $_" -ForegroundColor $RED
    exit 1
}
Write-Host ""

# Exécuter les tests
Write-Host "Exécution des tests d'intégration..." -ForegroundColor $YELLOW
try {
    if ($args.Count -eq 0) {
        # Exécuter tous les tests d'intégration si aucun argument n'est fourni
        & vendor/bin/phpunit --testsuite Integration --colors=always
    } else {
        # Exécuter un test spécifique
        & vendor/bin/phpunit --filter $args[0] --colors=always
    }
    
    # Vérifier le résultat des tests
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Des tests ont échoué." -ForegroundColor $RED
        exit 1
    } else {
        Write-Host "Tous les tests ont réussi." -ForegroundColor $GREEN
    }
} catch {
    Write-Host "Erreur lors de l'exécution des tests: $_" -ForegroundColor $RED
    exit 1
}

Write-Host ""
Write-Host "=== Fin des tests d'intégration ===" -ForegroundColor $BLUE 