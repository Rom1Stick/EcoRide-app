# Implémentation des Repositories MySQL - EcoRide

## Vue d'ensemble

Cette documentation détaille l'implémentation des repositories MySQL pour connecter l'architecture orientée objet d'EcoRide aux tables de base de données existantes. L'implémentation respecte les principes SOLID et maintient une compatibilité totale avec le schéma de base de données actuel.

## Structure des Repositories

### 1. Architecture en Couches

```
Infrastructure/
├── Repositories/           # Implémentations concrètes
│   ├── MySQLRideRepository.php
│   └── MySQLLocationRepository.php
├── Persistence/           # Mappers pour conversion SQL ↔ Entités
│   ├── RideMapper.php
│   ├── UserMapper.php
│   ├── LocationMapper.php
│   └── VehicleMapper.php
├── Database/             # Abstraction base de données
│   └── DatabaseAdapter.php
├── Factories/           # Factory pour injection de dépendances
│   └── RepositoryFactory.php
└── Examples/           # Exemples d'utilisation
    └── RepositoryUsageExample.php
```

### 2. Tables de Base de Données Mappées

| Table MySQL | Entité Domain | Repository | Mapper |
|-------------|---------------|------------|---------|
| `Covoiturage` | `Ride` | `MySQLRideRepository` | `RideMapper` |
| `Utilisateur` | `User` | Intégré dans RideMapper | `UserMapper` |
| `Lieu` | `Location` (VO) | `MySQLLocationRepository` | `LocationMapper` |
| `Voiture` | Vehicle (objet simple) | Intégré | `VehicleMapper` |

## Implémentations Détaillées

### MySQLRideRepository

**Fonctionnalités principales :**
- Recherche par ID, conducteur, critères multiples
- Pagination et tri des résultats
- Comptage des résultats
- Sauvegarde et suppression
- Trajets populaires et disponibles

**Requêtes SQL optimisées :**
```sql
-- Exemple de requête complexe avec JOINs
SELECT 
    c.covoiturage_id, c.date_depart, c.heure_depart,
    ld.nom AS lieu_depart, la.nom AS lieu_arrivee,
    u.pseudo, u.email, IFNULL(AVG(a.note), 0) AS note_moyenne,
    -- Calcul des places disponibles en temps réel
    (c.nb_place - (
        SELECT COUNT(*) FROM Participation p
        WHERE p.covoiturage_id = c.covoiturage_id
        AND p.statut_id = (SELECT statut_id FROM StatutParticipation WHERE libelle = 'confirmé')
    )) AS places_disponibles
FROM Covoiturage c
JOIN Lieu ld ON c.lieu_depart_id = ld.lieu_id
-- ... autres JOINs
```

**Méthodes clés :**
- `findById(int $id): ?Ride`
- `findByDriver(User $driver): array`
- `searchRides(?Location $departure, ?Location $arrival, ?\DateTime $date): array`
- `save(Ride $ride): void`
- `delete(Ride $ride): void`

### MySQLLocationRepository

