// EcoRide - Exemples de requêtes MongoDB
// Ce fichier contient des exemples de requêtes utiles pour l'application

// 1. REQUÊTES SUR LES PRÉFÉRENCES

// Récupérer les préférences d'un utilisateur
db.preferences.findOne({ userId: 1 });

// Mettre à jour une préférence standard
db.preferences.updateOne(
  { userId: 1 },
  { 
    $set: { 
      "standard.musique": "rock",
      "lastUpdated": new Date()
    } 
  }
);

// Ajouter une préférence personnalisée
db.preferences.updateOne(
  { userId: 1 },
  { 
    $push: { 
      "custom": { key: "notification_delai", value: 30 } 
    },
    $set: { "lastUpdated": new Date() }
  }
);

// 2. REQUÊTES SUR LES LOGS

// Rechercher tous les logs d'erreur des dernières 24h
const hier = new Date();
hier.setDate(hier.getDate() - 1);

db.logs.find({ 
  niveau: "error", 
  timestamp: { $gte: hier } 
});

// Compter les erreurs par service
db.logs.aggregate([
  { $match: { niveau: "error" } },
  { $group: { _id: "$service", count: { $sum: 1 } } },
  { $sort: { count: -1 } }
]);

// Rechercher les logs d'un utilisateur spécifique
db.logs.find({ "meta.userId": 2 }).sort({ timestamp: -1 });

// 3. REQUÊTES SUR LES ANALYTICS

// Obtenir les statistiques d'une journée spécifique
db.analytics.findOne({ 
  date: new Date("2023-06-15"), 
  type: "daily" 
});

// Calculer la moyenne du taux de remplissage sur une période
db.analytics.aggregate([
  { 
    $match: { 
      date: { 
        $gte: new Date("2023-06-01"), 
        $lte: new Date("2023-06-30") 
      },
      type: "daily"
    } 
  },
  { 
    $group: { 
      _id: null, 
      moyenne_remplissage: { $avg: "$metrics.tauxRemplissage" },
      total_covoiturages: { $sum: "$metrics.covoituragesCrees" },
      total_CO2: { $sum: "$metrics.economiesCO2" }
    } 
  }
]);

// Statistiques par région
db.analytics.aggregate([
  { $unwind: "$regionMetrics" },
  { 
    $group: { 
      _id: "$regionMetrics.region", 
      total_covoiturages: { $sum: "$regionMetrics.covoituragesCrees" },
      taux_moyen: { $avg: "$regionMetrics.tauxRemplissage" }
    } 
  },
  { $sort: { total_covoiturages: -1 } }
]);

// 4. REQUÊTES GÉOSPATIALES

// Trouver les trajets dans un rayon de 5km autour de Paris
db.geo_data.find({
  geometry: {
    $near: {
      $geometry: {
        type: "Point",
        coordinates: [2.3522, 48.8566] // Paris
      },
      $maxDistance: 5000 // 5km en mètres
    }
  }
});

// Trouver les trajets qui passent par une zone spécifique
db.geo_data.find({
  geometry: {
    $geoIntersects: {
      $geometry: {
        type: "Polygon",
        coordinates: [[
          [2.3200, 48.8800],
          [2.3700, 48.8800],
          [2.3700, 48.8400],
          [2.3200, 48.8400],
          [2.3200, 48.8800]
        ]]
      }
    }
  }
});

// 5. REQUÊTES SUR LES CONFIGURATIONS

// Récupérer une configuration par son code
db.configurations.findOne({ code: "app.feature_flags" });

// Vérifier si une fonctionnalité est active
db.configurations.findOne(
  { 
    code: "app.feature_flags",
    "valeur.nouveau_chat": true,
    actif: true
  }
);

// Activer ou désactiver une fonctionnalité
db.configurations.updateOne(
  { code: "app.feature_flags" },
  { 
    $set: { 
      "valeur.paiement_instantane": true,
      "dateModification": new Date(),
      "modifiePar": "admin"
    } 
  }
); 