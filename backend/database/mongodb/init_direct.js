// Script d'initialisation directe de MongoDB pour EcoRide
// Ce script peut être exécuté directement dans le shell mongo

// Se connecter à la base de données ecoride_nosql
db = db.getSiblingDB('ecoride_nosql');

print("=== INITIALISATION DIRECTE DE MONGODB POUR ECORIDE ===");

// 1. Création des collections avec leurs schémas
print("1. Création des collections et schémas...");

// Collection preferences
db.createCollection("preferences", {
  validator: {
    $jsonSchema: {
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
  }
});

// Collection logs
db.createCollection("logs", {
  validator: {
    $jsonSchema: {
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
  }
});

// Collection analytics
db.createCollection("analytics", {
  validator: {
    $jsonSchema: {
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
  }
});

// Collection geo_data
db.createCollection("geo_data", {
  validator: {
    $jsonSchema: {
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
  }
});

// Collection configurations
db.createCollection("configurations", {
  validator: {
    $jsonSchema: {
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
  }
});

// Collection reviews
db.createCollection("reviews", {
  validator: {
    $jsonSchema: {
      bsonType: "object",
      required: ["rideId", "authorUserId", "rating", "date"],
      properties: {
        rideId:       { bsonType: "int",    description: "ID du covoiturage (SQL)" },
        authorUserId: { bsonType: "int",    description: "ID de l'auteur (SQL)" },
        rating:       { bsonType: "int",    minimum: 1, maximum: 5, description: "Note de 1 à 5" },
        comment:      { bsonType: "string", maxLength: 1000, description: "Commentaire (optionnel)" },
        date:         { bsonType: "date",   description: "Date de publication" },
        validated:    { bsonType: "bool",   description: "Modéré : true si validé", default: false },
        moderatedBy:  { bsonType: "string", description: "Pseudo ou ID de l'admin (optionnel)" },
        moderatedAt:  { bsonType: "date",   description: "Date de modération (optionnel)" }
      }
    }
  }
});

print("Collections créées avec succès !");

// 2. Création des index
print("2. Création des index...");

// Collection preferences
db.preferences.createIndex({ userId: 1 }, { unique: true });

// Collection logs
db.logs.createIndex({ timestamp: 1 }, { expireAfterSeconds: 2592000 });
db.logs.createIndex({ niveau: 1, service: 1 });
db.logs.createIndex({ "meta.userId": 1 });

// Collection analytics
db.analytics.createIndex({ date: 1, type: 1 });

// Collection geo_data
db.geo_data.createIndex({ geometry: "2dsphere" });
db.geo_data.createIndex({ covoiturageId: 1 });

// Collection configurations
db.configurations.createIndex({ code: 1 }, { unique: true });

// Collection reviews
db.reviews.createIndex({ rideId: 1 });
db.reviews.createIndex({ authorUserId: 1 });
db.reviews.createIndex({ comment: "text" });
db.reviews.createIndex({ rideId: 1, rating: -1 });

print("Index créés avec succès !");

// 3. Insertion des données d'exemple
print("3. Insertion des données d'exemple...");

// Preferences
const prefData = [
  {
    userId: 1,
    standard: {
      musique: "jazz",
      animaux: true,
      fumeur: false,
      climatisation: "auto"
    },
    custom: [
      { key: "conversation", value: "modéré" },
      { key: "arrets", value: false }
    ],
    lastUpdated: new Date()
  },
  {
    userId: 2,
    standard: {
      musique: "pop",
      animaux: false,
      fumeur: false,
      climatisation: "off"
    },
    custom: [
      { key: "bagages", value: "grand" }
    ],
    lastUpdated: new Date()
  }
];

prefData.forEach(pref => {
  db.preferences.updateOne(
    { userId: pref.userId },
    { $set: pref },
    { upsert: true }
  );
});

// Logs
db.logs.insertMany([
  {
    timestamp: new Date(),
    niveau: "info",
    service: "auth",
    message: "Connexion réussie",
    meta: {
      userId: 1,
      ipAddress: "192.168.1.10",
      browser: "Chrome"
    }
  },
  {
    timestamp: new Date(),
    niveau: "error",
    service: "payment",
    message: "Échec de paiement: carte refusée",
    meta: {
      userId: 2,
      transactionId: "TX123456"
    }
  }
]);

// Analytics
const analyticsDates = [
  new Date("2023-06-15"),
  new Date("2023-06-16")
];

db.analytics.updateOne(
  { date: analyticsDates[0], type: "daily" },
  { 
    $set: {
      metrics: {
        nouveauxUtilisateurs: 25,
        covoituragesCrees: 78,
        covoituragesTermines: 65,
        tauxRemplissage: 0.76,
        economiesCO2: 356.2,
        transactionsTotal: 1250.50
      },
      regionMetrics: [
        {
          region: "Paris",
          covoituragesCrees: 42,
          tauxRemplissage: 0.82
        },
        {
          region: "Lyon",
          covoituragesCrees: 18,
          tauxRemplissage: 0.71
        }
      ]
    }
  },
  { upsert: true }
);

db.analytics.updateOne(
  { date: analyticsDates[1], type: "daily" },
  { 
    $set: {
      metrics: {
        nouveauxUtilisateurs: 18,
        covoituragesCrees: 65,
        covoituragesTermines: 59,
        tauxRemplissage: 0.72,
        economiesCO2: 298.5,
        transactionsTotal: 985.75
      },
      regionMetrics: [
        {
          region: "Paris",
          covoituragesCrees: 35,
          tauxRemplissage: 0.79
        },
        {
          region: "Lyon",
          covoituragesCrees: 15,
          tauxRemplissage: 0.68
        }
      ]
    }
  },
  { upsert: true }
);

// Geo_data
const geoData = [
  {
    type: "itineraire",
    covoiturageId: 1,
    geometry: {
      type: "LineString",
      coordinates: [
        [2.3522, 48.8566],
        [2.2950, 48.8738]
      ]
    },
    metadata: {
      distance: 5.2,
      duree: 25,
      created: new Date()
    },
    points_interet: [
      {
        position: [2.3343, 48.8638],
        type: "arret",
        nom: "Place de la Concorde"
      }
    ]
  },
  {
    type: "itineraire",
    covoiturageId: 2,
    geometry: {
      type: "LineString",
      coordinates: [
        [2.2950, 48.8738],
        [4.8320, 45.7578]
      ]
    },
    metadata: {
      distance: 463.5,
      duree: 275,
      created: new Date()
    },
    points_interet: [
      {
        position: [4.3872, 45.4397],
        type: "arret",
        nom: "Saint-Étienne"
      }
    ]
  }
];

geoData.forEach(geo => {
  db.geo_data.updateOne(
    { covoiturageId: geo.covoiturageId },
    { $set: geo },
    { upsert: true }
  );
});

// Configurations
const configData = [
  {
    code: "moderation.niveaux_alerte",
    valeur: {
      signalements_min: 3,
      suspension_auto: 5,
      intervalles_verification: 24
    },
    description: "Configuration des niveaux d'alerte pour la modération",
    actif: true,
    dateModification: new Date(),
    modifiePar: "admin"
  },
  {
    code: "app.feature_flags",
    valeur: {
      nouveau_chat: true,
      paiement_instantane: false,
      notation_etoiles: true
    },
    description: "Activation des fonctionnalités en cours de développement",
    actif: true,
    dateModification: new Date(),
    modifiePar: "admin"
  }
];

configData.forEach(config => {
  db.configurations.updateOne(
    { code: config.code },
    { $set: config },
    { upsert: true }
  );
});

// Reviews
const reviewsData = [
  {
    rideId: 1,
    authorUserId: 2,
    rating: 5,
    comment: "Trajet très agréable, conducteur ponctuel et sympathique. Voiture propre et confortable.",
    date: new Date("2023-06-18T10:30:00Z"),
    validated: true,
    moderatedBy: "admin",
    moderatedAt: new Date("2023-06-18T12:15:00Z")
  },
  {
    rideId: 1,
    authorUserId: 3,
    rating: 4,
    comment: "Bon trajet dans l'ensemble. Un peu de retard au départ mais bonne conduite.",
    date: new Date("2023-06-18T14:20:00Z"),
    validated: true,
    moderatedBy: "admin",
    moderatedAt: new Date("2023-06-18T15:45:00Z")
  },
  {
    rideId: 2,
    authorUserId: 1,
    rating: 3,
    comment: "Trajet correct mais le conducteur parlait beaucoup au téléphone.",
    date: new Date("2023-06-17T18:10:00Z"),
    validated: false
  }
];

reviewsData.forEach(review => {
  db.reviews.updateOne(
    { rideId: review.rideId, authorUserId: review.authorUserId },
    { $set: review },
    { upsert: true }
  );
});

print("Données insérées avec succès !");
print("=== INITIALISATION TERMINÉE AVEC SUCCÈS ===");

// Afficher un résumé des collections créées
print("\nRésumé des collections :");
const collections = db.getCollectionNames();
collections.forEach(collection => {
  const count = db[collection].countDocuments();
  print(`- ${collection}: ${count} document(s)`);
}); 