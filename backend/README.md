# Backend EcoRide 🌱

API backend légère et éco-conçue pour l'application EcoRide de covoiturage écologique.

## Approche d'éco-conception

Notre backend est conçu selon les principes d'éco-conception suivants :

- **Architecture légère** : Framework minimaliste fait maison sans dépendances lourdes
- **Empreinte mémoire réduite** : Optimisation des requêtes SQL et du cache
- **Double système de base de données** : MySQL pour les données critiques, SQLite pour les logs (économie de ressources)
- **Pagination systématique** : Limite la consommation de bande passante et de ressources serveur
- **Nettoyage automatique des données** : Politique de rétention et suppression automatique des données obsolètes
- **API optimisée** : Réponses JSON allégées avec seulement les champs nécessaires

## Outils de qualité de code

Le backend d'EcoRide implémente plusieurs outils pour garantir la qualité et la fiabilité du code :

- **PHPUnit** : Framework de tests unitaires et fonctionnels
- **API de test** : Infrastructure dédiée aux tests fonctionnels de l'API REST
- **Tests automatisés** : Tests exécutés automatiquement via GitHub Actions à chaque commit
- **Docker pour les tests** : Environnement de test isolé et reproductible

Ces outils permettent de valider systématiquement le comportement du backend et d'éviter les régressions lors de l'ajout de nouvelles fonctionnalités.

## Architecture

Architecture MVC légère et personnalisée avec les composants suivants :

- PHP 8.2 via PHP-FPM (optimisé pour la performance)
- MySQL 8.0 pour les données relationnelles (utilisateurs, trajets, réservations)
- SQLite embarqué pour les données non relationnelles (logs, cache, statistiques)
- API REST pour l'authentification, les trajets, les réservations, etc.
- Dockerisé pour un déploiement facile et une configuration cohérente

## Structure du projet

```
backend/
├── app/
│   ├── Controllers/    # Contrôleurs pour gérer les requêtes
│   ├── Models/         # Modèles de données
│   ├── Services/       # Services métier
│   ├── Repositories/   # Couche d'accès aux données
│   ├── Middlewares/    # Middlewares pour filtrer les requêtes
│   └── Core/           # Noyau du framework MVC
├── config/             # Fichiers de configuration
│   └── phpunit.xml     # Configuration des tests
├── public/             # Point d'entrée public
│   └── index.php       # Front controller
├── routes/             # Définitions des routes
│   └── api.php         # Routes de l'API
├── storage/            # Stockage des données
│   └── data.sqlite     # Base de données SQLite
├── tests/              # Tests unitaires et fonctionnels
│   ├── Feature/        # Tests fonctionnels de l'API
│   │   └── ApiRoutesTest.php # Tests des routes API
│   └── Unit/           # Tests unitaires
├── .env.example        # Exemple de fichier de configuration d'environnement
├── composer.json       # Dépendances PHP
└── README.md           # Documentation
```

## Mesures d'éco-conception

| Mesure           | Description                         | Impact                              |
| ---------------- | ----------------------------------- | ----------------------------------- |
| Mise en cache    | Cache des requêtes fréquentes       | -30% requêtes SQL                   |
| Optimisation SQL | Index et requêtes optimisées        | -50% temps de traitement            |
| Compression      | Compression gzip des réponses       | -70% bande passante                 |
| Logs SQLite      | Utilisation de SQLite pour les logs | Économie de ressources              |
| Pagination       | Limitation des résultats par page   | Réduction des données transférées   |
| JWT léger        | Tokens d'authentification optimisés | Réduction de la taille des en-têtes |

## Installation

### Prérequis

- Docker et Docker Compose
- PHP 8.2 (pour le développement local sans Docker)
- Composer

### Installation avec Docker (recommandée)

1. Cloner le dépôt :

   ```bash
   git clone https://github.com/votre-utilisateur/ecoride.git
   cd ecoride
   ```

2. Copier le fichier d'environnement :

   ```bash
   cp backend/.env.example backend/.env
   ```

3. Lancer Docker Compose :

   ```bash
   docker-compose up -d
   ```

4. L'API est accessible à l'adresse `http://localhost:8080/api`

### Installation locale (sans Docker)

1. Installer les dépendances PHP :

   ```bash
   cd backend
   composer install
   ```

2. Configurer l'environnement :

   ```bash
   cp .env.example .env
   # Éditer .env avec vos paramètres locaux
   ```

3. Initialiser la base de données :

   ```bash
   php scripts/init-db.php
   ```

4. Lancer le serveur de développement :
   ```bash
   php -S localhost:8080 -t public
   ```

## Endpoints de l'API

### Authentification

- `POST /api/auth/register` - Inscription
- `POST /api/auth/login` - Connexion
- `POST /api/auth/refresh` - Rafraîchir le token
- `POST /api/auth/logout` - Déconnexion

### Trajets

- `GET /api/rides` - Liste des trajets (paginée)
- `GET /api/rides/{id}` - Détails d'un trajet
- `POST /api/rides` - Créer un trajet
- `PUT /api/rides/{id}` - Modifier un trajet
- `DELETE /api/rides/{id}` - Supprimer un trajet
- `GET /api/rides/search` - Rechercher des trajets (paginée)

### Utilisateurs

- `GET /api/users/me` - Profil de l'utilisateur connecté
- `PUT /api/users/me` - Mettre à jour le profil

### Réservations

- `GET /api/bookings` - Liste des réservations (paginée)
- `POST /api/rides/{id}/book` - Réserver un trajet
- `DELETE /api/bookings/{id}` - Annuler une réservation

## Tests

Les tests sont essentiels pour garantir la qualité et éviter les régressions :

```bash
# Exécuter tous les tests
docker-compose run tests

# Exécuter uniquement les tests unitaires
docker-compose run tests ./vendor/bin/phpunit -c config/phpunit.xml --testsuite Unit

# Exécuter uniquement les tests fonctionnels
docker-compose run tests ./vendor/bin/phpunit -c config/phpunit.xml --testsuite Feature
```

### Tests fonctionnels de l'API

Notre backend intègre un système complet de tests fonctionnels pour l'API REST. Ces tests permettent de vérifier le bon fonctionnement des routes, des contrôleurs et des middlewares dans un environnement proche de la production.

Le fichier `ApiRoutesTest.php` contient plusieurs tests qui valident :

- Le fonctionnement des routes avec paramètres
- La reconnaissance des méthodes HTTP
- La gestion des erreurs 404
- Le routage correct vers les contrôleurs

Ces tests s'exécutent automatiquement à chaque commit et sont intégrés à notre pipeline CI/CD.

## Contribution

Veuillez consulter le fichier CONTRIBUTING.md à la racine du projet pour les directives de contribution.

## Surveillance des performances

Nous surveillons activement les performances du backend pour maintenir notre engagement d'éco-conception :

- Temps de réponse moyen < 200ms
- Utilisation CPU < 20% en charge normale
- Empreinte mémoire < 50MB par instance PHP-FPM
- Taille moyenne des réponses < 10KB

## Licence

Ce projet est sous licence MIT. Voir le fichier LICENSE pour plus de détails.
