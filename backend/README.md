# EcoRide - Backend

Ce dossier contient le backend pour l'application EcoRide, une plateforme permettant de réduire l'empreinte carbone des utilisateurs via le covoiturage et des conseils personnalisés.

## Structure du projet

```
backend/
├── app/                  # Code principal de l'application
├── config/               # Fichiers de configuration
├── database/             # Migrations et seeds pour la base de données
├── public/               # Point d'entrée public (index.php)
├── routes/               # Définition des routes de l'API
├── scripts/              # Scripts utilitaires
│   ├── database/         # Scripts de gestion et tests de base de données
│   └── tests/            # Scripts d'exécution des tests
├── src/                  # Code source supplémentaire
├── tests/                # Tests automatisés
└── vendor/               # Dépendances (générées par Composer)
```

## Installation

1. Cloner le dépôt
2. Installer les dépendances avec Composer
```bash
docker-compose run --rm composer install
```
3. Copier le fichier `.env.example` en `.env` et configurer les variables d'environnement
```bash
cp .env.example .env
```
4. Lancer l'application
```bash
docker-compose up -d
```

## Tests

### Exécution des tests PHPUnit

Pour exécuter les tests unitaires et fonctionnels :

```bash
docker-compose run --rm tests scripts/tests/run-tests.sh
```

Options disponibles :
- `-u, --unit` : Exécuter uniquement les tests unitaires
- `-f, --feature` : Exécuter uniquement les tests fonctionnels
- `-c, --coverage` : Générer un rapport de couverture de code
- `-t, --testdox` : Afficher les résultats en format testdox

### Tests des services MongoDB

Pour tester le service de configuration MongoDB :

```bash
docker-compose run --rm tests php scripts/tests/run_config_tests.php
```

Pour tester le service de revues utilisateur MongoDB :

```bash
docker-compose run --rm tests php scripts/tests/run_review_tests.php
```

### Vérification des connexions aux bases de données

Pour vérifier les connexions MySQL et MongoDB :

```bash
docker-compose run --rm tests php scripts/database/test_db.php
```

Pour vérifier uniquement la connexion MySQL :

```bash
docker-compose run --rm tests php scripts/database/check_sql.php
```

Pour vérifier uniquement la connexion MongoDB :

```bash
docker-compose run --rm tests php scripts/database/check_mongo.php
```

## Documentation API

La documentation de l'API est disponible à l'adresse `/docs` une fois l'application lancée.
