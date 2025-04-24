# Architecture de la couche d'accès aux données (DAL) - EcoRide

## Aperçu de l'architecture

La couche d'accès aux données (DAL) d'EcoRide est conçue pour gérer efficacement les interactions avec deux types de bases de données :

1. **MySQL** (SQL) pour les données relationnelles structurées
2. **MongoDB** (NoSQL) pour les données flexibles et non relationnelles

Cette architecture hybride permet d'exploiter les forces de chaque type de base de données tout en maintenant une cohérence dans l'accès aux données.

## Choix du pattern : Repository vs DAO vs micro-ORM

Après analyse des besoins du projet, nous avons opté pour le **pattern Repository** pour les raisons suivantes :

- **Simplicité et légèreté** : Le pattern Repository offre une abstraction légère mais suffisante de la couche d'accès aux données, sans la complexité d'un ORM complet.
- **Séparation des préoccupations** : Il permet une séparation claire entre la logique métier et l'accès aux données.
- **Testabilité** : Les repositories sont facilement mockables pour les tests unitaires.
- **Adaptabilité** : Le pattern s'adapte aussi bien aux bases de données relationnelles (MySQL) qu'aux bases de données NoSQL (MongoDB).
- **Cohérence** : Il fournit une interface cohérente pour l'accès aux données, indépendamment de la source.

Pour MongoDB, nous utilisons une variante nommée "Service" qui suit les mêmes principes mais avec une terminologie plus adaptée aux opérations NoSQL.

## Organisation des dossiers

```
backend/
├── src/
│   ├── DataAccess/
│   │   ├── Sql/
│   │   │   ├── Repository/  # Repositories pour l'accès aux données MySQL
│   │   │   │   ├── RepositoryInterface.php
│   │   │   │   ├── AbstractRepository.php
│   │   │   │   └── [EntityName]Repository.php
│   │   │   └── Entity/      # Entités pour les données MySQL
│   │   │       └── [EntityName].php
│   │   ├── NoSql/
│   │   │   ├── Service/     # Services pour l'accès aux données MongoDB
│   │   │   │   ├── MongoServiceInterface.php
│   │   │   │   ├── AbstractMongoService.php
│   │   │   │   └── [ModelName]Service.php
│   │   │   ├── Model/       # Modèles pour les données MongoDB
│   │   │   │   └── [ModelName].php
│   │   │   └── MongoConnection.php
│   │   └── Exception/
│   │       └── DataAccessException.php
│   └── ...
```

### Conventions de nommage

- **Classes** : CamelCase (ex: `UserRepository`, `UserPreferenceService`)
- **Méthodes** : camelCase (ex: `findById`, `createUser`)
- **Tables MySQL** : snake_case (ex: `utilisateur`, `covoiturage`)
- **Collections MongoDB** : camelCase (ex: `preferences`, `geoData`)
- **Fichiers** : Même nom que la classe qu'ils contiennent

## Composants clés

### Pour MySQL (SQL)

1. **RepositoryInterface** : Définit le contrat de base pour tous les repositories
   - `findById(int $id)`
   - `create($entity): int`
   - `update($entity): bool`
   - `delete(int $id): bool`

2. **AbstractRepository** : Implémente les fonctionnalités communes
   - Gestion de la connexion PDO
   - Méthodes CRUD de base
   - Méthodes utilitaires (pagination, comptage, etc.)

3. **EntityRepository** (ex: `UserRepository`) : Implémentations spécifiques pour chaque entité
   - Méthodes de recherche avancées
   - Validation spécifique
   - Construction d'entités

4. **Entity** (ex: `User`) : Classes représentant les données
   - Attributs correspondant aux colonnes
   - Getters/setters avec validations
   - Méthodes utilitaires spécifiques à l'entité

### Pour MongoDB (NoSQL)

1. **MongoServiceInterface** : Définit le contrat pour les services MongoDB
   - `findById($id)`
   - `insert(array $data): ObjectId`
   - `update($id, array $data): bool`
   - `delete($id): bool`
   - `find(array $criteria, array $options): array`
   - `count(array $criteria): int`

2. **AbstractMongoService** : Implémentation commune pour tous les services
   - Gestion de la connexion MongoDB
   - Méthodes CRUD de base
   - Gestion des erreurs
   - Conversion ObjectId et formatage

3. **ModelService** (ex: `UserPreferenceService`) : Services spécifiques
   - Méthodes spécialisées pour chaque collection
   - Agrégations et requêtes complexes
   - Logique métier spécifique

4. **Model** (ex: `UserPreference`) : Classes représentant les documents
   - Conversion des données BSON vers PHP et vice-versa
   - Validations métier
   - Méthodes utilitaires

### Gestion des exceptions

La classe `DataAccessException` centralise la gestion des erreurs de la DAL :
- Encapsule les exceptions PDO et MongoDB
- Distingue les types d'erreurs (connexion, requête, validation)
- Fournit des méthodes utilitaires pour le diagnostic
- Indique la source de l'erreur (SQL ou NoSQL)

## Utilisation des transactions

Pour les opérations MySQL qui nécessitent une cohérence transactionnelle :
- Utilisation des transactions PDO (`BEGIN`, `COMMIT`, `ROLLBACK`)
- Gestion des erreurs avec rollback automatique
- Isolation des transactions pour éviter les problèmes de concurrence

## Performances et optimisations

1. **Préparation des requêtes** : Utilisation systématique de `prepare()` et `bindParam()`
2. **Pagination** : Implémentation par défaut sur les méthodes de liste
3. **Limiteurs** : Utilisation de `LIMIT` pour contrôler la taille des résultats
4. **Indexes** : Conception spécifique pour MySQL et MongoDB
5. **Requêtes MongoDB optimisées** : Projection, limitation des champs retournés

## Intégration avec l'injection de dépendances

Pour faciliter l'utilisation de la DAL, nous recommandons d'utiliser une approche d'injection de dépendances :

```php
// Exemple avec PHP-DI
$container = new \DI\Container();
$container->set(\App\Core\Database::class, \DI\create()->constructor());
$container->set(\App\DataAccess\NoSql\MongoConnection::class, \DI\create());
$container->set(\App\DataAccess\Sql\Repository\UserRepository::class, \DI\create());
$container->set(\App\DataAccess\NoSql\Service\UserPreferenceService::class, \DI\create());

// Utilisation
$userRepository = $container->get(\App\DataAccess\Sql\Repository\UserRepository::class);
$preferenceService = $container->get(\App\DataAccess\NoSql\Service\UserPreferenceService::class);
```

Alternativement, un simple factory peut être utilisé pour les projets plus légers.

## Considérations de sécurité

- Utilisation systématique de requêtes préparées pour éviter les injections SQL
- Validation des entrées au niveau des entités
- Gestion des erreurs sans exposer de détails sensibles
- Limitation des droits de connexion aux bases de données

## Conclusion

Cette architecture de DAL offre un équilibre entre simplicité, flexibilité et robustesse. Elle permet d'exploiter efficacement les deux types de bases de données tout en maintenant une interface cohérente pour le reste de l'application.

Elle suit les principes SOLID, en particulier le principe de responsabilité unique (chaque classe a une seule raison de changer) et le principe d'inversion de dépendance (dépendance envers des abstractions plutôt que des implémentations concrètes). 