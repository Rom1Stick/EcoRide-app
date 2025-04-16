# Tests unitaires et fonctionnels pour EcoRide 🧪🌱

Ce répertoire contient les tests automatisés pour l'application EcoRide. Les tests sont organisés en deux catégories principales : tests unitaires et tests fonctionnels, et sont conçus dans une optique d'éco-conception.

## Approche éco-responsable des tests

Notre stratégie de tests suit plusieurs principes d'éco-conception :

- **Tests ciblés** : Chaque test a un objectif précis, évitant les redondances
- **Base de données en mémoire** : Utilisation de SQLite en mémoire pour les tests (`:memory:`)
- **Isolation optimisée** : Préparation minimale de l'environnement de test
- **Conteneurs légers** : Image Docker optimisée pour l'exécution des tests
- **Détection précoce des problèmes** : Les tests évitent les déploiements inutiles qui consomment des ressources
- **Intégration continue efficiente** : Utilisation du cache et parallélisation quand possible

## Structure des tests

- `Unit/` : Tests unitaires qui testent des composants isolés sans dépendances externes
- `Feature/` : Tests fonctionnels qui testent des fonctionnalités complètes et des interactions

## Exécution des tests

### Avec Docker (recommandé)

Nous avons configuré un conteneur Docker spécifique pour exécuter les tests, ce qui évite d'avoir à installer des dépendances localement et garantit un environnement de test cohérent et reproductible.

Pour exécuter tous les tests :

```bash
docker-compose run --rm tests
```

Ou utilisez le script shell fourni :

```bash
./run-tests.sh
```

Options disponibles pour le script :

- `--unit` : Exécute uniquement les tests unitaires
- `--feature` : Exécute uniquement les tests fonctionnels
- `--coverage` : Génère un rapport de couverture de code
- `--testdox` : Affiche les résultats sous forme de documentation

Exemples :

```bash
./run-tests.sh --unit --coverage  # Exécute les tests unitaires avec rapport de couverture
./run-tests.sh --feature          # Exécute uniquement les tests fonctionnels
```

### Avec Composer

Vous pouvez également utiliser les scripts définis dans `composer.json` :

```bash
composer test            # Exécute tous les tests
composer test:unit       # Exécute uniquement les tests unitaires
composer test:feature    # Exécute uniquement les tests fonctionnels
composer test:coverage   # Génère un rapport de couverture
```

Pour utiliser Docker via composer :

```bash
composer docker:test
composer docker:test:unit
composer docker:test:feature
composer docker:test:coverage
```

### Localement

Si vous préférez exécuter les tests localement sans Docker, assurez-vous d'avoir PHPUnit installé :

```bash
cd backend
./vendor/bin/phpunit -c config/phpunit.xml
```

## Impact écologique des tests

Bien que les tests automatisés consomment des ressources à court terme, leur impact à long terme est positif sur le plan écologique :

- Prévention des bugs qui nécessiteraient des corrections coûteuses en ressources
- Détection des régressions de performance qui augmenteraient la consommation d'énergie
- Réduction des déploiements inutiles (qui consomment des ressources serveur)
- Validation du bon fonctionnement sur différents environnements (évite les incompatibilités)

## Conseils pour des tests éco-conçus

Lorsque vous ajoutez de nouveaux tests, suivez ces principes :

1. **Minimalisme** : Testez uniquement ce qui est nécessaire
2. **Rapidité** : Optimisez les tests pour qu'ils s'exécutent rapidement
3. **Isolation** : Assurez-vous que chaque test peut s'exécuter indépendamment
4. **Préparation efficiente** : Minimisez les opérations de configuration des tests
5. **Nettoyage** : Libérez les ressources après les tests

## Rapports de couverture

Les rapports de couverture HTML sont générés dans le répertoire `tests/coverage/`. Vous pouvez les consulter en ouvrant `tests/coverage/index.html` dans votre navigateur.

La couverture de code nous aide à identifier le code non testé et potentiellement problématique.

## Écrire de nouveaux tests

### Tests unitaires

Les tests unitaires doivent être placés dans le répertoire `Unit/` et étendre la classe `Tests\TestCase`. Exemple :

```php
<?php

namespace Tests\Unit;

use App\Core\Router;
use Tests\TestCase;

class RouterTest extends TestCase
{
    public function testCanCreateRoute(): void
    {
        $router = new Router();
        $route = $router->get('/test', 'TestController@index');
        
        $this->assertInstanceOf(Route::class, $route);
    }
}
```

### Tests fonctionnels

Les tests fonctionnels doivent être placés dans le répertoire `Feature/` et également étendre `Tests\TestCase`. Ces tests vérifient généralement le comportement de l'application de bout en bout.

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiTest extends TestCase
{
    public function testLoginEndpoint(): void
    {
        // Simuler une requête
        $this->mockRequest('POST', '/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);
        
        // ... vérifications
    }
}
```

## Intégration continue

Ces tests sont automatiquement exécutés dans le pipeline CI. Le script `composer test:ci` est utilisé pour générer un rapport de couverture au format XML, qui est ensuite envoyé à des outils d'analyse de couverture.

Notre pipeline CI est optimisé pour minimiser l'utilisation des ressources :
- Mise en cache des dépendances
- Parallélisation intelligente des tests
- Exécution conditionnelle basée sur les fichiers modifiés 