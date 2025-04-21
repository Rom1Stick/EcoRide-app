# Architecture de la Couche d'Accès aux Données (DAL) - EcoRide

## 1. Pattern Choisi : Repository

Nous avons choisi le pattern Repository pour la couche d'accès aux données d'EcoRide en raison de ses avantages :

- **Séparation claire** entre la logique métier et la persistance des données
- **Abstraction des sources de données** permettant de traiter de manière uniforme SQL et NoSQL
- **Facilité de tests** grâce à la possibilité de substituer les implémentations par des mocks
- **Respect des principes d'éco-conception** avec un code léger et une consommation de ressources maîtrisée
- **Compatibilité avec l'injection de dépendances** pour les services

## 2. Structure des Packages

```
backend/
└── app/
    ├── Core/              # Éléments fondamentaux du framework
    │   ├── Database/      # Connexions et configuration des bases de données
    │   │   ├── SqlConnection.php
    │   │   └── MongoConnection.php
    ├── Models/            # Entités et modèles de domaine
    │   ├── Entities/      # Classes d'entités PHP pour tables SQL
    │   │   ├── User.php
    │   │   ├── Vehicle.php
    │   │   ├── Trip.php
    │   │   └── ...
    │   └── Documents/     # Structures de documents MongoDB
    │       ├── ReviewDocument.php
    │       ├── PreferenceDocument.php
    │       └── ...
    ├── Repositories/      # Implémentations concrètes des repositories
    │   ├── Interfaces/    # Contrats pour les repositories
    │   │   ├── IUserRepository.php
    │   │   ├── ITripRepository.php
    │   │   └── ...
    │   ├── SQL/           # Repositories pour les données relationnelles
    │   │   ├── UserRepository.php
    │   │   ├── TripRepository.php
    │   │   └── ...
    │   └── NoSQL/         # Repositories pour les données MongoDB
    │       ├── ReviewRepository.php
    │       ├── PreferenceRepository.php
    │       └── ...
    └── Services/          # Couche de service utilisant les repositories
```

## 3. Conventions de Nommage

Nous avons établi les conventions suivantes pour unifier la nomenclature :

### Classes et Interfaces
- **Entités SQL** : Noms au singulier, PascalCase (ex: `User`, `Vehicle`, `Trip`)
- **Documents NoSQL** : Suffixe "Document" (ex: `ReviewDocument`, `PreferenceDocument`)
- **Interfaces de Repository** : Préfixe "I" + Nom d'entité + "Repository" (ex: `IUserRepository`, `ITripRepository`)
- **Implémentations de Repository** : Nom d'entité + "Repository" (ex: `UserRepository`, `TripRepository`)

### Méthodes des Repositories
- **CRUD basique** : `findById()`, `findAll()`, `create()`, `update()`, `delete()`
- **Requêtes spécialisées** : Verbe + Par + Critère (ex: `findByLocation()`, `findByDateRange()`, `countByStatus()`)

## 4. Gestion des Erreurs

Notre approche de gestion des erreurs différencie les types d'exception :

### Hiérarchie d'Exceptions
- `DALException` : Classe de base pour toutes les exceptions de la DAL
  - `ConnectionException` : Problèmes de connexion aux bases
  - `ValidationException` : Données invalides avant persistance
  - `PersistenceException` : Échec des opérations de CRUD
  - `QueryException` : Erreurs de requêtes ou problèmes de filtrage

### Standards de Messages
- Messages clairs et normalisés par type d'erreur
- Format : "[Type de ressource] - [Action] - [Raison]"
- Exemples : "USER - CREATE - Email already exists", "TRIP - UPDATE - Invalid date format"

### Journalisation
- Erreurs critiques : Journalisation détaillée immédiate, notification possible
- Erreurs connues : Journalisation basique pour debug
- Utilisation de niveaux de logs adéquats (INFO, WARNING, ERROR, CRITICAL)

## 5. Flux de Traitement des Données

1. **Controller** reçoit une requête du client
2. **Service** traite la logique métier et sollicite le repository approprié
3. **Repository** convertit les objets métier en instructions de persistance :
   - Pour SQL : Conversion en requêtes préparées via PDO
   - Pour NoSQL : Transformation en documents pour le driver MongoDB
4. **Repository** traite les résultats de la base et les convertit en entités métier
5. **Service** enrichit éventuellement les données avec d'autres sources
6. **Controller** formate la réponse finale pour le client

## 6. Optimisations pour l'Éco-conception

- **Connexions poolées** : Réutilisation des connexions DB existantes
- **Requêtes optimisées** : Récupération sélective des champs nécessaires uniquement
- **Pagination systématique** : Limitation du volume de données transférées
- **Mise en cache** : Mécanisme de cache pour les requêtes fréquentes mais peu variables
- **Transactions** : Utilisation judicieuse pour garantir la cohérence avec un minimum d'opérations

## 7. Interactions entre SQL et NoSQL

Les repositories peuvent interagir entre eux via les services :

```
[SQL Repository] ← → [Service] ← → [NoSQL Repository]
```

Exemple de flux pour un covoiturage et ses avis :
1. `TripRepository` (SQL) récupère les données du covoiturage
2. `TripService` enrichit ces données en appelant `ReviewRepository` (NoSQL)
3. Les deux jeux de données sont fusionnés au niveau service, non au niveau repository 