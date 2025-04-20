# Structure de la Base de Données EcoRide

Ce répertoire contient les scripts SQL et la documentation liés à la base de données relationnelle et NoSQL du projet EcoRide.

## Organisation des dossiers

- **schema/** : Structure de base complète
  - `schema.sql` : Script complet de création (tables, index, données de référence)

- **scripts/** : Scripts SQL modulaires pour déploiement progressif
  - `01_create_database.sql` : Création de la base et de l'utilisateur
  - `02_create_tables.sql` : Création des tables uniquement
  - `03_create_indexes.sql` : Création des index d'optimisation
  - `04_clean_database.sql` : Suppression des tables (pour réinitialisation)
  - `05_insert_ref_data.sql` : Insertion des données de référence
  - `06_create_triggers_and_funcs.sql` : Triggers et fonctions pour l'intégrité des données
  - `07_test_data.sql` : Jeu de données pour le développement
  - `08_optimize_database.sql` : Optimisations pour la production
  - `09_create_views.sql` : Vues SQL pour simplifier les requêtes front-end
  - `10_validation_tests.sql` : Tests de validation de la structure et des données

- **mongodb/** : Configurations et schémas pour MongoDB
  - `schemas.js` : Schémas de validation MongoDB 
  - `indexes.js` : Index MongoDB
  - `sample_data.js` : Données d'exemple MongoDB
  - `queries.js` : Requêtes MongoDB d'exemple

- **queries/** : Exemples de requêtes SQL
  - `test-queries.sql` : Requêtes de test avec données d'exemples

## Utilisation des scripts

### Installation initiale

Pour initialiser la base de données, exécutez les scripts dans cet ordre :

```bash
mysql < scripts/01_create_database.sql
mysql ecoride < scripts/02_create_tables.sql
mysql ecoride < scripts/03_create_indexes.sql
mysql ecoride < scripts/05_insert_ref_data.sql
mysql ecoride < scripts/06_create_triggers_and_funcs.sql
mysql ecoride < scripts/09_create_views.sql
```

### Environnement de développement

Pour ajouter des données de test :

```bash
mysql ecoride < scripts/07_test_data.sql
```

### Réinitialisation 

Pour nettoyer et réinitialiser la base de données :

```bash
mysql ecoride < scripts/04_clean_database.sql
mysql ecoride < scripts/02_create_tables.sql
mysql ecoride < scripts/03_create_indexes.sql
mysql ecoride < scripts/05_insert_ref_data.sql
mysql ecoride < scripts/06_create_triggers_and_funcs.sql
mysql ecoride < scripts/09_create_views.sql
```

### Configuration de production

Optimisations pour la production :

```bash
mysql ecoride < scripts/08_optimize_database.sql
```

### Validation de la base de données

Pour vérifier l'intégrité et la cohérence de la base de données :

```bash
mysql ecoride < scripts/10_validation_tests.sql
```

## Architecture hybride SQL/NoSQL

EcoRide utilise une approche hybride de stockage des données :

- **MySQL** : Stockage principal des données structurées (utilisateurs, covoiturages, transactions)
- **MongoDB** : Stockage complémentaire pour les données semi-structurées :
  - Préférences utilisateurs personnalisées
  - Données analytiques
  - Logs d'activité
  - Géolocalisation détaillée
  - Configuration système

## Bonnes pratiques

1. Toujours utiliser les transactions (BEGIN/COMMIT) pour les insertions groupées
2. Respecter la convention de nommage établie
3. Documenter tout changement de schéma
4. Tester les requêtes complexes avant implémentation
5. Maintenir les index à jour pour optimiser les performances

## Documentation

La documentation complète de la base de données se trouve dans le répertoire `docs/`:

- `docs/database/data-dictionary.md` : Dictionnaire détaillé des tables et colonnes
- `docs/architecture/mcd.md` : Description du modèle conceptuel de données

## Outils recommandés

- MySQL Workbench : Pour la modélisation et la gestion visuelle
- DBeaver : Pour l'exploration et les requêtes ad hoc
- HeidiSQL : Alternative légère pour Windows 