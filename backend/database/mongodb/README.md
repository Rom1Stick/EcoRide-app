# EcoRide - MongoDB

Ce dossier contient les scripts et la documentation pour la configuration de la base de données MongoDB d'EcoRide.

## Base de données

EcoRide utilise une base de données MongoDB nommée `ecoride_nosql` avec les collections suivantes:

- `preferences`: Préférences utilisateur pour l'application
- `logs`: Journaux d'événements de l'application
- `analytics`: Données d'analyse pour le suivi des performances
- `geo_data`: Données de géolocalisation pour les trajets
- `configurations`: Configurations système pour l'application
- `reviews`: Avis des utilisateurs sur les trajets

## Structure des collections

### Collection preferences
```
{
  userId: Number,          // Identifiant unique de l'utilisateur
  standard: {              // Préférences standard
    musique: String,       // Genre de musique préféré
    // Autres préférences...
  },
  // Autres types de préférences...
  lastUpdated: Date        // Date de dernière mise à jour
}
```

### Collection logs
```
{
  timestamp: Date,         // Horodatage de l'événement
  level: String,           // Niveau (info, warning, error)
  message: String,         // Message de log
  source: String,          // Source du log (module)
  userId: Number,          // Utilisateur concerné (facultatif)
  metadata: Object         // Métadonnées supplémentaires
}
```

## Accès à la base de données

### Mongo Express
Une interface web Mongo Express est disponible pour gérer la base de données:
- URL: http://localhost:8082/
- Utilisateur: admin
- Mot de passe: pass

### Shell MongoDB
Pour accéder au shell MongoDB:
```bash
docker exec -it ecoride-app-mongodb-1 mongosh -u mongo -p changeme --authenticationDatabase admin
```

## Maintenance

### Vérification et recréation des index

Pour vérifier et recréer les index de la base de données, exécutez le script suivant :

```bash
mongosh -u mongo -p changeme --authenticationDatabase admin ecoride_nosql backend/database/mongodb/scripts/check_indexes.js
```

### Nettoyage de la base de données

Pour nettoyer la base de données (supprimer les collections de test, nettoyer les logs anciens, optimiser les collections), utilisez le script suivant :

```bash
mongosh -u mongo -p changeme --authenticationDatabase admin ecoride_nosql backend/database/mongodb/scripts/clean_database.js
```

Ce script effectue les opérations suivantes :
- Supprime la collection test_collection
- Nettoie les logs de débogage de plus de 30 jours
- Supprime les index temporaires inutilisés
- Vérifie et corrige les documents avec un format userId incorrect
- Optimise les collections logs et analytics

Pour plus de détails sur la maintenance MongoDB, consultez la [documentation officielle MongoDB](https://docs.mongodb.com/manual/administration/maintenance/).

## Scripts

- [check_indexes.js](./check_indexes.js): Script pour vérifier et recréer les index de la base de données.

## Configuration initiale

Pour initialiser la base de données avec les collections et index nécessaires:
1. Assurez-vous que les conteneurs Docker sont en cours d'exécution
2. Exécutez le script check_indexes.js via mongosh
3. Vérifiez que toutes les collections sont créées dans Mongo Express 