# Schéma MongoDB EcoRide

## Vue d'ensemble des collections

Ce document détaille la structure des collections MongoDB utilisées dans l'application EcoRide.

| Collection | Description | Indexation | TTL |
|------------|-------------|------------|-----|
| preferences | Préférences utilisateurs | userId (unique) | Non |
| logs | Journaux d'activité système | timestamp, niveau+service, meta.userId | Oui (30j) |
| analytics | Statistiques d'utilisation | date+type | Non |
| geo_data | Données géospatiales | geometry (2dsphere), covoiturageId | Non |
| configurations | Paramètres dynamiques | code (unique) | Non |

## Détail des collections

### Collection `preferences`

Stocke les préférences et paramètres personnalisés des utilisateurs.

#### Schéma de validation
```js
{
  bsonType: "object",
  required: ["userId", "standard"],
  properties: {
    userId: { bsonType: "int" },
    standard: {
      bsonType: "object",
      properties: {
        musique: { bsonType: "string" },
        animaux: { bsonType: "bool" },
        fumeur: { bsonType: "bool" },
        climatisation: { bsonType: "string" }
      }
    },
    custom: {
      bsonType: "array",
      items: {
        bsonType: "object",
        required: ["key", "value"],
        properties: {
          key: { bsonType: "string" },
          value: { bsonType: ["string", "bool", "int", "double"] }
        }
      }
    },
    lastUpdated: { bsonType: "date" }
  }
}
```

#### Indexation
```js
db.preferences.createIndex({ userId: 1 }, { unique: true });
```

### Collection `logs`

Stocke les journaux d'activité système et événements utilisateurs.

#### Schéma de validation
```js
{
  bsonType: "object",
  required: ["timestamp", "niveau", "service", "message"],
  properties: {
    timestamp: { bsonType: "date" },
    niveau: { 
      bsonType: "string",
      enum: ["info", "warn", "error", "fatal"] 
    },
    service: { bsonType: "string" },
    message: { bsonType: "string" },
    meta: { bsonType: "object" }
  }
}
```

#### Indexation
```js
// TTL index: suppression automatique après 30 jours
db.logs.createIndex({ timestamp: 1 }, { expireAfterSeconds: 2592000 });
// Index pour recherche par niveau et service
db.logs.createIndex({ niveau: 1, service: 1 });
// Index pour recherche par utilisateur
db.logs.createIndex({ "meta.userId": 1 });
```

### Collection `analytics`

Stocke les métriques et statistiques pré-calculées.

#### Schéma de validation
```js
{
  bsonType: "object",
  required: ["date", "type", "metrics"],
  properties: {
    date: { bsonType: "date" },
    type: { 
      bsonType: "string",
      enum: ["daily", "weekly", "monthly"] 
    },
    metrics: { bsonType: "object" },
    regionMetrics: { 
      bsonType: "array",
      items: {
        bsonType: "object",
        required: ["region"],
        properties: {
          region: { bsonType: "string" },
          covoituragesCrees: { bsonType: "int" },
          tauxRemplissage: { bsonType: "double" }
        }
      }
    }
  }
}
```

#### Indexation
```js
db.analytics.createIndex({ date: 1, type: 1 });
```

### Collection `geo_data`

Stocke les données géospatiales pour les itinéraires et recherches par proximité.

#### Schéma de validation
```js
{
  bsonType: "object",
  required: ["type", "geometry"],
  properties: {
    type: { 
      bsonType: "string",
      enum: ["itineraire", "point", "zone"] 
    },
    covoiturageId: { bsonType: "int" },
    geometry: { bsonType: "object" },
    metadata: { bsonType: "object" },
    points_interet: { 
      bsonType: "array",
      items: { bsonType: "object" }
    }
  }
}
```

#### Indexation
```js
// Index géospatial pour requêtes de proximité
db.geo_data.createIndex({ geometry: "2dsphere" });
// Index pour récupérer les données par covoiturage
db.geo_data.createIndex({ covoiturageId: 1 });
```

### Collection `configurations`

Stocke les paramètres dynamiques de l'application.

#### Schéma de validation
```js
{
  bsonType: "object",
  required: ["code", "valeur", "actif"],
  properties: {
    code: { bsonType: "string" },
    valeur: { bsonType: "object" },
    description: { bsonType: "string" },
    actif: { bsonType: "bool" },
    dateModification: { bsonType: "date" },
    modifiePar: { bsonType: "string" }
  }
}
```

#### Indexation
```js
db.configurations.createIndex({ code: 1 }, { unique: true });
```

## Exemples de documents

### Document `preferences`
```json
{
  "_id": ObjectId("..."),
  "userId": 1,
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
  "lastUpdated": ISODate("2023-06-15T14:30:00Z")
}
```

### Document `logs`
```json
{
  "_id": ObjectId("..."),
  "timestamp": ISODate("2023-06-15T10:25:30Z"),
  "niveau": "error",
  "service": "payment",
  "message": "Échec de paiement: carte refusée",
  "meta": {
    "userId": 2,
    "transactionId": "TX123456",
    "ipAddress": "192.168.1.1"
  }
}
```

### Document `analytics`
```json
{
  "_id": ObjectId("..."),
  "date": ISODate("2023-06-15T00:00:00Z"),
  "type": "daily",
  "metrics": {
    "nouveauxUtilisateurs": 25,
    "covoituragesCrees": 78,
    "covoituragesTermines": 65,
    "tauxRemplissage": 0.76,
    "economiesCO2": 356.2,
    "transactionsTotal": 1250.50
  },
  "regionMetrics": [
    {
      "region": "Paris",
      "covoituragesCrees": 42,
      "tauxRemplissage": 0.82
    },
    {
      "region": "Lyon",
      "covoituragesCrees": 18,
      "tauxRemplissage": 0.71
    }
  ]
}
```

### Document `geo_data`
```json
{
  "_id": ObjectId("..."),
  "type": "itineraire",
  "covoiturageId": 1,
  "geometry": {
    "type": "LineString",
    "coordinates": [
      [2.3522, 48.8566],
      [2.2950, 48.8738]
    ]
  },
  "metadata": {
    "distance": 5.2,
    "duree": 25,
    "created": ISODate("2023-06-15T08:30:00Z")
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

### Document `configurations`
```json
{
  "_id": ObjectId("..."),
  "code": "moderation.niveaux_alerte",
  "valeur": {
    "signalements_min": 3,
    "suspension_auto": 5,
    "intervalles_verification": 24
  },
  "description": "Configuration des niveaux d'alerte pour la modération",
  "actif": true,
  "dateModification": ISODate("2023-06-01T10:00:00Z"),
  "modifiePar": "admin"
}
``` 