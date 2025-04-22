# Guide de la couche d'accès aux données (DAL) - EcoRide

## Introduction

Ce document décrit l'utilisation des repositories SQL et des services NoSQL pour accéder aux données dans l'application EcoRide. La couche d'accès aux données (DAL) fournit une interface unifiée pour interagir avec les bases de données MySQL et MongoDB.

## Utilisation de base

### Repositories SQL (MySQL)

Les repositories fournissent des méthodes pour créer, lire, mettre à jour et supprimer des entités dans la base de données MySQL.

#### Exemple d'utilisation du UserRepository

```php
// Obtenir une instance du repository
$userRepository = new UserRepository($database);

// Rechercher un utilisateur par ID
$user = $userRepository->findById(123);
if ($user) {
    echo "Utilisateur trouvé: " . $user->getFullName();
} else {
    echo "Utilisateur non trouvé";
}

// Créer un nouvel utilisateur
$newUser = new User();
$newUser->setEmail('john.doe@example.com')
        ->setPassword(password_hash('password123', PASSWORD_DEFAULT))
        ->setFirstName('John')
        ->setLastName('Doe')
        ->setPhone('+33612345678')
        ->setRole('ROLE_USER');

try {
    $userId = $userRepository->create($newUser);
    echo "Utilisateur créé avec l'ID: " . $userId;
} catch (DataAccessException $e) {
    echo "Erreur lors de la création de l'utilisateur: " . $e->getMessage();
}

// Mettre à jour un utilisateur
$user->setPhone('+33687654321');
$success = $userRepository->update($user);

// Supprimer un utilisateur
$success = $userRepository->delete($userId);

// Rechercher par email
$user = $userRepository->findByEmail('john.doe@example.com');

// Rechercher par nom
$users = $userRepository->searchByName('Doe', 1, 20); // page 1, 20 résultats par page
```

### Services NoSQL (MongoDB)

Les services NoSQL fournissent des méthodes pour interagir avec les collections MongoDB.

#### Exemple d'utilisation du UserPreferenceService

```php
// Obtenir une instance du service
$connection = new MongoConnection();
$preferenceService = new UserPreferenceService($connection);

// Trouver les préférences d'un utilisateur
$preferences = $preferenceService->findByUserId(123);
if ($preferences) {
    $musicPreference = $preferences->getStandardPreference('musique', 'jazz');
    echo "Préférence musicale: " . $musicPreference;
} else {
    echo "Aucune préférence trouvée";
}

// Créer ou mettre à jour des préférences
$newPreferences = new UserPreference();
$newPreferences->setUserId(123);
$newPreferences->setStandardPreference('musique', 'rock');
$newPreferences->setStandardPreference('animaux', true);
$newPreferences->setCustomPreference('arrets', false);

$result = $preferenceService->savePreference($newPreferences);

// Mettre à jour une préférence spécifique
$success = $preferenceService->updateStandardPreference(123, 'climatisation', 'auto');
$success = $preferenceService->updateCustomPreference(123, 'conversation', 'modéré');

// Supprimer une préférence
$success = $preferenceService->deleteStandardPreference(123, 'musique');
$success = $preferenceService->deleteCustomPreference(123, 'arrets');

// Rechercher des utilisateurs par préférence
$usersPreferences = $preferenceService->findByPreferenceValue('animaux', true);
```

## Méthodes communes

### Repositories SQL

| Méthode | Description | Signature |
|---------|-------------|-----------|
| `findById` | Trouve une entité par son ID | `findById(int $id)` |
| `create` | Crée une nouvelle entité | `create($entity): int` |
| `update` | Met à jour une entité existante | `update($entity): bool` |
| `delete` | Supprime une entité par son ID | `delete(int $id): bool` |
| `findAll` | Récupère toutes les entités avec pagination | `findAll(int $page = 1, int $limit = 20): array` |
| `count` | Compte le nombre total d'entités | `count(): int` |

### Services NoSQL

