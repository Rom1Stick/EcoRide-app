# Architecture Orientée Objet - EcoRide

## 🏗️ Vue d'ensemble

Ce document présente la migration de l'application EcoRide vers une architecture orientée objet robuste, basée sur les principes SOLID et les design patterns éprouvés.

## 📁 Structure de la nouvelle architecture

```
backend/app/
├── Domain/                    # Couche métier (Business Logic)
│   ├── Entities/             # Entités métier
│   │   ├── Ride.php          # Trajet de covoiturage
│   │   ├── User.php          # Utilisateur
│   │   ├── Vehicle.php       # Véhicule
│   │   └── Participation.php # Participation à un trajet
│   ├── ValueObjects/         # Value Objects (objets valeur)
│   │   ├── Money.php         # Montant monétaire
│   │   ├── Location.php      # Lieu géographique
│   │   └── Email.php         # Adresse email
│   ├── Enums/               # Énumérations typées
│   │   └── RideStatus.php    # Statuts des trajets
│   ├── Services/            # Services métier
│   │   └── RideManagementService.php
│   ├── Repositories/        # Interfaces des repositories
│   │   └── RideRepositoryInterface.php
│   └── Exceptions/          # Exceptions métier
│       ├── RideNotFoundException.php
│       ├── BookingException.php
│       └── UnauthorizedException.php
├── Infrastructure/           # Couche infrastructure
│   ├── Repositories/        # Implémentations des repositories
│   └── Persistence/         # Mappers et adaptateurs DB
├── Controllers/
│   └── Refactored/          # Nouveaux contrôleurs OO
│       └── RideController.php
└── Core/                    # Classes système existantes
```

## 🎯 Principes appliqués

### 1. **SOLID Principles**

#### **S - Single Responsibility Principle**
- Chaque classe a une seule responsabilité
- `Ride` : gère uniquement la logique d'un trajet
- `Money` : gère uniquement les montants monétaires
- `RideManagementService` : gère uniquement les opérations sur les trajets

#### **O - Open/Closed Principle**
- Extensions possibles sans modification du code existant
- Nouveaux types de véhicules via l'héritage
- Nouveaux statuts via l'enum extensible

#### **L - Liskov Substitution Principle**
- Les interfaces peuvent être remplacées par leurs implémentations
- `RideRepositoryInterface` peut être implémentée par différents systèmes de persistance

#### **I - Interface Segregation Principle**
- Interfaces spécifiques et focalisées
- `RideRepositoryInterface` se concentre uniquement sur les trajets

#### **D - Dependency Inversion Principle**
- Dépendance sur les abstractions, pas les implémentations
- Services dépendent d'interfaces, pas de classes concrètes

### 2. **Design Patterns utilisés**

#### **Repository Pattern**
```php
interface RideRepositoryInterface
{
    public function findById(int $id): ?Ride;
    public function save(Ride $ride): void;
    // ...
}
```

#### **Value Object Pattern**
```php
final class Money
{
    private float $amount;
    private string $currency;
    
    public function add(Money $other): Money
    {
        // Logique métier encapsulée
    }
}
```

#### **Service Pattern**
```php
class RideManagementService
{
    public function __construct(RideRepositoryInterface $repository) {
        // Injection de dépendances
    }
}
```

#### **Factory Pattern (implicite)**
- Création d'objets complexes via les constructeurs
- Validation automatique lors de la création

## 🔄 Migration sans impact sur la base de données

### Principe de compatibilité
- Les nouvelles classes **mappent** les structures de données existantes
- Aucune modification de schéma de base de données requise
- Migration progressive possible

### Exemples de mapping

#### Entité Ride vers table existante
```php
// L'entité Ride map la table Covoiturage existante
class Ride {
    // Les propriétés correspondent aux colonnes existantes
    private int $id;           // -> covoiturage_id
    private Location $departure; // -> lieu_depart_id + nom du lieu
    private DateTime $departureDateTime; // -> date_depart + heure_depart
    // ...
}
```

