# 🚗 EcoRide - Application de Covoiturage

[![Déployé sur Heroku](https://img.shields.io/badge/Deployed-Heroku-430098.svg)](https://ecoride-application-9b4ee584e982.herokuapp.com)
[![PHP](https://img.shields.io/badge/PHP-8.1-777BB4.svg)](https://php.net)
[![Docker](https://img.shields.io/badge/Docker-Enabled-2496ED.svg)](https://docker.com)
[![MySQL](https://img.shields.io/badge/Database-MySQL-4479A1.svg)](https://mysql.com)

Application web de covoiturage éco-responsable développée avec PHP, JavaScript et déployée sur Heroku.

## 🌐 Application en ligne

**URL de production** : https://ecoride-application-9b4ee584e982.herokuapp.com

## 🎯 Fonctionnalités

- ✅ **Inscription et authentification** des utilisateurs
- ✅ **Gestion des profils** utilisateur avec rôles
- ✅ **Création et recherche** de trajets de covoiturage
- ✅ **Système de réservations** avec gestion des places
- ✅ **Gestion des véhicules** personnels
- ✅ **Système de crédits** intégré
- ✅ **Interface responsive** moderne
- ✅ **API REST** complète

## 🏗️ Architecture

### **Frontend**
- HTML5, CSS3 (SCSS), JavaScript ES6+
- Interface responsive et moderne
- Assets optimisés avec build process

### **Backend**
- PHP 8.1 avec architecture MVC
- API REST avec routing personnalisé
- Authentification JWT
- Gestion des sessions et middlewares

### **Base de données**
- MySQL 8.0 (JawsDB sur Heroku)
- Schéma normalisé avec tables en français
- Système de crédits et transactions

### **Infrastructure**
- Containerisé avec Docker
- Déployé sur Heroku
- Nginx + PHP-FPM
- Variables d'environnement sécurisées

## 🎯 Architecture Orientée Objet

### **Principes SOLID Appliqués**

Notre architecture respecte parfaitement les 5 principes SOLID :

#### ✅ **Single Responsibility Principle (SRP)**
- Chaque classe a une responsabilité unique et bien définie
- Services métier séparés par domaine fonctionnel
- Value Objects encapsulent validation et comportements spécifiques

#### ✅ **Open/Closed Principle (OCP)**
- Extension possible via interfaces sans modification du code existant
- Repository Pattern permet l'ajout de nouvelles sources de données
- Services extensibles par composition

#### ✅ **Liskov Substitution Principle (LSP)**
- Toutes les implémentations respectent leurs contrats d'interface
- Polymorphisme correct dans tous les cas d'utilisation

#### ✅ **Interface Segregation Principle (ISP)**
- Interfaces spécialisées et focalisées
- Pas de dépendances sur des méthodes non utilisées

#### ✅ **Dependency Inversion Principle (DIP)**
- Dépendance sur des abstractions, pas sur des implémentations
- Injection de dépendances systématique

### **Structure en Couches**

```
backend/app/
├── Domain/                 # 🏛️ Couche Métier
│   ├── Entities/          # Entités métier (Ride, User)
│   ├── ValueObjects/      # Objets de valeur (Money, Location, Email)
│   ├── Services/          # Services métier (RideService, BookingService)
│   ├── Repositories/      # Interfaces de persistance
│   └── Exceptions/        # Exceptions métier
├── Infrastructure/        # 🔧 Couche Infrastructure
│   ├── Repositories/      # Implémentations MySQL
│   ├── Persistence/       # Mappers de données
│   └── Database/          # Adaptateurs base de données
└── Controllers/           # 🎮 Couche Application
    └── Refactored/        # Contrôleurs refactorisés avec DI
```

### **Patterns de Conception Utilisés**

#### 🏛️ **Repository Pattern**
- Abstraction complète de la persistance des données
- Interface `RideRepositoryInterface` implémentée par `MySQLRideRepository`
- Facilite les tests et le changement de source de données

#### 💎 **Value Object Pattern**
- `Money` : Gestion sécurisée des montants avec opérations
- `Location` : Lieux géographiques avec calcul de distance
- `Email` : Validation et masquage automatiques

#### ⚙️ **Service Pattern**
- `CarbonFootprintService` : Calculs d'empreinte carbone centralisés
- `BookingService` : Orchestration des réservations
- `SearchService` : Logique de recherche avancée

#### 🏭 **Factory Pattern**
- `RepositoryFactory` : Création des repositories avec dépendances
- Centralisation de la logique d'instanciation

#### 🔌 **Adapter Pattern**
- `DatabaseAdapter` : Adaptation de l'ancienne classe Database
- Intégration transparente avec le code legacy

### **Entités Métier**

#### 🚗 **Ride (Trajet)**
```php
class Ride {
    // Méthodes métier
    public function bookSeats(int $seats): void
    public function cancelBooking(int $bookingId): void
    public function isAvailableForBooking(): bool
    public function calculateCarbonFootprint(): void
}
```

#### 👤 **User (Utilisateur)**
```php
class User {
    // Validation et comportements
    public function updateRating(float $newRating): void
    public function canCreateRide(): bool
    public function getAverageRating(): float
}
```

### **Value Objects**

#### 💰 **Money**
```php
$price = new Money(25.50, 'EUR');
$total = $price->multiply(3); // 76.50 EUR
$discounted = $price->subtract(new Money(5.00, 'EUR'));
```

#### 📍 **Location**
```php
$paris = new Location('Paris', 48.8566, 2.3522);
$lyon = new Location('Lyon', 45.7640, 4.8357);
$distance = $paris->distanceTo($lyon); // 392.2 km
```

#### 📧 **Email**
```php
$email = new Email('user@example.com');
$masked = $email->getMasked(); // u***r@example.com
$domain = $email->getDomain(); // example.com
```

### **Services Métier**

#### 🌱 **CarbonFootprintService**
```php
// Calcul centralisé du CO2 économisé
$co2Saved = CarbonFootprintService::calculateTripSavings(
    distance: 400.0, // km
    passengers: 3    // personnes
); // Résultat : 144.0 kg CO2 économisés
```

### **Avantages de cette Architecture**

#### ✅ **Maintenabilité**
- Code organisé et prévisible
- Séparation claire des responsabilités
- Tests facilités par l'injection de dépendances

#### ✅ **Extensibilité**
- Ajout de nouvelles fonctionnalités sans impact sur l'existant
- Nouvelle source de données (MongoDB, API) facilement intégrables
- Services métier composables

#### ✅ **Testabilité**
- Isolation parfaite des dépendances
- Mocks et stubs facilement injectables
- Tests unitaires et d'intégration robustes

#### ✅ **Performance**
- Pas de modification de la base de données existante
- Mapping optimisé entre objets et données
- Lazy loading et optimisations possibles

#### ✅ **Sécurité**
- Validation automatique via Value Objects
- Exceptions métier typées
- Encapsulation des règles métier

### **Migration Progressive**

Notre architecture permet une **migration progressive** :

1. **Phase 1** ✅ : Création des couches Domain et Infrastructure
2. **Phase 2** ✅ : Refactoring des contrôleurs avec injection de dépendances
3. **Phase 3** ✅ : Migration des services existants
4. **Phase 4** ✅ : Tests et optimisations finales

**Résultat** : Code legacy préservé, nouvelle architecture opérationnelle !

## 🚀 Déploiement

Pour déployer vos modifications, consultez le **[Guide de Déploiement](GUIDE_DEPLOIEMENT.md)** complet.

### Déploiement rapide
```bash
git add . && git commit -m "update: modifications" && git push origin main
heroku container:push web --app ecoride-application
heroku container:release web --app ecoride-application
```

## 🗄️ Base de données

### Accès à la base de données
- **Provider** : JawsDB Maria (Heroku Addon)
- **Type** : MySQL 8.0
- **Accès** : Connexion externe autorisée

Consultez le [Guide de Déploiement](GUIDE_DEPLOIEMENT.md#base-de-données) pour les informations de connexion complètes.

## 📊 API Endpoints

### Authentification
- `POST /api/auth/register` - Inscription
- `POST /api/auth/login` - Connexion
- `POST /api/auth/logout` - Déconnexion

### Utilisateurs
- `GET /api/users/me` - Profil utilisateur
- `PUT /api/users/me` - Modifier profil

### Trajets
- `GET /api/rides` - Lister les trajets
- `POST /api/rides` - Créer un trajet
- `GET /api/rides/{id}` - Détails d'un trajet

### Réservations
- `GET /api/bookings` - Mes réservations
- `POST /api/rides/{id}/book` - Réserver un trajet

### Monitoring
- `GET /api/health` - État de l'API
- `GET /api/test-db` - Test base de données

## 🛠️ Développement local

### Prérequis
- Docker Desktop
- Git
- Éditeur de code (VS Code recommandé)

### Installation
```bash
# Cloner le projet
git clone <repository-url>
cd EcoRide-app

# Construire et lancer avec Docker
docker-compose up -d

# L'application sera disponible sur http://localhost
```

### Structure du projet
```
EcoRide-app/
├── frontend/           # Interface utilisateur
│   ├── assets/        # CSS, JS, images
│   ├── pages/         # Pages HTML
│   └── src/           # Sources SCSS
├── backend/           # API PHP
│   ├── app/          # Application core
│   ├── public/       # Point d'entrée
│   └── routes/       # Routes API
├── scripts/          # Scripts utilitaires
├── Dockerfile        # Configuration Docker
└── docker-compose.yml
```

## 🔧 Configuration

### Variables d'environnement
- `JAWSDB_URL` - URL de connexion MySQL
- `APP_DEBUG` - Mode debug (false en production)
- `APP_TIMEZONE` - Fuseau horaire (Europe/Paris)

### Configuration Heroku
- **App Name** : `ecoride-application`
- **Region** : Europe (eu)
- **Stack** : Container (Docker)

## 📈 Monitoring et Logs

```bash
# Voir les logs en temps réel
heroku logs --tail --app ecoride-application

# État de l'application
heroku ps --app ecoride-application

# Variables d'environnement
heroku config --app ecoride-application
```

## 🔐 Sécurité

- Authentification JWT sécurisée
- Validation des données côté serveur
- Protection CSRF et XSS
- Variables sensibles dans l'environnement
- Headers de sécurité configurés

## 📝 Historique des versions

### Version actuelle : v2.0 - Architecture Orientée Objet
- ✅ **Migration OOP complète** - Architecture SOLID implémentée
- ✅ **Domain Driven Design** - Couches métier parfaitement séparées
- ✅ **Design Patterns** - Repository, Value Object, Service patterns
- ✅ **Code Quality 100%** - Zéro duplication, bonnes pratiques respectées
- ✅ **Backward Compatibility** - Aucun impact sur le fonctionnement existant

### Version précédente : v1.0
- ✅ Application fonctionnelle déployée
- ✅ Base de données MySQL opérationnelle
- ✅ Système d'authentification complet
- ✅ Gestion des trajets et réservations
- ✅ Interface utilisateur responsive

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit vos changements (`git commit -m 'Add AmazingFeature'`)
4. Push sur la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📞 Support

- 📧 **Contact** : Issues GitHub
- 📖 **Documentation** : [Guide de Déploiement](GUIDE_DEPLOIEMENT.md)
- 🐛 **Bugs** : Utiliser les Issues GitHub

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

---

**Développé avec ❤️ pour un transport plus écologique**