| Méthode | Description | Signature |
|---------|-------------|-----------|
| `findById` | Trouve un document par son ID | `findById($id)` |
| `insert` | Insère un nouveau document | `insert(array $data): ObjectId` |
| `update` | Met à jour un document existant | `update($id, array $data): bool` |
| `delete` | Supprime un document par son ID | `delete($id): bool` |
| `find` | Recherche des documents selon des critères | `find(array $criteria = [], array $options = []): array` |
| `count` | Compte le nombre de documents correspondant aux critères | `count(array $criteria = []): int` |

## Entités et modèles disponibles

### Entités SQL

- **User** : Utilisateur du système
  - Propriétés : id, email, password, firstName, lastName, phone, role, createdAt, updatedAt
  - Repository : `UserRepository`

### Modèles NoSQL

- **UserPreference** : Préférences utilisateur
  - Propriétés : id, userId, standard, custom, lastUpdated
  - Service : `UserPreferenceService`

## Gestion des erreurs

Toutes les erreurs liées à l'accès aux données sont encapsulées dans la classe `DataAccessException`. Cette exception contient des informations sur l'origine de l'erreur (SQL ou NoSQL) et peut être utilisée pour déterminer si l'erreur est liée à une connexion ou à une requête.

```php
try {
    $user = $userRepository->findById(123);
} catch (DataAccessException $e) {
    if ($e->isConnectionError()) {
        // Problème de connexion à la base de données
        echo "Impossible de se connecter à la base de données : " . $e->getMessage();
    } else {
        // Autre type d'erreur
        echo "Erreur lors de l'accès aux données : " . $e->getMessage();
    }
    
    // Journalisation de l'erreur
    error_log("Erreur DAL (" . $e->getDbType() . "): " . $e->getMessage());
}
```

## Transactions

Pour les opérations qui nécessitent une cohérence transactionnelle dans MySQL, vous pouvez utiliser la méthode suivante :

```php
try {
    $pdo = $database->getMysqlConnection();
    $pdo->beginTransaction();
    
    // Opérations sur la base de données
    $userId = $userRepository->create($user);
    $creditRepository->createInitialBalance($userId);
    
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    throw new DataAccessException("Erreur lors de la transaction : " . $e->getMessage(), 0, $e);
}
```

## Pagination

La pagination est intégrée dans les méthodes de recherche pour limiter la taille des résultats :

```php
// Récupérer la page 2 avec 10 résultats par page
$users = $userRepository->findAll(2, 10);

// Calculer le nombre total de pages
$totalUsers = $userRepository->count();
$totalPages = ceil($totalUsers / 10);
```

## Injection de dépendances

Il est recommandé d'utiliser l'injection de dépendances pour obtenir des instances de repositories et de services. Voici un exemple avec un container simple :

```php
class Container {
    private array $instances = [];
    
    public function get(string $class) {
        if (!isset($this->instances[$class])) {
            $this->instances[$class] = $this->create($class);
        }
        
        return $this->instances[$class];
    }
    
    private function create(string $class) {
        if ($class === UserRepository::class) {
            return new UserRepository($this->get(Database::class));
        }
        
        if ($class === UserPreferenceService::class) {
            return new UserPreferenceService($this->get(MongoConnection::class));
        }
        
        if ($class === Database::class) {
            return new Database();
        }
        
        if ($class === MongoConnection::class) {
            return new MongoConnection();
        }
        
        throw new \InvalidArgumentException("Classe non gérée: $class");
    }
}

// Utilisation
$container = new Container();
$userRepository = $container->get(UserRepository::class);
```

## Bonnes pratiques

1. **Validation** : Validez toujours les données avant de les envoyer au repository
2. **Transactions** : Utilisez des transactions pour les opérations qui impliquent plusieurs tables
3. **Pagination** : Utilisez systématiquement la pagination pour les listes de résultats
4. **Erreurs** : Attrapez et gérez correctement les DataAccessException
5. **Injection** : Injectez les dépendances plutôt que de les créer directement
6. **Requêtes complexes** : Pour les requêtes très complexes, considérez l'utilisation de procédures stockées

## Conclusion

La DAL fournit une interface unifiée et cohérente pour accéder aux données, qu'elles soient stockées dans MySQL ou MongoDB. En suivant les exemples et bonnes pratiques décrits dans ce document, vous pouvez interagir avec les données de manière sécurisée et efficace. 