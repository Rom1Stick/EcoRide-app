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

### Version actuelle : v1.0
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
