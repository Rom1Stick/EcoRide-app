# Guide de Migration - Repositories MySQL

## Migration Rapide en 3 Étapes

### 1. Installation et Configuration

**Régénérer l'autoload Composer :**
```bash
cd backend
composer dump-autoload
```

**Vérifier les namespaces disponibles :**
- `App\Infrastructure\Repositories\*`
- `App\Infrastructure\Persistence\*`
- `App\Infrastructure\Database\*`
- `App\Infrastructure\Factories\*`

### 2. Modification du Contrôleur

**Avant (architecture legacy) :**
```php
class RideController extends Controller
{
    public function index(): array
    {
        $db = $this->app->getDatabase()->getMysqlConnection();
        $stmt = $db->prepare("SELECT * FROM Covoiturage...");
        // Logique SQL directe...
    }
}
```

**Après (architecture orientée objet) :**
```php
use App\Infrastructure\Factories\RepositoryFactory;
use App\Domain\Services\RideManagementService;

class RideController extends Controller
{
    private RideRepositoryInterface $rideRepository;
    private RideManagementService $rideService;

    public function __construct()
    {
        parent::__construct();
        
        $logger = new Logger();
        $factory = RepositoryFactory::createFromLegacyDatabase(
            $this->app->getDatabase(),
            $logger
        );
        
        $this->rideRepository = $factory->createRideRepository();
        $this->rideService = new RideManagementService($this->rideRepository);
    }

    public function index(): array
    {
        try {
            $rides = $this->rideService->getAvailableRides(20);
            return $this->success([
                'rides' => array_map([$this, 'formatRide'], $rides)
            ]);
        } catch (Exception $e) {
            return $this->error('Erreur lors de la récupération', 500);
        }
    }

    private function formatRide(Ride $ride): array
    {
        return [
            'id' => $ride->getId(),
            'departure' => $ride->getDeparture()->getName(),
            'arrival' => $ride->getArrival()->getName(),
            'departureTime' => $ride->getDepartureDateTime()->format('Y-m-d H:i'),
            'price' => $ride->getPricePerPerson()->getAmount(),
            'availableSeats' => $ride->getAvailableSeats(),
            'driver' => [
                'id' => $ride->getDriver()->getId(),
                'username' => $ride->getDriver()->getUsername(),
                'rating' => $ride->getDriver()->getAverageRating()
            ]
        ];
    }
}
```

### 3. Test de Fonctionnement

**Script de test simple :**
```php
// test_repositories.php
require_once 'vendor/autoload.php';

use App\Core\Application;
use App\Infrastructure\Factories\RepositoryFactory;
use App\Core\Logger;

try {
    $app = new Application();
    $logger = new Logger();
    
    $factory = RepositoryFactory::createFromLegacyDatabase(
        $app->getDatabase(),
        $logger
    );
    
    $rideRepository = $factory->createRideRepository();
    
    // Test de récupération d'un trajet
    $ride = $rideRepository->findById(1);
    if ($ride) {
        echo "✅ Repository fonctionne !\n";
        echo "Trajet: {$ride->getDeparture()->getName()} → {$ride->getArrival()->getName()}\n";
    } else {
        echo "❌ Aucun trajet trouvé avec l'ID 1\n";
    }
    
    // Test de recherche
    $availableRides = $rideRepository->findAvailableRides(5);
    echo "✅ Trajets disponibles: " . count($availableRides) . "\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
```

## Comparaison des Méthodes

| Opération | Legacy | Nouveau Repository |
|-----------|--------|-------------------|
| Recherche trajet | Requête SQL manuelle | `$repo->findById($id)` |
| Trajets disponibles | SQL complexe avec JOINs | `$repo->findAvailableRides()` |
| Filtrage | WHERE manuel | `$repo->searchRides($departure, $arrival, $date)` |
| Validation données | Manuelle/dispersée | Entités auto-validantes |
| Gestion erreurs | Try/catch basique | Exceptions typées |

## Points d'Attention

### ⚠️ Compatibilité
- Les repositories utilisent les **mêmes tables** que l'ancien code
- **Aucun changement** de schéma de base de données requis
- **Coexistence possible** entre ancien et nouveau code

### ⚠️ Performance
- Les requêtes sont **optimisées** avec JOINs appropriés
- **Pagination intégrée** pour éviter les surcharges mémoire
- **Calculs en temps réel** des places disponibles

### ⚠️ Migration Progressive
1. **Garder** l'ancien contrôleur fonctionnel
2. **Créer** le nouveau contrôleur en parallèle
3. **Tester** avec des routes de développement
4. **Basculer** les routes principales
5. **Supprimer** l'ancien code

## Résolution des Problèmes Courants

### Erreur "Class not found"
```bash
composer dump-autoload -o
```

### Erreur de connexion base de données
```php
// Vérifier que la classe Database existante fonctionne
$db = $app->getDatabase()->getMysqlConnection();
var_dump($db->getAttribute(PDO::ATTR_CONNECTION_STATUS));
```

### Mappage incorrect des données
```php
// Test unitaire simple du mapper
$mapper = new RideMapper($userMapper, $locationMapper, $vehicleMapper);
$testData = [
    'covoiturage_id' => 1,
    'lieu_depart' => 'Paris',
    'lieu_arrivee' => 'Lyon',
    // ... autres champs
];
$ride = $mapper->mapToEntity($testData);
assert($ride instanceof Ride);
```

## Migration Complète Recommandée

### Étape 1 : Controllers (Semaine 1)
- `RideController` ✅
- `UserController`
- `LocationController`

### Étape 2 : Services (Semaine 2)
- Logique métier complexe
- Calculs d'optimisation
- Notifications

### Étape 3 : Optimisations (Semaine 3)
- Cache Redis
- Monitoring performance
- Tests automatisés

## Support

En cas de problème :
1. Vérifier les logs dans `logs/`
2. Consulter la documentation complète `IMPLEMENTATION_REPOSITORIES_MYSQL.md`
3. Tester avec `RepositoryUsageExample.php`

**Migration réussie = Meilleure maintenabilité + Performance + Évolutivité** 🚀 