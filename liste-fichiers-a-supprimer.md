# Liste des fichiers à supprimer pour la version de production

## Fichiers de test du backend uniquement

- **`backend/tests/`** - Contient tous les tests unitaires, d'intégration et fonctionnels
- **`backend/.phpunit.cache/`** - Répertoire de cache des tests PHPUnit (si présent)
- **`backend/.phpunit.result.cache`** - Cache des résultats des tests PHPUnit (si présent)

## Note importante

Ces fichiers peuvent être supprimés uniquement pour une version de production finale car:
- Ils ne sont pas nécessaires au fonctionnement de l'application
- Ils sont utilisés uniquement pour vérifier la qualité du code pendant le développement
- Leur suppression permet de réduire légèrement la taille du package de déploiement

Tous les autres fichiers doivent être conservés car ils sont importants pour:
- Le développement continu
- Les processus de CI/CD
- La documentation
- La qualité du code
- La configuration du projet

Il est recommandé de créer une branche de production spécifique où ces fichiers seraient supprimés, tout en conservant la branche principale de développement avec tous les fichiers. 