**Fonctionnalités :**
- Recherche par nom (exacte et partielle)
- Création automatique de nouveaux lieux
- Gestion des coordonnées GPS
- Lieux populaires (statistiques d'utilisation)

**Méthodes :**
- `findById(int $id): ?Location`
- `findByName(string $name): ?Location`
- `searchByName(string $searchTerm, int $limit): array`
- `findOrCreate(string $name): Location`
- `updateCoordinates(int $locationId, float $lat, float $lng): void`

## Mappers de Données

### RideMapper
Convertit les résultats SQL complexes en entités `Ride` complètes :
```php
public function mapToEntity(array $data): Ride
{
    $departure = $this->locationMapper->mapToLocation(/* ... */);
    $arrival = $this->locationMapper->mapToLocation(/* ... */);
    $driver = $this->userMapper->mapToEntity($data);
    $vehicle = $this->vehicleMapper->mapToVehicle($data);
    
    $departureDateTime = new DateTime($data['date_depart'] . ' ' . $data['heure_depart']);
    $pricePerPerson = new Money((float) $data['prix_personne']);
    
    return new Ride(/* parametres */);
}
```

### Gestion des Status
Mapping bidirectionnel entre enum `RideStatus` et table `StatutCovoiturage` :
```php
private function mapStatus(string $statusLabel): RideStatus
{
    return match (strtolower($statusLabel)) {
        'planifié' => RideStatus::PLANNED,
        'en cours' => RideStatus::IN_PROGRESS,
        'terminé' => RideStatus::COMPLETED,
        'annulé' => RideStatus::CANCELLED,
        default => RideStatus::PLANNED
    };
}
```

## Intégration avec l'Architecture Existante

### DatabaseAdapter
Pont entre la classe `Database` existante et l'interface `DatabaseInterface` :
```php
class DatabaseAdapter implements DatabaseInterface
{
    private Database $database;
    private PDO $connection;

    public function __construct(Database $database)
    {
        $this->database = $database;
        $this->connection = $this->database->getMysqlConnection();
    }
    
    // Implémentation des méthodes de l'interface...
}
```

### RepositoryFactory
Factory pattern pour l'injection de dépendances :
```php
public static function createFromLegacyDatabase(Database $legacyDatabase, Logger $logger): self
{
    $databaseAdapter = new DatabaseAdapter($legacyDatabase);
    return new self($databaseAdapter, $logger);
}
```

## Utilisation Pratique

### Dans un Contrôleur Refactorisé
```php
class RideController extends Controller
{
    private RideRepositoryInterface $rideRepository;
    private RideManagementService $rideManagementService;

    public function __construct(Application $app)
    {
        parent::__construct();
        
        $logger = new Logger();
        $repositoryFactory = RepositoryFactory::createFromLegacyDatabase(
            $app->getDatabase(),
            $logger
        );
        
        $this->rideRepository = $repositoryFactory->createRideRepository();
        $this->rideManagementService = new RideManagementService($this->rideRepository);
    }

    public function index(): array
    {
        try {
            $rides = $this->rideManagementService->getAvailableRides(20);
            return $this->success(['rides' => $this->formatRides($rides)]);
        } catch (Exception $e) {
            return $this->error('Erreur lors de la récupération des trajets', 500);
        }
    }
}
```

## Avantages de cette Implémentation

### 1. **Compatibilité Totale**
- Aucune modification du schéma de base de données
- Utilise les tables et relations existantes
- Compatible avec l'application en cours de fonctionnement

### 2. **Performance Optimisée**
- Requêtes SQL optimisées avec JOINs efficaces
- Calculs de places disponibles en temps réel
- Pagination intégrée pour les grosses listes

### 3. **Maintenance Facilitée**
- Séparation claire entre logique métier et accès aux données
- Mappers réutilisables et testables
- Interface abstraite pour future migration

### 4. **Extensibilité**
- Factory pattern pour injection de dépendances
- Interface permettant de changer d'implémentation
- Structure modulaire pour ajout de nouvelles fonctionnalités

## Migration Progressive

### Phase 1 : Coexistence
```php
// Ancien code (garde compatibilité)
$rideService = new RideService($app);
$rides = $rideService->getAllRides();

// Nouveau code (architecture OO)
$repositoryFactory = RepositoryFactory::createFromLegacyDatabase($app->getDatabase(), $logger);
$rideRepository = $repositoryFactory->createRideRepository();
$rides = $rideRepository->findAvailableRides();
```

### Phase 2 : Migration Contrôleur par Contrôleur
1. Créer nouveau contrôleur avec repositories
2. Tester en parallèle
3. Basculer les routes
4. Supprimer l'ancien code

### Phase 3 : Optimisation
- Ajout de cache Redis pour les requêtes fréquentes
- Optimisation des requêtes SQL complexes
- Monitoring des performances

## Tests et Validation

### Tests Unitaires
```php
class MySQLRideRepositoryTest extends TestCase
{
    private MySQLRideRepository $repository;
    
    public function testFindById(): void
    {
        $ride = $this->repository->findById(1);
        $this->assertNotNull($ride);
        $this->assertInstanceOf(Ride::class, $ride);
    }
}
```

### Tests d'Intégration
- Validation mapping SQL ↔ Entités
- Tests de performance sur gros volumes
- Vérification compatibilité avec données existantes

## Monitoring et Logs

### Logging Structuré
```php
$this->logger->info('Trajet créé', [
    'ride_id' => $ride->getId(),
    'departure' => $ride->getDeparture()->getName(),
    'arrival' => $ride->getArrival()->getName(),
    'driver_id' => $ride->getDriver()->getId()
]);
```

### Métriques de Performance
- Temps de réponse des requêtes
- Nombre de trajets traités par seconde
- Taux d'erreur des repositories

## Conclusion

Cette implémentation des repositories MySQL offre une transition en douceur vers une architecture orientée objet moderne tout en préservant la stabilité de l'application existante. Elle constitue la fondation technique pour les futures évolutions d'EcoRide. 