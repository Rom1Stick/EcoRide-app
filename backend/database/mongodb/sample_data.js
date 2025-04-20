// EcoRide - Exemples de données pour MongoDB
// Ce fichier contient des exemples de documents pour chaque collection

// Exemples pour la collection preferences
db.preferences.insertMany([
  {
    userId: 1,  // Référence à Jean Dupont dans MySQL
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
    userId: 2,  // Référence à Sophie Martin dans MySQL
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
]);

// Exemples pour la collection logs
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

// Exemples pour la collection analytics
db.analytics.insertMany([
  {
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
  },
  {
    date: new Date("2023-06-16"),
    type: "daily",
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
]);

// Exemples pour la collection geo_data
db.geo_data.insertMany([
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
]);

// Exemples pour la collection configurations
db.configurations.insertMany([
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
]); 