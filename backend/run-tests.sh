#!/bin/bash

# Script pour exécuter les tests unitaires et fonctionnels

# Couleurs pour les messages
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction pour afficher un message d'aide
function show_help {
    echo -e "${BLUE}Usage: $0 [options]${NC}"
    echo ""
    echo "Options:"
    echo "  -h, --help           Affiche ce message d'aide"
    echo "  -u, --unit           Exécute uniquement les tests unitaires"
    echo "  -f, --feature        Exécute uniquement les tests fonctionnels"
    echo "  -c, --coverage       Génère un rapport de couverture de code"
    echo "  -t, --testdox        Affiche les résultats en format testdox"
    echo ""
    echo "Examples:"
    echo "  $0                   Exécute tous les tests"
    echo "  $0 --unit --coverage Exécute les tests unitaires avec rapport de couverture"
    echo "  $0 --feature         Exécute les tests fonctionnels"
}

# Variables par défaut
RUN_UNIT=true
RUN_FEATURE=true
COVERAGE=false
TESTDOX=false

# Analyser les arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -h|--help)
            show_help
            exit 0
            ;;
        -u|--unit)
            RUN_UNIT=true
            RUN_FEATURE=false
            shift
            ;;
        -f|--feature)
            RUN_UNIT=false
            RUN_FEATURE=true
            shift
            ;;
        -c|--coverage)
            COVERAGE=true
            shift
            ;;
        -t|--testdox)
            TESTDOX=true
            shift
            ;;
        *)
            echo -e "${RED}Option inconnue: $1${NC}"
            show_help
            exit 1
            ;;
    esac
done

# Préparer la commande de base
cmd="docker-compose run --rm tests ./vendor/bin/phpunit -c config/phpunit.xml"

# Ajouter les options
if [ "$TESTDOX" = true ]; then
    cmd="$cmd --testdox"
fi

if [ "$COVERAGE" = true ]; then
    cmd="$cmd --coverage-html ./tests/coverage"
fi

# Ajouter les suites de tests à exécuter
if [ "$RUN_UNIT" = true ] && [ "$RUN_FEATURE" = false ]; then
    cmd="$cmd --testsuite Unit"
elif [ "$RUN_UNIT" = false ] && [ "$RUN_FEATURE" = true ]; then
    cmd="$cmd --testsuite Feature"
fi

# Afficher la commande
echo -e "${BLUE}Exécution de: ${cmd}${NC}"

# Exécuter la commande
eval $cmd

# Vérifier le résultat
if [ $? -eq 0 ]; then
    echo -e "${GREEN}Tests réussis!${NC}"
    exit 0
else
    echo -e "${RED}Des tests ont échoué.${NC}"
    exit 1
fi 