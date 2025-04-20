# Requêtes MongoDB Utiles - EcoRide

Ce document présente des exemples de requêtes MongoDB pour les opérations courantes sur chaque collection.

## Requêtes sur la collection `preferences`

### Récupérer les préférences d'un utilisateur
```js
// Récupérer toutes les préférences de l'utilisateur avec ID 1
db.preferences.findOne({ userId: 1 });
```

### Mettre à jour une préférence standard
```js
// Mettre à jour la préférence de musique pour l'utilisateur 1
db.preferences.updateOne(
  { userId: 1 },
  { 
    $set: { 
      "standard.musique": "rock",
      "lastUpdated": new Date()
    } 
  }
);
```

### Ajouter une préférence personnalisée
```js
// Ajouter une préférence personnalisée pour l'utilisateur 1
db.preferences.updateOne(
  { userId: 1 },
  { 
    $push: { 
      "custom": { key: "notification_delai", value: 30 } 
    },
    $set: { "lastUpdated": new Date() }
  }
);
```

### Supprimer une préférence personnalisée
```js
// Supprimer une préférence personnalisée par sa clé
db.preferences.updateOne(
  { userId: 1 },
  { 
    $pull: { 
      "custom": { key: "notification_delai" } 
    },
    $set: { "lastUpdated": new Date() }
  }
);
```

## Requêtes sur la collection `logs`

### Rechercher des logs par niveau
```js
// Trouver tous les logs d'erreur
db.logs.find({ niveau: "error" }).sort({ timestamp: -1 });
```

### Rechercher les logs récents (dernières 24h)
```js
// Calculer la date d'il y a 24h
const hier = new Date();
hier.setDate(hier.getDate() - 1);

// Trouver tous les logs depuis cette date
db.logs.find({ 
  timestamp: { $gte: hier } 
}).sort({ timestamp: -1 });
```

### Compter les erreurs par service
```js
// Agréger et compter les erreurs par service
db.logs.aggregate([
  { $match: { niveau: "error" } },
  { $group: { _id: "$service", count: { $sum: 1 } } },
  { $sort: { count: -1 } }
]);
```

### Rechercher les logs d'un utilisateur
```js
// Trouver tous les logs concernant l'utilisateur 2
db.logs.find({ "meta.userId": 2 }).sort({ timestamp: -1 });
```

## Requêtes sur la collection `analytics`

### Obtenir les statistiques d'une journée
```js
// Trouver les statistiques du 15 juin 2023
db.analytics.findOne({ 
  date: new Date("2023-06-15"), 
  type: "daily" 
});
```

### Calculer des moyennes sur une période
```js
// Calculer la moyenne du taux de remplissage pour le mois de juin
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
```

### Analyser les statistiques par région
```js
// Regrouper et analyser les données par région
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
```

## Requêtes sur la collection `geo_data`

### Recherche par proximité
```js
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
```

### Recherche d'intersection avec une zone
```js
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
```

### Trouver les trajets par covoiturage
```js
// Récupérer les données géo pour un covoiturage spécifique
db.geo_data.findOne({ covoiturageId: 1 });
```

### Calculer la distance entre deux points
```js
// Calculer la distance entre deux points en mètres
db.runCommand({
  geoNear: "geo_data",
  near: {
    type: "Point",
    coordinates: [2.3522, 48.8566] // Paris
  },
  spherical: true,
  query: { type: "point" }
});
```

## Requêtes sur la collection `configurations`

### Récupérer une configuration
```js
// Trouver la configuration des drapeaux de fonctionnalités
db.configurations.findOne({ code: "app.feature_flags" });
```

### Vérifier si une fonctionnalité est active
```js
// Vérifier si le chat est activé
db.configurations.findOne(
  { 
    code: "app.feature_flags",
    "valeur.nouveau_chat": true,
    actif: true
  }
);
```

### Activer ou désactiver une fonctionnalité
```js
// Activer le paiement instantané
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
```

### Ajouter une nouvelle configuration
```js
// Ajouter une nouvelle configuration
db.configurations.insertOne({
  code: "notification.delais",
  valeur: {
    rappel_covoiturage: 60, // minutes
    confirmation_reservation: 15 // minutes
  },
  description: "Délais pour les envois de notifications",
  actif: true,
  dateModification: new Date(),
  modifiePar: "admin"
});
```

## Requêtes d'administration

### Vérifier les index
```js
// Lister tous les index d'une collection
db.logs.getIndexes();
```

### Vérifier la taille des collections
```js
// Obtenir les statistiques d'une collection
db.logs.stats();
```

### Compacter une collection
```js
// Compacter la collection logs
db.runCommand({ compact: "logs" });
``` 