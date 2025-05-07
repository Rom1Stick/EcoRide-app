# Base de données NoSQL (MongoDB) - EcoRide

Ce document présente la structure des collections MongoDB utilisées dans l'application EcoRide pour compléter la base de données relationnelle MySQL existante.

## Architecture hybride SQL/NoSQL

EcoRide utilise une architecture de données hybride :

- **MySQL** : Stockage des données structurées et transactionnelles (utilisateurs, covoiturages, transactions financières)
- **MongoDB** : Stockage des données flexibles, volumineuses ou nécessitant des requêtes géospatiales

Cette architecture permet d'exploiter les forces de chaque technologie sans dupliquer les données.

## Collections MongoDB

### 1. Collection `preferences`

Stocke les préférences des utilisateurs avec une structure flexible.

**Structure** :
```json
{
  "_id": ObjectId("..."),
  "userId": 123,
  "standard": {
    "musique": "jazz",
    "animaux": true,
    "fumeur": false,
    "climatisation": "auto"
  },
  "custom": [
    { "key": "conversation", "value": "modéré" },
    { "key": "arrets", "value": false }
  ],
  "lastUpdated": ISODate("...")
}
```

**Utilisation** : Profil utilisateur, personnalisation de l'interface, filtres de recherche

### 2. Collection `logs`

Enregistre les événements système et les actions utilisateur.

**Structure** :
```json
{
  "_id": ObjectId("..."),
  "timestamp": ISODate("..."),
  "niveau": "error",
  "service": "payment",
  "message": "Échec de paiement: carte refusée",
  "meta": {
    "userId": 2,
    "transactionId": "TX123456"
  }
}
```

**Utilisation** : Débogage, audit, suivi des erreurs, analyse des problèmes

### 3. Collection `analytics`

Stocke les statistiques d'utilisation pré-calculées.

**Structure** :
```json
{
  "_id": ObjectId("..."),
  "date": ISODate("..."),
  "type": "daily",
  "metrics": {
    "nouveauxUtilisateurs": 25,
    "covoituragesCrees": 78,
    "tauxRemplissage": 0.76,
    "economiesCO2": 356.2
  },
  "regionMetrics": [
    {
      "region": "Paris",
      "covoituragesCrees": 42,
      "tauxRemplissage": 0.82
    }
  ]
}
```

**Utilisation** : Tableaux de bord, rapports, suivi des performances

### 4. Collection `geo_data`

Stocke les données géospatiales pour les itinéraires et les recherches de proximité.

**Structure** :
```json
{
  "_id": ObjectId("..."),
  "type": "itineraire",
  "covoiturageId": 789,
  "geometry": {
    "type": "LineString",
    "coordinates": [
      [2.3522, 48.8566],
      [2.3881, 48.8431]
    ]
  },
  "metadata": {
    "distance": 8.5,
    "duree": 25,
    "created": ISODate("...")
  },
  "points_interet": [
    {
      "position": [2.3343, 48.8638],
      "type": "arret",
      "nom": "Place de la Concorde"
    }
  ]
}
```

**Utilisation** : Recherche de trajets par proximité, calcul d'itinéraires, visualisation de carte

### 5. Collection `configurations`

Stocke les paramètres dynamiques et configurables de l'application.

**Structure** :
```json
{
  "_id": ObjectId("..."),
  "code": "moderation.niveaux_alerte",
  "valeur": {
    "signalements_min": 3,
    "suspension_auto": 5
  },
  "description": "Configuration des niveaux d'alerte pour la modération",
  "actif": true,
  "dateModification": ISODate("..."),
  "modifiePar": "admin"
}
```

**Utilisation** : Paramètres dynamiques, fonctionnalités expérimentales, A/B testing

## Optimisations

### Indexation
Chaque collection dispose d'index optimisés pour les requêtes fréquentes :

- `preferences` : Index unique sur `userId`
- `logs` : Index TTL sur `timestamp` (suppression auto après 30 jours), index sur `niveau` et `service`
- `analytics` : Index sur `date` et `type`
- `geo_data` : Index géospatial 2dsphere sur `geometry`, index sur `covoiturageId`
- `configurations` : Index unique sur `code`

### Validation de schéma
Chaque collection utilise un schéma JSON pour valider les données tout en permettant la flexibilité.

## Fichiers disponibles

1. `database/mongodb/schemas.js` : Définitions des collections et schémas
2. `database/mongodb/indexes.js` : Création des index pour l'optimisation
3. `database/mongodb/sample_data.js` : Exemples de données pour chaque collection
4. `database/mongodb/queries.js` : Exemples de requêtes utiles

## Intégration avec MySQL

La cohérence entre MySQL et MongoDB est assurée par :

1. Les références d'ID (`userId` → `utilisateur_id`, `covoiturageId` → `covoiturage_id`)
2. La synchronisation lors des opérations (création/suppression d'utilisateurs, etc.)
3. L'absence de duplication des données essentielles

## Écoconception

Cette architecture respecte les principes d'écoconception par :

1. La suppression automatique des données obsolètes (TTL index)
2. L'optimisation du stockage (pas de duplication)
3. L'indexation ciblée pour des requêtes efficientes
4. Le pré-calcul des métriques pour réduire la charge serveur 