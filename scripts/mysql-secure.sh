#!/bin/bash

# Script pour exécuter des commandes MySQL de manière sécurisée
# Utilise un fichier de configuration pour les identifiants au lieu de les passer en ligne de commande

# Chemin vers le fichier de configuration MySQL
CONFIG_FILE="../backend/database/config/mysql-credentials.cnf"

# Fonction pour exécuter une requête SQL
run_query() {
    local query="$1"
    docker exec -i ecoride-app-mysql-1 mysql --defaults-extra-file=/tmp/mysql-credentials.cnf -e "$query"
}

# Fonction pour exécuter un fichier SQL
run_sql_file() {
    local file="$1"
    docker exec -i ecoride-app-mysql-1 mysql --defaults-extra-file=/tmp/mysql-credentials.cnf < "$file"
}

# Fonction pour tester la distance Paris-Marseille
test_distance() {
    docker exec -i ecoride-app-mysql-1 mysql --defaults-extra-file=/tmp/mysql-credentials.cnf -e "SELECT calculer_distance_km(48.8566, 2.3522, 43.2965, 5.3698) AS distance_paris_marseille;"
}

# Fonction pour exécuter un benchmark
run_benchmark() {
    local function_name="$1"
    local param="$2"
    local iterations="$3"
    local label="$4"
    
    docker exec -i ecoride-app-mysql-1 mysql --defaults-extra-file=/tmp/mysql-credentials.cnf -e "SELECT BENCHMARK($iterations, $function_name($param)) AS test_perf_$label, NOW() as fin_test;"
}

# Copier le fichier de configuration dans le conteneur MySQL
docker cp "$CONFIG_FILE" ecoride-app-mysql-1:/tmp/mysql-credentials.cnf

# Vérifier si un argument a été passé
if [ $# -eq 0 ]; then
    echo "Usage: $0 [query|file|distance|benchmark]"
    exit 1
fi

# Exécuter la commande en fonction de l'argument
case "$1" in
    query)
        if [ -z "$2" ]; then
            echo "Veuillez spécifier une requête SQL."
            exit 1
        fi
        run_query "$2"
        ;;
    file)
        if [ -z "$2" ]; then
            echo "Veuillez spécifier un fichier SQL."
            exit 1
        fi
        run_sql_file "$2"
        ;;
    distance)
        test_distance
        ;;
    benchmark)
        if [ -z "$2" ] || [ -z "$3" ] || [ -z "$4" ] || [ -z "$5" ]; then
            echo "Usage: $0 benchmark <function_name> <param> <iterations> <label>"
            exit 1
        fi
        run_benchmark "$2" "$3" "$4" "$5"
        ;;
    *)
        echo "Commande non reconnue. Utilisez: query, file, distance ou benchmark."
        exit 1
        ;;
esac

exit 0 