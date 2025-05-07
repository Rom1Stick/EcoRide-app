# Maintenance de la base de données MongoDB - EcoRide

Ce document décrit les procédures de maintenance pour la base de données MongoDB d'EcoRide.

## Nettoyage des données

### Suppression des collections non utilisées

Pour supprimer une collection non utilisée (comme `test_collection`):

```bash
# Se connecter au shell mongo avec authentification
docker exec -it ecoride-app-mongodb-1 mongosh -u mongo -p changeme --authenticationDatabase admin

# Sélectionner la base de données
use ecoride_nosql

# Supprimer la collection
db.test_collection.drop()
```

### Nettoyage des logs anciens

Les logs sont automatiquement supprimés après 30 jours grâce à l'index TTL. Pour forcer le nettoyage manuellement:

```js
// Supprimer les logs antérieurs à une certaine date
const dateLimit = new Date();
dateLimit.setDate(dateLimit.getDate() - 7); // Logs plus vieux que 7 jours
db.logs.deleteMany({ timestamp: { $lt: dateLimit } });
```

## Vérification et reconstruction des index

Pour vérifier et reconstruire tous les index, exécutez le script `check_indexes.js`:

```bash
docker exec -it ecoride-app-mongodb-1 mongosh -u mongo -p changeme --authenticationDatabase admin --file /app/backend/database/mongodb/check_indexes.js
```

Pour reconstruire un index particulier:

```js
// Supprimer et recréer l'index
db.preferences.dropIndex("userId_1");
db.preferences.createIndex({ userId: 1 }, { unique: true });
```

## Sauvegarde et restauration

### Sauvegarde de la base de données

```bash
# Utiliser mongodump pour sauvegarder toute la base de données
docker exec -it ecoride-app-mongodb-1 mongodump --username mongo --password changeme --authenticationDatabase admin --db ecoride_nosql --out /tmp/backup

# Copier les sauvegardes sur la machine hôte
docker cp ecoride-app-mongodb-1:/tmp/backup ./mongodb_backup
```

### Restauration de la base de données

```bash
# Copier les sauvegardes dans le conteneur
docker cp ./mongodb_backup ecoride-app-mongodb-1:/tmp/backup

# Utiliser mongorestore pour restaurer
docker exec -it ecoride-app-mongodb-1 mongorestore --username mongo --password changeme --authenticationDatabase admin --db ecoride_nosql /tmp/backup/ecoride_nosql
```

## Surveillance des performances

### Vérification de la taille des collections

```js
// Afficher la taille de chaque collection
db.getCollectionNames().forEach(collection => {
  const stats = db[collection].stats();
  print(`${collection}: ${(stats.size / (1024*1024)).toFixed(2)} MB, ${stats.count} documents`);
});
```

### Recherche des requêtes lentes

```js
// Activer le profilage de requêtes lentes (>100ms)
db.setProfilingLevel(1, { slowms: 100 });

// Consulter les requêtes lentes
db.system.profile.find().sort({ ts: -1 }).limit(10);
```

## Résolution de problèmes courants

### Identifiants de connexion perdus

Si vous avez perdu les identifiants de connexion, vous pouvez les retrouver dans les variables d'environnement du conteneur:

```bash
docker exec ecoride-app-mongodb-1 env | grep MONGO
```

### Index manquants 

Les index manquants peuvent causer des problèmes de performance. Utilisez le script `check_indexes.js` pour les recréer. 