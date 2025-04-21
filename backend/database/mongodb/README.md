# Configuration MongoDB - EcoRide

Ce document explique comment initialiser et vérifier la configuration MongoDB pour l'application EcoRide.

## Prérequis

- Docker et docker-compose installés
- Conteneur MongoDB en cours d'exécution
- Accès à MongoDB avec les identifiants appropriés

## Étapes d'initialisation

### 1. Création de la base de données et de l'utilisateur

```bash
# Se connecter à MongoDB en tant qu'administrateur
docker exec -it ecoride-app-mongodb-1 mongosh

# Créer la base de données
use ecoride_nosql

# Créer l'utilisateur dédié
db.createUser({
  user: "ecoride_app",
  pwd: "motdepassesecurise",
  roles: [
    { role: "readWrite", db: "ecoride_nosql" }
  ]
})

# Vérifier la création de l'utilisateur
db.getUsers()
```

### 2. Création des collections et schémas

Exécuter le script `schemas.js` pour créer toutes les collections avec leurs schémas de validation:

```bash
docker exec -it ecoride-app-mongodb-1 mongosh ecoride_nosql --file /app/backend/database/mongodb/schemas.js
```

Ce script crée les collections suivantes avec leurs schémas de validation:
- `preferences`: Préférences utilisateurs
- `logs`: Journaux d'activité
- `analytics`: Statistiques d'utilisation
- `geo_data`: Données géospatiales
- `configurations`: Paramètres dynamiques
- `reviews`: Avis des utilisateurs

### 3. Création des index

Exécuter le script `indexes.js` pour créer tous les index nécessaires:

```bash
docker exec -it ecoride-app-mongodb-1 mongosh ecoride_nosql --file /app/backend/database/mongodb/indexes.js
```

Index créés:
- `preferences`: Index unique sur `userId`
- `logs`: Index TTL sur `timestamp`, index sur `(niveau, service)`, index sur `meta.userId`
- `analytics`: Index sur `(date, type)`
- `geo_data`: Index géospatial 2dsphere sur `geometry`, index sur `covoiturageId`
- `configurations`: Index unique sur `code`
- `reviews`: Index sur `rideId`, `authorUserId`, index textuel sur `comment`, index composite sur `(rideId, rating)`

### 4. Insertion des données de test

Exécuter le script `sample_data.js` pour insérer des données de test:

```bash
docker exec -it ecoride-app-mongodb-1 mongosh ecoride_nosql --file /app/backend/database/mongodb/sample_data.js
```

### 5. Vérification de la configuration

Exécuter le script de test pour vérifier que tout est correctement configuré:

```bash
docker exec -it ecoride-app-mongodb-1 mongosh ecoride_nosql --file /app/backend/database/mongodb/test_setup.js
```

Ce script vérifie:
- L'existence de toutes les collections
- La présence de tous les index attendus
- La validité des schémas de validation
- La possibilité d'exécuter des requêtes de base

## Bonnes pratiques

### Idempotence

Tous les scripts sont conçus pour être idempotents, c'est-à-dire qu'ils peuvent être exécutés plusieurs fois sans effets secondaires.

- `createCollection()` échoue silencieusement si la collection existe déjà
- `createIndex()` ignore la création si l'index existe déjà
- Les insertions de données utilisent `insertMany()` qui peut être remplacé par des opérations `updateOne()` avec `upsert: true` pour éviter les doublons

### Exécution dans l'ordre

Pour une initialisation complète, exécuter les scripts dans cet ordre:
1. `schemas.js` - Création des collections et schémas
2. `indexes.js` - Création des index
3. `sample_data.js` - Insertion des données de test
4. `test_setup.js` - Vérification de la configuration

### Smoke-test rapide

Pour vérifier rapidement que la base MongoDB fonctionne correctement:

```bash
docker exec -it ecoride-app-mongodb-1 mongosh ecoride_nosql --eval "db.getCollectionNames()"
```

Cette commande doit renvoyer la liste de toutes les collections configurées.

## Requêtes utiles

Le fichier `queries.js` contient des exemples de requêtes pour chaque collection, qui peuvent être utilisées pour:
- Tester les performances des requêtes
- Servir de modèles pour l'implémentation du code d'accès aux données
- Explorer les fonctionnalités de MongoDB

Exemples de requêtes par collection:
- `preferences`: Lecture et mise à jour
- `logs`: Filtrage et agrégation
- `analytics`: Analyse statistique
- `geo_data`: Requêtes géospatiales
- `configurations`: Gestion des paramètres
- `reviews`: Recherche textuelle et agrégation 