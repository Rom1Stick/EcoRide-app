# EcoRide 🚗🌱

## Application de covoiturage écoresponsable

EcoRide est une application de covoiturage conçue avec une approche d'écoconception numérique. Notre mission est de faciliter la mobilité partagée tout en minimisant l'impact environnemental du service numérique lui-même, créant ainsi une double réduction de l'empreinte carbone.

## 🌟 Caractéristiques

- Interface utilisateur légère et accessible (<150KB de JS compressé)
- Performance optimisée pour réduire la consommation énergétique (Web Vitals optimisés)
- Architecture modulaire pour une maintenance facilitée et une longévité accrue
- Approche "mobile-first" avec design responsive et adaptatif
- Respect des normes RGPD pour la protection des données
- Backend sobre utilisant SQLite pour les logs et MySQL pour les données principales
- API REST optimisée pour minimiser les échanges de données

## 🔧 Installation

### Prérequis
- Docker et Docker Compose
- Git

### Installation locale

```bash
# Cloner le dépôt
git clone git@github.com:ton-org/ecoride-app.git
cd ecoride-app

# Lancer l'environnement Docker
docker-compose up -d

# L'application est accessible sur :
# - Frontend : http://localhost:5173
# - API : http://localhost:8080/api
# - Site complet : http://localhost
```

## 📊 Écoconception

EcoRide suit les principes d'écoconception suivants :

- **Sobriété fonctionnelle** : fonctionnalités essentielles uniquement, sans surcharge d'options
- **Performance** : optimisation des ressources et des requêtes (mesurée régulièrement)
- **Accessibilité** : conforme aux standards WCAG 2.1 AA pour une inclusion maximale
- **Légèreté** : compression des assets, lazy loading, code splitting, optimisation des images
- **Durée de vie** : compatibilité étendue avec les anciens appareils et navigateurs
- **Mesure d'impact** : suivi des métriques d'éco-conception via notre checklist
- **Optimisation du stockage** : politique de nettoyage des données obsolètes, types optimisés

### Métriques d'éco-conception
Nous surveillons activement les indicateurs suivants :
- Score Lighthouse Performance > 90
- First Contentful Paint < 1.5s
- Taille totale de page < 500KB
- JavaScript < 150KB compressé
- Consommation CPU/RAM en veille < 2%

## 🏗️ Architecture du projet

Le projet EcoRide suit une architecture modulaire et optimisée pour l'écoconception :

- Séparation claire entre le front-end (dossier `frontend/`) et le back-end (dossier `backend/`)
- Fichiers de configuration spécifiques à chaque partie du projet
- Utilisation de Docker pour isoler les environnements et optimiser les ressources
- Architecture MVC côté backend pour une maintenance facilitée
- API REST économe en ressources avec des réponses optimisées
- Tests automatisés pour garantir la qualité sans régression

Cette architecture offre plusieurs avantages :

✅ Modularité accrue : chaque équipe peut travailler de manière indépendante
✅ Sobriété numérique : évite les traitements inutiles et optimise les ressources
✅ Évolutivité : les règles peuvent évoluer indépendamment pour chaque partie du projet
✅ Docker-ready : chaque conteneur peut embarquer sa propre logique sans conflit
✅ Cohérence avec l'écoconception : réduit les temps de build et de test, isole les environnements

## 👥 Contribution

Nous encourageons les contributions à ce projet ! Veuillez consulter notre [guide de contribution](CONTRIBUTING.md) pour connaître nos normes de code, conventions de commits et procédures de pull request.

```bash
# Installation des hooks Git pour les contributeurs
npm run prepare
```

## 🔒 Sécurité

Si vous découvrez une faille de sécurité, veuillez consulter notre [politique de sécurité](SECURITY.md) pour savoir comment la signaler de manière responsable.

## 📏 Évaluation continue

Nous évaluons régulièrement notre conformité aux principes d'éco-conception grâce à notre [checklist d'écoconception](eco-checklist.md). Cette approche nous permet d'identifier les axes d'amélioration et de mesurer nos progrès.

## ✅ Priorités du projet

- **Écoconception** : Impact minimal sur l'environnement numérique
- **Performance et accessibilité** : Expérience utilisateur optimale pour tous
- **Sécurité et robustesse** : Protection des données et fiabilité
- **Maintenabilité et évolutivité** : Architecture facilitant les évolutions futures
- **Transparence** : Information claire sur l'impact environnemental de l'application
