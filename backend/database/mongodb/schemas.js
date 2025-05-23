// EcoRide - Schémas de validation MongoDB
// Ce fichier contient les définitions des collections et leurs schémas de validation

// Création de la collection preferences
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

// Création de la collection logs
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

// Création de la collection analytics
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

// Création de la collection geo_data
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

// Création de la collection configurations
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

// Création de la collection reviews
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