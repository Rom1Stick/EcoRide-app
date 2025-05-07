# Script PowerShell d'initialisation de la base de données MongoDB pour EcoRide

# Fonction pour afficher des messages colorés
function Write-ColorOutput($ForegroundColor) {
    $fc = $host.UI.RawUI.ForegroundColor
    $host.UI.RawUI.ForegroundColor = $ForegroundColor
    if ($args) {
        Write-Output $args
    } else {
        $input | Write-Output
    }
    $host.UI.RawUI.ForegroundColor = $fc
}

Write-ColorOutput Blue "=== INITIALISATION DE LA BASE MONGODB ECORIDE ==="

# Variables d'environnement (à remplacer par les valeurs réelles)
$MONGO_USERNAME = "ShBZo1U37uJq9c8"
$MONGO_PASSWORD = '%3J(vVRk3Kma_6q}fck>3$,5YvbHygx=7=S{A()'
$MONGO_DB = "ecoride_nosql"
$MONGO_CONTAINER = "ecoride-app-mongodb-1"

# Étape 1: Vérification de l'instance MongoDB
Write-ColorOutput Blue "1. Vérification de l'instance MongoDB"
try {
    docker exec -it $MONGO_CONTAINER mongosh --eval "db.adminCommand('ping')" | Out-Null
    Write-ColorOutput Green "✅ Connexion à MongoDB réussie"
} catch {
    Write-ColorOutput Red "❌ Échec de connexion à MongoDB - Vérifiez que le conteneur est en cours d'exécution"
    exit 1
}

# Étape 2: Création de la base de données
Write-ColorOutput Blue "2. Création de la base de données '$MONGO_DB'"
try {
    docker exec -it $MONGO_CONTAINER mongosh --eval "use $MONGO_DB" | Out-Null
    Write-ColorOutput Green "✅ Base de données créée ou existante"
} catch {
    Write-ColorOutput Red "❌ Échec de création de la base de données"
    exit 1
}

# Étape 3: Création des collections avec leurs schémas
Write-ColorOutput Blue "3. Création des collections et schémas"
try {
    docker exec -it $MONGO_CONTAINER mongosh "$MONGO_DB" --file "/app/backend/database/mongodb/schemas.js" | Out-Null
    Write-ColorOutput Green "✅ Collections et schémas créés"
} catch {
    Write-ColorOutput Red "❌ Échec de création des collections et schémas"
    exit 1
}

# Étape 4: Création des index
Write-ColorOutput Blue "4. Création des index"
try {
    docker exec -it $MONGO_CONTAINER mongosh "$MONGO_DB" --file "/app/backend/database/mongodb/indexes.js" | Out-Null
    Write-ColorOutput Green "✅ Index créés"
} catch {
    Write-ColorOutput Red "❌ Échec de création des index"
    exit 1
}

# Étape 5: Insertion des données de test
Write-ColorOutput Blue "5. Insertion des données de test"
try {
    docker exec -it $MONGO_CONTAINER mongosh "$MONGO_DB" --file "/app/backend/database/mongodb/sample_data_upsert.js" | Out-Null
    Write-ColorOutput Green "✅ Données de test insérées"
} catch {
    Write-ColorOutput Red "❌ Échec d'insertion des données de test"
    exit 1
}

# Étape 6: Vérification par requêtes
Write-ColorOutput Blue "6. Vérification par requêtes"
try {
    docker exec -it $MONGO_CONTAINER mongosh "$MONGO_DB" --file "/app/backend/database/mongodb/test_setup.js" | Out-Null
    Write-ColorOutput Green "✅ Tests réussis"
} catch {
    Write-ColorOutput Red "❌ Échec des tests"
    exit 1
}

Write-ColorOutput Green "=== INITIALISATION TERMINÉE AVEC SUCCÈS ==="
Write-Output "Bases: MongoDB $MONGO_DB"
Write-Output "Utilisateur: $MONGO_USERNAME"
Write-Output "Collections créées: preferences, logs, analytics, geo_data, configurations, reviews"
Write-ColorOutput Blue "Pour explorer les données: http://localhost:8082/"

exit 0 