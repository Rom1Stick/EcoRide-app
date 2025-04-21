// Script de nettoyage de la base de données MongoDB EcoRide
// Pour exécuter: mongosh -u mongo -p changeme --authenticationDatabase admin ecoride_nosql clean_database.js

// Suppression des collections de test
try {
  db.test_collection.drop();
  print("Collection test_collection supprimée avec succès");
} catch (e) {
  if (e.codeName === "NamespaceNotFound") {
    print("La collection test_collection n'existe pas, aucune action nécessaire");
  } else {
    print("Erreur lors de la suppression de test_collection: " + e.message);
  }
}

// Nettoyage des documents temporaires
db.logs.deleteMany({ 
  timestamp: { $lt: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000) }, 
  level: "debug" 
});
print("Nettoyage des logs de débogage de plus de 30 jours terminé");

// Suppression des index inutilisés
const logIndexes = db.logs.getIndexes();
for (let index of logIndexes) {
  // Suppression de l'index temporaire s'il existe
  if (index.name === "temp_index_to_remove") {
    db.logs.dropIndex("temp_index_to_remove");
    print("Index temporaire supprimé de la collection logs");
  }
}

// Vérification des documents orphelins
const orphanReviews = db.reviews.find({
  userId: { $exists: true },
  $expr: { $eq: [{ $type: "$userId" }, "string"] }  // Identifie les userId au mauvais format
}).toArray();

if (orphanReviews.length > 0) {
  print(`Trouvé ${orphanReviews.length} avis avec un format userId incorrect`);
  // Option 1: Correction du format
  for (let review of orphanReviews) {
    // Convertir string en number si possible
    if (!isNaN(review.userId)) {
      db.reviews.updateOne(
        { _id: review._id },
        { $set: { userId: NumberInt(review.userId) } }
      );
      print(`Corrigé le format du userId pour l'avis ${review._id}`);
    }
  }
  // Option 2: Suppression (décommenter si nécessaire)
  // db.reviews.deleteMany({
  //   userId: { $exists: true },
  //   $expr: { $eq: [{ $type: "$userId" }, "string"] }
  // });
  // print("Suppression des avis orphelins terminée");
}

// Optimisation des collections
db.runCommand({ compact: "logs" });
print("Optimisation de la collection logs terminée");

db.runCommand({ compact: "analytics" });
print("Optimisation de la collection analytics terminée");

print("Nettoyage de la base de données terminé avec succès"); 