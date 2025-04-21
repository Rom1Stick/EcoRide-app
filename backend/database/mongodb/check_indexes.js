// Script de vérification et création des index pour MongoDB EcoRide
// Ce script vérifie si tous les index requis existent et les recrée si nécessaire

// Se connecter à la base de données ecoride_nosql
db = db.getSiblingDB('ecoride_nosql');

print("=== VÉRIFICATION DES INDEX MONGODB POUR ECORIDE ===");

// Fonction pour vérifier et créer un index s'il n'existe pas
function ensureIndex(collection, indexSpec, options = {}) {
  const collObj = db[collection];
  const indexes = collObj.getIndexes();
  
  // Construire le nom de l'index pour la recherche
  let indexName = '';
  for (const [key, value] of Object.entries(indexSpec)) {
    if (indexName !== '') indexName += '_';
    indexName += key + '_' + value;
  }
  
  // Vérifier si l'index existe
  const exists = indexes.some(idx => idx.name === indexName);
  
  if (exists) {
    print(`Index ${indexName} existe déjà dans ${collection}`);
    return false;
  } else {
    print(`Création de l'index ${indexName} dans ${collection}...`);
    collObj.createIndex(indexSpec, options);
    return true;
  }
}

// Collection preferences
ensureIndex('preferences', { userId: 1 }, { unique: true });

// Collection logs
ensureIndex('logs', { timestamp: 1 }, { expireAfterSeconds: 2592000 }); // TTL 30 jours
ensureIndex('logs', { niveau: 1, service: 1 });
ensureIndex('logs', { "meta.userId": 1 });

// Collection analytics
ensureIndex('analytics', { date: 1, type: 1 });

// Collection geo_data
ensureIndex('geo_data', { geometry: "2dsphere" });
ensureIndex('geo_data', { covoiturageId: 1 });

// Collection configurations
ensureIndex('configurations', { code: 1 }, { unique: true });

// Collection reviews
ensureIndex('reviews', { rideId: 1 });
ensureIndex('reviews', { authorUserId: 1 });
ensureIndex('reviews', { comment: "text" });
ensureIndex('reviews', { rideId: 1, rating: -1 });

print("\n=== VÉRIFICATION DES INDEX TERMINÉE ===");

// Vérification finale et affichage des statistiques
print("\nListe des collections et leurs index :");
db.getCollectionNames().forEach(collection => {
  const indexes = db[collection].getIndexes();
  print(`\n${collection} (${indexes.length} index):`);
  indexes.forEach(idx => {
    print(` - ${idx.name}: ${JSON.stringify(idx.key)}`);
  });
}); 