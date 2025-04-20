# Structure de la Base de Données EcoRide

Ce répertoire contient les scripts SQL et la documentation liés à la base de données relationnelle du projet EcoRide.

## Organisation des dossiers

- **schema/** : Scripts de création de la structure de la base de données
  - `schema.sql` : Script principal de création de toutes les tables, index et contraintes

- **seeds/** : Scripts d'insertion de données
  - Peut contenir des jeux de données pour différents environnements (dev, test, prod)

- **queries/** : Exemples de requêtes SQL
  - `test-queries.sql` : Requêtes de test avec données d'exemples et cas d'usage courants

## Documentation

La documentation complète de la base de données se trouve dans le répertoire `docs/`:

- `docs/database/data-dictionary.md` : Dictionnaire détaillé des tables et colonnes
- `docs/architecture/mcd.md` : Description du modèle conceptuel de données

## Bonnes pratiques

1. Toujours utiliser les transactions (BEGIN/COMMIT) pour les insertions groupées
2. Respecter la convention de nommage établie
3. Documenter tout changement de schéma
4. Tester les requêtes complexes avant implémentation
5. Maintenir les index à jour pour optimiser les performances

## Outils recommandés

- MySQL Workbench : Pour la modélisation et la gestion visuelle
- DBeaver : Pour l'exploration et les requêtes ad hoc
- HeidiSQL : Alternative légère pour Windows 