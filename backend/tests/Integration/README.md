# Tests d'Intégration EcoRide

Ce répertoire contient les tests d'intégration pour l'application EcoRide. Ces tests vérifient l'interaction des classes et composants avec une base de données réelle.

## Structure des tests

Les tests sont organisés comme suit :

- `DatabaseTestCase.php` : Classe de base pour tous les tests d'intégration avec base de données
- `VehicleRepositoryTest.php` : Tests pour le repository des véhicules
- `TripRepositoryTest.php` : Tests pour le repository des covoiturages

## Préalables

Pour exécuter ces tests, vous devez disposer de :

1. PHP 8.2 ou supérieur
2. MySQL 8.0 ou supérieur
3. Composer
4. PHPUnit

## Configuration

1. Assurez-vous que MySQL est installé et en cours d'exécution
2. Créez une base de données de test avec le script `database/scripts/setup_test_db.sql` :
   ```bash
   mysql -u root < database/scripts/setup_test_db.sql
   ```
   ou avec PowerShell :
   ```powershell
   Get-Content database/scripts/setup_test_db.sql | mysql -u root
   ```

## Exécution des tests

### Utilisation des scripts

Nous fournissons des scripts pour faciliter l'exécution des tests :

#### Sous Linux/Mac :
```bash
cd backend
bash run-tests.sh
```

#### Sous Windows :
```powershell
cd backend
.\run-tests.ps1
```

### Exécution manuelle

Pour exécuter tous les tests d'intégration :
```bash
vendor/bin/phpunit --testsuite Integration
```

Pour exécuter un test spécifique :
```bash
vendor/bin/phpunit --filter VehicleRepositoryTest
```

## Bonnes pratiques

1. **Isolation** : Chaque test doit être indépendant des autres
2. **Données de test** : Utilisez les fixtures définies dans `DatabaseTestCase`
3. **Nettoyage** : La base de données est nettoyée automatiquement après chaque test
4. **Cohérence** : Vérifiez que vos tests utilisent la même structure de base de données que l'application
5. **Performance** : Minimisez les opérations de base de données dans les tests

## Ajout de nouveaux tests

Pour ajouter un nouveau test d'intégration :

1. Créez une nouvelle classe qui étend `DatabaseTestCase`
2. Définissez les fixtures nécessaires à vos tests
3. Implémentez les méthodes de test en suivant le modèle AAA (Arrange, Act, Assert)
4. Exécutez vos tests pour vérifier qu'ils fonctionnent correctement

## Dépannage

- **La base de données n'est pas créée** : Vérifiez vos permissions MySQL
- **Les tests échouent avec une erreur de connexion** : Vérifiez vos paramètres de connexion dans `phpunit.xml`
- **Les fixtures ne sont pas chargées** : Vérifiez la structure de votre tableau `$fixtures` 