#!/bin/bash
# Script d'initialisation de la base de données MongoDB pour EcoRide

# Affichage en couleur pour mieux distinguer les étapes
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== INITIALISATION DE LA BASE MONGODB ECORIDE ===${NC}"

# Variables d'environnement (à remplacer par les valeurs réelles)
MONGO_USERNAME="ShBZo1U37uJq9c8"
MONGO_PASSWORD='%3J(vVRk3Kma_6q}fck>3$,5YvbHygx=7=S{A()'
MONGO_DB="ecoride_nosql"
MONGO_CONTAINER="ecoride-app-mongodb-1"

# Étape 1: Vérification de l'instance MongoDB
echo -e "${BLUE}1. Vérification de l'instance MongoDB${NC}"
if docker exec -it $MONGO_CONTAINER mongosh --eval "db.adminCommand('ping')" > /dev/null; then
    echo -e "${GREEN}✅ Connexion à MongoDB réussie${NC}"
else
    echo -e "${RED}❌ Échec de connexion à MongoDB - Vérifiez que le conteneur est en cours d'exécution${NC}"
    exit 1
fi

# Étape 2: Création de la base de données
echo -e "${BLUE}2. Création de la base de données '$MONGO_DB'${NC}"
docker exec -it $MONGO_CONTAINER mongosh --eval "use $MONGO_DB"
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Base de données créée ou existante${NC}"
else
    echo -e "${RED}❌ Échec de création de la base de données${NC}"
    exit 1
fi

# Étape 3: Création des collections avec leurs schémas
echo -e "${BLUE}3. Création des collections et schémas${NC}"
docker exec -it $MONGO_CONTAINER mongosh "$MONGO_DB" --file "/app/backend/database/mongodb/schemas.js"
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Collections et schémas créés${NC}"
else
    echo -e "${RED}❌ Échec de création des collections et schémas${NC}"
    exit 1
fi

# Étape 4: Création des index
echo -e "${BLUE}4. Création des index${NC}"
docker exec -it $MONGO_CONTAINER mongosh "$MONGO_DB" --file "/app/backend/database/mongodb/indexes.js"
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Index créés${NC}"
else
    echo -e "${RED}❌ Échec de création des index${NC}"
    exit 1
fi

# Étape 5: Insertion des données de test
echo -e "${BLUE}5. Insertion des données de test${NC}"
docker exec -it $MONGO_CONTAINER mongosh "$MONGO_DB" --file "/app/backend/database/mongodb/sample_data_upsert.js"
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Données de test insérées${NC}"
else
    echo -e "${RED}❌ Échec d'insertion des données de test${NC}"
    exit 1
fi

# Étape 6: Vérification par requêtes
echo -e "${BLUE}6. Vérification par requêtes${NC}"
docker exec -it $MONGO_CONTAINER mongosh "$MONGO_DB" --file "/app/backend/database/mongodb/test_setup.js"
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Tests réussis${NC}"
else
    echo -e "${RED}❌ Échec des tests${NC}"
    exit 1
fi

echo -e "${GREEN}=== INITIALISATION TERMINÉE AVEC SUCCÈS ===${NC}"
echo -e "Bases: MongoDB $MONGO_DB"
echo -e "Utilisateur: $MONGO_USERNAME"
echo -e "Collections créées: preferences, logs, analytics, geo_data, configurations, reviews"
echo -e "${BLUE}Pour explorer les données: http://localhost:8082/${NC}"

exit 0 