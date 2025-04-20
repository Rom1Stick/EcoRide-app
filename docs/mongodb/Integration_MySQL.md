# Intégration MySQL et MongoDB dans EcoRide

Ce document explique comment faire cohabiter les bases de données MySQL et MongoDB dans l'application EcoRide.

## Principes fondamentaux

### 1. Séparation des responsabilités

| Base | Responsabilité | Types de données |
|------|----------------|------------------|
| **MySQL** | Source de vérité pour les données structurées | Utilisateurs, covoiturages, transactions, voitures |
| **MongoDB** | Stockage flexible pour données semi-structurées | Préférences, logs, analytics, données géospatiales, configurations |

### 2. Référencement croisé

Les collections MongoDB référencent les données MySQL via leurs identifiants :

- `userId` dans MongoDB → `utilisateur_id` dans MySQL
- `covoiturageId` dans MongoDB → `covoiturage_id` dans MySQL

## Stratégies d'intégration

### Pour créer un utilisateur

```
┌──────────────┐         ┌──────────────┐
│              │         │              │
│     PHP      │  MySQL  │   Base de    │
│  Controller  │ ------► │   données    │
│              │         │   MySQL      │
│              │         │              │
└──────┬───────┘         └──────────────┘
       │
       │ Récupère l'ID généré
       │
       ▼
┌──────────────┐         ┌──────────────┐
│              │         │              │
│    MongoDB   │ MongoDB │   Base de    │
│   Service    │ ------► │   données    │
│              │         │   MongoDB    │
│              │         │              │
└──────────────┘         └──────────────┘
```

**Exemple de code** :

```php
// 1. Insertion dans MySQL
$stmt = $pdo->prepare("INSERT INTO Utilisateur (nom, prenom, email) VALUES (?, ?, ?)");
$stmt->execute([$nom, $prenom, $email]);
$userId = $pdo->lastInsertId();

// 2. Création du document de préférences dans MongoDB
$mongoCollection = $mongoDb->selectCollection('preferences');
$mongoCollection->insertOne([
    'userId' => (int)$userId,  // Référence à l'ID MySQL
    'standard' => [
        'musique' => 'jazz',
        'animaux' => false,
        'fumeur' => false
    ],
    'custom' => [],
    'lastUpdated' => new MongoDB\BSON\UTCDateTime()
]);
```

### Pour supprimer un utilisateur

```php
// Utilisation d'une transaction MySQL pour garantir l'atomicité
try {
    $pdo->beginTransaction();
    
    // 1. Supprimer l'utilisateur de MySQL (avec ON DELETE CASCADE pour les tables liées)
    $stmt = $pdo->prepare("DELETE FROM Utilisateur WHERE utilisateur_id = ?");
    $stmt->execute([$userId]);
    
    // 2. Supprimer les documents liés dans MongoDB
    $mongoDb->preferences->deleteOne(['userId' => (int)$userId]);
    
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    // Gérer l'erreur
}
```

## Synchronisation des données

### Approche 1 : Synchronisation directe

Chaque opération sur MySQL qui affecte des données aussi stockées dans MongoDB doit immédiatement mettre à jour la base MongoDB.

**Avantages** :
- Cohérence forte
- Simplicité conceptuelle

**Inconvénients** :
- Couplage fort entre les opérations
- Risque de problèmes si MongoDB est indisponible

### Approche 2 : Synchronisation par événements (recommandée)

Utiliser un système de files d'attente pour propager les événements entre les bases.

```
┌──────────────┐         ┌──────────────┐
│              │         │              │
│     PHP      │         │   Base de    │
│  Controller  │ ------► │   données    │
│              │         │   MySQL      │
│              │         │              │
└──────┬───────┘         └──────────────┘
       │
       │ Publie un événement
       │
       ▼
┌──────────────┐
│              │
│  File        │
│  d'attente   │
│  (RabbitMQ)  │
│              │
└──────┬───────┘
       │
       │ Consomme l'événement
       │
       ▼
┌──────────────┐         ┌──────────────┐
│              │         │              │
│  Worker      │         │   Base de    │
│  MongoDB     │ ------► │   données    │
│              │         │   MongoDB    │
│              │         │              │
└──────────────┘         └──────────────┘
```

**Exemple d'implémentation** :

1. Création d'un utilisateur :

```php
// 1. Insertion dans MySQL
$stmt = $pdo->prepare("INSERT INTO Utilisateur (nom, prenom, email) VALUES (?, ?, ?)");
$stmt->execute([$nom, $prenom, $email]);
$userId = $pdo->lastInsertId();

// 2. Publication de l'événement
$messageBroker->publish('user.created', [
    'userId' => $userId,
    'email' => $email
]);
```

2. Worker qui traite l'événement :

```php
$messageBroker->consume('user.created', function($event) use ($mongoDb) {
    $mongoDb->preferences->insertOne([
        'userId' => (int)$event['userId'],
        'standard' => [
            'musique' => 'jazz',
            'animaux' => false,
            'fumeur' => false
        ],
        'custom' => [],
        'lastUpdated' => new MongoDB\BSON\UTCDateTime()
    ]);
});
```

## Requêtes combinant les deux sources

Pour les requêtes qui nécessitent des données des deux bases, l'approche recommandée est :

1. Effectuer la requête principale dans MySQL
2. Récupérer les IDs des entités concernées
3. Effectuer une requête complémentaire dans MongoDB avec ces IDs
4. Fusionner les résultats dans l'application

**Exemple** : Profil utilisateur avec préférences

```php
// 1. Récupérer les données utilisateur depuis MySQL
$stmt = $pdo->prepare("SELECT * FROM Utilisateur WHERE utilisateur_id = ?");
$stmt->execute([$userId]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Récupérer les préférences depuis MongoDB
$preferences = $mongoDb->preferences->findOne(['userId' => (int)$userId]);

// 3. Fusionner les données pour l'API
$profile = [
    'id' => $userData['utilisateur_id'],
    'nom' => $userData['nom'],
    'prenom' => $userData['prenom'],
    'email' => $userData['email'],
    'preferences' => [
        'musique' => $preferences['standard']['musique'] ?? null,
        'animaux' => $preferences['standard']['animaux'] ?? false,
        'fumeur' => $preferences['standard']['fumeur'] ?? false,
        'custom' => $preferences['custom'] ?? []
    ]
];
```

## Gestion de la cohérence

La cohérence entre les deux bases est gérée selon ces principes :

1. **Cohérence forte pour les données critiques** (utilisateurs, covoiturages)
   - Synchronisation immédiate ou par événements avec confirmation

2. **Cohérence éventuelle pour les données non-critiques** (logs, analytics)
   - Asynchrone sans confirmation stricte
   - Tolère les délais de propagation

## Optimisations et performances

1. **Mise en cache** des identifiants fréquemment utilisés
2. **Agrégation** des opérations MongoDB pour limiter les allers-retours
3. **Indexation** adaptée aux patterns d'accès

## En résumé

L'architecture hybride MySQL/MongoDB d'EcoRide exploite les forces de chaque système :

- **MySQL** : Transactions ACID, intégrité référentielle, modèle relationnel
- **MongoDB** : Flexibilité du schéma, performances en lecture, requêtes géospatiales

Cette approche permet d'avoir le meilleur des deux mondes tout en maintenant une cohérence satisfaisante pour les besoins métier. 