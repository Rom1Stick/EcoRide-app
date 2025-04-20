// EcoRide - Création des index MongoDB
// Ce fichier contient tous les index nécessaires pour optimiser les performances

// Collection preferences
db.preferences.createIndex({ userId: 1 }, { unique: true });

// Collection logs
// TTL index : supprime automatiquement les logs après 30 jours
db.logs.createIndex({ timestamp: 1 }, { expireAfterSeconds: 2592000 });
// Index pour recherche rapide par niveau et service
db.logs.createIndex({ niveau: 1, service: 1 });
// Index pour recherche des logs par utilisateur
db.logs.createIndex({ "meta.userId": 1 });

// Collection analytics
// Index pour recherche rapide des statistiques par date et type
db.analytics.createIndex({ date: 1, type: 1 });

// Collection geo_data
// Index géospatial pour recherches de proximité
db.geo_data.createIndex({ geometry: "2dsphere" });
// Index pour trouver rapidement les données géo d'un covoiturage
db.geo_data.createIndex({ covoiturageId: 1 });

// Collection configurations
// Index pour recherche rapide des configurations par code
db.configurations.createIndex({ code: 1 }, { unique: true }); 