#### Repository implementation
```php
class MySQLRideRepository implements RideRepositoryInterface 
{
    public function findById(int $id): ?Ride 
    {
        // Requête SQL sur les tables existantes
        $sql = "SELECT c.*, ld.nom as lieu_depart, la.nom as lieu_arrivee 
                FROM Covoiturage c 
                JOIN Lieu ld ON c.lieu_depart_id = ld.lieu_id
                JOIN Lieu la ON c.lieu_arrivee_id = la.lieu_id
                WHERE c.covoiturage_id = ?";
        
        // Conversion des données DB vers l'entité
        return $this->mapToEntity($result);
    }
}
```

## 🚀 Avantages de la nouvelle architecture

### 1. **Maintenabilité**
- Code plus lisible et organisé
- Responsabilités clairement définies
- Tests unitaires plus faciles

### 2. **Extensibilité**
- Ajout de nouvelles fonctionnalités sans impact sur l'existant
- Nouveaux types de véhicules, de statuts, etc.

### 3. **Robustesse**
- Validation automatique via les Value Objects
- Gestion des erreurs typée avec les exceptions métier
- Logique métier encapsulée dans les entités

### 4. **Performance**
- Pas de changement de schéma DB = pas d'impact performance
- Optimisations possibles au niveau repository
- Cache possible au niveau service

## 📋 Plan de migration progressive

### Phase 1 : Mise en place des fondations ✅
- [x] Création des entités de base
- [x] Création des Value Objects
- [x] Création des interfaces de repository
- [x] Création des services métier

### Phase 2 : Implémentation des repositories
- [ ] MySQLRideRepository
- [ ] MySQLUserRepository
- [ ] Tests d'intégration

### Phase 3 : Migration des contrôleurs
- [ ] Mise à jour progressive des contrôleurs existants
- [ ] Injection de dépendances via un container DI
- [ ] Tests fonctionnels

### Phase 4 : Optimisations
- [ ] Cache au niveau service
- [ ] Optimisation des requêtes
- [ ] Monitoring et logging avancé

## 🧪 Tests et validation

### Tests unitaires
```php
class RideTest extends TestCase 
{
    public function testBookSeatsSuccess()
    {
        $ride = new Ride(/* paramètres */);
        $passenger = new User(/* paramètres */);
        
        $ride->bookSeats($passenger, 2);
        
        $this->assertEquals(2, $ride->getTotalSeats() - $ride->getAvailableSeats());
    }
}
```

### Tests d'intégration
```php
class RideManagementServiceTest extends TestCase 
{
    public function testCreateRideSuccess()
    {
        $service = new RideManagementService($this->mockRepository);
        
        $ride = $service->createRide(/* paramètres */);
        
        $this->assertInstanceOf(Ride::class, $ride);
    }
}
```

## 📊 Métriques de qualité

### Avant la refactorisation
- **Couplage** : Fort (contrôleurs directement liés à la DB)
- **Cohésion** : Faible (logique métier dispersée)
- **Testabilité** : Difficile (dépendances hardcodées)

### Après la refactorisation
- **Couplage** : Faible (interfaces et injection de dépendances)
- **Cohésion** : Forte (responsabilités bien définies)
- **Testabilité** : Excellente (mocking facilité)

## 🔧 Outils et bonnes pratiques

### Static Analysis
```bash
composer require --dev phpstan/phpstan
./vendor/bin/phpstan analyze app/Domain
```

### Code Style
```bash
composer require --dev squizlabs/php_codesniffer
./vendor/bin/phpcs --standard=PSR-12 app/Domain
```

### Tests
```bash
composer require --dev phpunit/phpunit
./vendor/bin/phpunit tests/Domain
```

## 🎯 Conclusion

Cette nouvelle architecture orientée objet apporte :
- **Maintenabilité** accrue du code
- **Extensibilité** pour les évolutions futures
- **Robustesse** grâce aux validations intégrées
- **Performance** préservée (pas de changement DB)
- **Compatibilité** totale avec l'existant

La migration peut être effectuée progressivement, sans interruption de service et sans impact sur les utilisateurs finaux. 