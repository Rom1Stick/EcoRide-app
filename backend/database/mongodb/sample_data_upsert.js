// EcoRide - Exemples de données pour MongoDB avec upserts
// Ce fichier contient des exemples de documents pour chaque collection
// Utilise des upserts pour éviter les erreurs de duplication

// === PREFERENCES ===

// Nettoyage préalable (optionnel si idempotence stricte nécessaire)
// db.preferences.deleteMany({});

// Insertion avec upsert pour preferences
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
  print(`Upsert preference pour userId: ${pref.userId}`);
});

// === LOGS ===

// Exemple d'insertion directe pour les logs car ils sont généralement cumulatifs
// et n'ont pas de contrainte d'unicité
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

// === ANALYTICS ===

// Dates clés pour les analytics
const analyticsDates = [
  new Date("2023-06-15"),
  new Date("2023-06-16")
];

// Upsert pour analytics (unicité par date + type)
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

// === GEO_DATA ===

// Upsert pour geo_data (unicité par covoiturageId)
const geoData = [
  {
    type: "itineraire",
    covoiturageId: 1,  // Référence au covoiturage de Paris à l'Arc de Triomphe
    geometry: {
      type: "LineString",
      coordinates: [
        [2.3522, 48.8566],  // Paris (Gare de Lyon)
        [2.2950, 48.8738]   // Arc de Triomphe
      ]
    },
    metadata: {
      distance: 5.2,  // km
      duree: 25,      // minutes
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
    covoiturageId: 2,  // Référence au deuxième covoiturage dans MySQL
    geometry: {
      type: "LineString",
      coordinates: [
        [2.2950, 48.8738],  // Arc de Triomphe
        [4.8320, 45.7578]   // Lyon (Place Bellecour)
      ]
    },
    metadata: {
      distance: 463.5,  // km
      duree: 275,       // minutes
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
  print(`Upsert geo_data pour covoiturageId: ${geo.covoiturageId}`);
});

// === CONFIGURATIONS ===

// Upsert pour configurations (unicité par code)
const configData = [
  {
    code: "moderation.niveaux_alerte",
    valeur: {
      signalements_min: 3,
      suspension_auto: 5,
      intervalles_verification: 24  // heures
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
  print(`Upsert configuration pour code: ${config.code}`);
});

// === REVIEWS ===

// Clé composite pour reviews (rideId + authorUserId)
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
  print(`Upsert review pour rideId: ${review.rideId}, authorUserId: ${review.authorUserId}`);
});

print("Données d'exemple insérées avec succès !"); 