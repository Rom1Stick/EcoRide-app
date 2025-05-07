// Script simplifié d'initialisation MongoDB

// Créer la base de données ecoride_nosql
db = db.getSiblingDB('ecoride_nosql');

// Nettoyer les collections existantes (optionnel)
//db.preferences.drop();
//db.logs.drop();
//db.analytics.drop();
//db.geo_data.drop();
//db.configurations.drop();
//db.reviews.drop();
//db.test_collection.drop();

// Création des collections
db.createCollection("preferences");
db.createCollection("logs");
db.createCollection("analytics");
db.createCollection("geo_data");
db.createCollection("configurations");
db.createCollection("reviews");

// Insertion de quelques données de test
db.preferences.insertOne({
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
});

db.logs.insertOne({
  timestamp: new Date(),
  niveau: "info",
  service: "auth",
  message: "Connexion réussie",
  meta: {
    userId: 1,
    ipAddress: "192.168.1.10",
    browser: "Chrome"
  }
});

db.analytics.insertOne({
  date: new Date("2023-06-15"),
  type: "daily",
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
});

db.geo_data.insertOne({
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
});

db.configurations.insertOne({
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
});

db.reviews.insertOne({
  rideId: 1,
  authorUserId: 2,
  rating: 5,
  comment: "Trajet très agréable, conducteur ponctuel et sympathique. Voiture propre et confortable.",
  date: new Date("2023-06-18T10:30:00Z"),
  validated: true,
  moderatedBy: "admin",
  moderatedAt: new Date("2023-06-18T12:15:00Z")
});

// Création des index de base
db.preferences.createIndex({ userId: 1 }, { unique: true });
db.logs.createIndex({ timestamp: 1 }, { expireAfterSeconds: 2592000 });
db.reviews.createIndex({ rideId: 1 });
db.geo_data.createIndex({ geometry: "2dsphere" });
db.configurations.createIndex({ code: 1 }, { unique: true });

// Afficher les collections créées
print("\nCollections créées:");
db.getCollectionNames().forEach(collection => {
  print(` - ${collection}: ${db[collection].countDocuments()} document(s)`);
}); 