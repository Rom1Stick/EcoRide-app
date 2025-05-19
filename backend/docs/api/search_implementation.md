# Implémentation de l'API de recherche de trajets

Ce document détaille l'implémentation et l'optimisation de l'API de recherche de trajets pour EcoRide.

## Documentation

La documentation OpenAPI de l'endpoint se trouve dans le fichier `backend/docs/api/openapi.yaml`.

## Optimisation des performances

Pour optimiser les performances de la recherche, nous avons créé un script SQL qui ajoute des index sur les colonnes fréquemment utilisées dans les requêtes de recherche. Ce script se trouve dans `backend/database/migrations/search_indexes.sql`.

### Exécution du script d'optimisation

Pour appliquer les optimisations, exécutez le script SQL :

```bash
# En utilisant la ligne de commande MySQL
mysql -u username -p ecoride < backend/database/migrations/search_indexes.sql

# OU via phpMyAdmin
# 1. Connectez-vous à phpMyAdmin
# 2. Sélectionnez la base de données ecoride
# 3. Cliquez sur l'onglet "SQL"
# 4. Collez le contenu du fichier search_indexes.sql
# 5. Cliquez sur "Exécuter"
```

### Procédure stockée

Le script inclut également une procédure stockée `search_rides` qui peut être utilisée pour optimiser davantage les performances. Cette procédure encapsule la logique de recherche complexe et réduit le trafic réseau entre l'application et la base de données.

## Harmonisation des URL d'API

Si vous souhaitez harmoniser les URL de l'API pour utiliser `/api/trips/search` au lieu de `/api/rides/search`, suivez ces étapes :

1. Modifiez le fichier des routes `backend/routes/api.php` :

```php
// Remplacer cette ligne
$router->get('/api/rides/search', 'SearchController@search');

// Par celle-ci
$router->get('/api/trips/search', 'SearchController@search');

// Ou ajouter les deux pour maintenir la compatibilité
$router->get('/api/rides/search', 'SearchController@search');
$router->get('/api/trips/search', 'SearchController@search');
```

2. Mettez à jour la documentation OpenAPI dans `backend/docs/api/openapi.yaml` pour refléter ce changement.

3. Informez l'équipe de front-end du changement afin qu'ils puissent mettre à jour leurs appels d'API.

## Bonnes pratiques pour l'utilisation de l'API

### Filtrage précis

Pour obtenir des résultats plus précis et améliorer les performances :
- Utilisez des noms de localités exacts quand c'est possible
- Spécifiez des filtres supplémentaires comme `maxPrice` ou `departureTime`

### Pagination

Utilisez toujours la pagination pour éviter de récupérer un trop grand nombre de résultats :
- Paramètre `page` pour spécifier la page (commence à 1)
- Paramètre `limit` pour spécifier le nombre d'éléments par page

### Tri

Utilisez le paramètre `sortBy` pour trier les résultats :
- `departureTime` (défaut) : tri par date et heure de départ
- `price` : tri par prix (croissant) 