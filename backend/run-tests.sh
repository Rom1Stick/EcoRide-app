#!/bin/bash

# Script pour exécuter les tests d'intégration de l'application EcoRide

# Couleurs pour les messages
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== Tests d'intégration EcoRide ===${NC}"
echo ""

# Vérifier que MySQL est disponible
echo -e "${YELLOW}Vérification de MySQL...${NC}"
mysql --version > /dev/null 2>&1
if [ $? -ne 0 ]; then
    echo -e "${RED}MySQL n'est pas disponible. Veuillez installer MySQL ou démarrer le service.${NC}"
    exit 1
fi
echo -e "${GREEN}MySQL est disponible.${NC}"
echo ""

# Vérifier que les dépendances sont installées
echo -e "${YELLOW}Vérification des dépendances...${NC}"
if [ ! -d "vendor" ]; then
    echo -e "${YELLOW}Installation des dépendances...${NC}"
    composer install
    if [ $? -ne 0 ]; then
        echo -e "${RED}Impossible d'installer les dépendances.${NC}"
        exit 1
    fi
fi
echo -e "${GREEN}Les dépendances sont installées.${NC}"
echo ""

# Configurer la base de données de test
echo -e "${YELLOW}Configuration de la base de données de test...${NC}"
mysql -u root < database/scripts/setup_test_db.sql
if [ $? -ne 0 ]; then
    echo -e "${RED}Impossible de configurer la base de données de test.${NC}"
    echo -e "${YELLOW}Essai avec un mot de passe...${NC}"
    read -s -p "Entrez le mot de passe root MySQL: " rootpw
    echo ""
    mysql -u root -p"$rootpw" < database/scripts/setup_test_db.sql
    if [ $? -ne 0 ]; then
        echo -e "${RED}Impossible de configurer la base de données de test.${NC}"
        exit 1
    fi
fi
echo -e "${GREEN}Base de données de test configurée.${NC}"
echo ""

# Exécuter les tests
echo -e "${YELLOW}Exécution des tests d'intégration...${NC}"
if [ -z "$1" ]; then
    # Exécuter tous les tests d'intégration si aucun argument n'est fourni
    vendor/bin/phpunit --testsuite Integration --colors=always
else
    # Exécuter un test spécifique
    vendor/bin/phpunit --filter $1 --colors=always
fi

# Vérifier le résultat des tests
if [ $? -ne 0 ]; then
    echo -e "${RED}Des tests ont échoué.${NC}"
    exit 1
else
    echo -e "${GREEN}Tous les tests ont réussi.${NC}"
fi

echo ""
echo -e "${BLUE}=== Fin des tests d'intégration ===${NC}" 