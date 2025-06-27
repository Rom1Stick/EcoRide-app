@echo off
:: Script Windows pour Tests Sécurisés - Migration Architecture OO
:: GARANTIE: Aucune donnée ne sera modifiée

echo.
echo 🛡️  RECHERCHE DE PHP SUR VOTRE SYSTÈME...
echo ============================================================
echo.

:: Recherche de PHP dans les emplacements typiques Windows
set PHP_PATH=""

if exist "C:\xampp\php\php.exe" (
    set PHP_PATH="C:\xampp\php\php.exe"
    echo ✅ PHP trouvé dans XAMPP: %PHP_PATH%
    goto :run_tests
)

if exist "C:\wamp64\bin\php\php7.4.33\php.exe" (
    set PHP_PATH="C:\wamp64\bin\php\php7.4.33\php.exe"
    echo ✅ PHP trouvé dans WAMP: %PHP_PATH%
    goto :run_tests
)

if exist "C:\wamp64\bin\php\php8.0.30\php.exe" (
    set PHP_PATH="C:\wamp64\bin\php\php8.0.30\php.exe"
    echo ✅ PHP trouvé dans WAMP: %PHP_PATH%
    goto :run_tests
)

if exist "C:\wamp64\bin\php\php8.1.25\php.exe" (
    set PHP_PATH="C:\wamp64\bin\php\php8.1.25\php.exe"
    echo ✅ PHP trouvé dans WAMP: %PHP_PATH%
    goto :run_tests
)

if exist "C:\wamp64\bin\php\php8.2.13\php.exe" (
    set PHP_PATH="C:\wamp64\bin\php\php8.2.13\php.exe"
    echo ✅ PHP trouvé dans WAMP: %PHP_PATH%
    goto :run_tests
)

if exist "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe" (
    set PHP_PATH="C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe"
    echo ✅ PHP trouvé dans Laragon: %PHP_PATH%
    goto :run_tests
)

if exist "C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe" (
    set PHP_PATH="C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe"
    echo ✅ PHP trouvé dans Laragon: %PHP_PATH%
    goto :run_tests
)

if exist "C:\php\php.exe" (
    set PHP_PATH="C:\php\php.exe"
    echo ✅ PHP trouvé dans C:\php: %PHP_PATH%
    goto :run_tests
)

:: Si PHP n'est pas trouvé
echo ❌ PHP non trouvé dans les emplacements standard
echo.
echo 📥 SOLUTIONS POSSIBLES:
echo.
echo 1. Installer XAMPP: https://www.apachefriends.org/download.html
echo 2. Installer WAMP: https://wampserver.aviatechno.net/
echo 3. Installer Laragon: https://laragon.org/download/
echo 4. Télécharger PHP: https://windows.php.net/download/
echo.
echo 💡 SOLUTION RAPIDE - Recherche manuelle:
echo    Cherchez "php.exe" sur votre disque C: et notez le chemin complet
echo    Puis modifiez ce script avec le bon chemin.
echo.
pause
exit /b 1

:run_tests
echo.
echo 🚀 DÉMARRAGE DES TESTS SÉCURISÉS
echo ============================================================
echo 🛡️  MODE SÉCURISÉ ACTIVÉ - AUCUNE MODIFICATION DE DONNÉES
echo.

:: Test de la version PHP
echo 📋 Version PHP:
%PHP_PATH% --version
echo.

:: Arguments du script
set ARGS=%1 %2 %3
if "%ARGS%"=="" set ARGS=--detailed --benchmark

echo 🧪 Lancement des tests avec arguments: %ARGS%
echo.

:: Génération du nom de fichier de rapport avec date/heure
for /f "tokens=1-3 delims=/ " %%a in ('date /t') do (
    set DATE_STR=%%c%%b%%a
)
for /f "tokens=1-2 delims=: " %%a in ('time /t') do (
    set TIME_STR=%%a%%b
)
set REPORT_FILE=migration_validation_%DATE_STR%_%TIME_STR:.=%.log

:: Exécution du test avec capture des logs
echo 📄 Rapport sera sauvé dans: logs\%REPORT_FILE%
echo.

%PHP_PATH% scripts/safe_migration_test.php %ARGS% > logs\%REPORT_FILE% 2>&1
if errorlevel 1 (
    echo ❌ Erreur lors de l'exécution du test
    echo 📄 Vérifiez le fichier de log: logs\%REPORT_FILE%
) else (
    echo ✅ Tests terminés avec succès
    echo 📄 Rapport complet disponible: logs\%REPORT_FILE%
)

echo.
echo 📊 Affichage du rapport:
echo ============================================================
type logs\%REPORT_FILE%

echo.
echo ============================================================
echo 🛡️  GARANTIE: Aucune donnée n'a été modifiée pendant les tests.
echo 📄 Log complet: logs\%REPORT_FILE%
echo.
pause 