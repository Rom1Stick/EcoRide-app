// EcoRide - Script de smoke-test pour MongoDB
// Ce script vérifie la présence des collections, des index et exécute des requêtes de base

// Fonction pour vérifier l'existence d'une collection
function checkCollection(collectionName) {
  const collections = db.getCollectionNames();
  if (!collections.includes(collectionName)) {
    print(`[ERREUR] Collection ${collectionName} non trouvée`);
    return false;
  }
  print(`[OK] Collection ${collectionName} existe`);
  return true;
}

// Fonction pour vérifier les index
function checkIndexes(collectionName, expectedIndexes) {
  const indexes = db[collectionName].getIndexes();
  const indexNames = indexes.map(idx => idx.name).filter(name => name !== "_id_");
  
  for (const expected of expectedIndexes) {
    if (!indexNames.includes(expected)) {
      print(`[ERREUR] Index ${expected} manquant dans ${collectionName}`);
      return false;
    }
  }
  print(`[OK] Tous les index attendus existent dans ${collectionName}`);
  return true;
}

// Fonction pour vérifier le schéma de validation
function checkValidator(collectionName, requiredFields) {
  const options = db.getCollectionInfos({ name: collectionName })[0].options;
  if (!options.validator || !options.validator.$jsonSchema) {
    print(`[ERREUR] Pas de validateur JSON Schema pour ${collectionName}`);
    return false;
  }
  
  const schema = options.validator.$jsonSchema;
  if (!schema.required) {
    print(`[ERREUR] Pas de champs requis définis dans le schéma de ${collectionName}`);
    return false;
  }
  
  for (const field of requiredFields) {
    if (!schema.required.includes(field)) {
      print(`[ERREUR] Champ requis ${field} manquant dans le schéma de ${collectionName}`);
      return false;
    }
  }
  
  print(`[OK] Schéma de validation de ${collectionName} correct`);
  return true;
}

// Fonction pour vérifier une requête de base
function testQuery(collectionName, query) {
  try {
    const result = db[collectionName].find(query).toArray();
    print(`[OK] Requête sur ${collectionName} a retourné ${result.length} document(s)`);
    return true;
  } catch (e) {
    print(`[ERREUR] Échec de la requête sur ${collectionName}: ${e.message}`);
    return false;
  }
}

// Fonction principale
function runTests() {
  print("=== DÉBUT DES TESTS EcoRide MongoDB ===");
  
  let errors = 0;
  
  // Vérifier l'existence des collections
  const collections = [
    "preferences", 
    "logs", 
    "analytics", 
    "geo_data", 
    "configurations",
    "reviews"
  ];
  
  for (const coll of collections) {
    if (!checkCollection(coll)) errors++;
  }
  
  // Vérifier les index
  const indexTests = [
    { collection: "preferences", indexes: ["userId_1"] },
    { collection: "logs", indexes: ["timestamp_1", "nivel_1_service_1", "meta.userId_1"] },
    { collection: "analytics", indexes: ["date_1_type_1"] },
    { collection: "geo_data", indexes: ["geometry_2dsphere", "covoiturageId_1"] },
    { collection: "configurations", indexes: ["code_1"] },
    { collection: "reviews", indexes: ["rideId_1", "authorUserId_1", "comment_text", "rideId_1_rating_-1"] }
  ];
  
  for (const test of indexTests) {
    if (!checkIndexes(test.collection, test.indexes)) errors++;
  }
  
  // Vérifier les validateurs
  const validatorTests = [
    { collection: "preferences", requiredFields: ["userId", "standard"] },
    { collection: "logs", requiredFields: ["timestamp", "niveau", "service", "message"] },
    { collection: "analytics", requiredFields: ["date", "type", "metrics"] },
    { collection: "geo_data", requiredFields: ["type", "geometry"] },
    { collection: "configurations", requiredFields: ["code", "valeur", "actif"] },
    { collection: "reviews", requiredFields: ["rideId", "authorUserId", "rating", "date"] }
  ];
  
  for (const test of validatorTests) {
    if (!checkValidator(test.collection, test.requiredFields)) errors++;
  }
  
  // Tester des requêtes de base
  const queryTests = [
    { collection: "preferences", query: {} },
    { collection: "logs", query: { niveau: "info" } },
    { collection: "analytics", query: {} },
    { collection: "geo_data", query: {} },
    { collection: "configurations", query: {} },
    { collection: "reviews", query: {} }
  ];
  
  for (const test of queryTests) {
    if (!testQuery(test.collection, test.query)) errors++;
  }
  
  // Résumé
  print("=== FIN DES TESTS ===");
  if (errors === 0) {
    print("✅ TOUS LES TESTS ONT RÉUSSI");
    quit(0);
  } else {
    print(`❌ ${errors} ERREUR(S) DÉTECTÉE(S)`);
    quit(1);
  }
}

// Exécuter les tests
runTests(); 