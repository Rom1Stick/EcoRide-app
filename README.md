# EcoRide 🚗🌱

## Application de covoiturage écoresponsable

EcoRide est une application de covoiturage conçue avec une approche d'écoconception numérique. Notre mission est de faciliter la mobilité partagée tout en minimisant l'impact environnemental du service numérique lui-même.

## 🌟 Caractéristiques

- Interface utilisateur légère et accessible
- Performance optimisée pour réduire la consommation énergétique
- Architecture modulaire pour une maintenance facilitée
- Approche "mobile-first" avec design responsive
- Respect des normes RGPD pour la protection des données

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

# Installer les dépendances
npm install

# Lancer l'application en développement
npm run dev
```

## 📊 Écoconception

EcoRide suit les principes d'écoconception suivants:

- **Sobriété fonctionnelle** : fonctionnalités essentielles uniquement
- **Performance** : optimisation des ressources et des requêtes
- **Accessibilité** : conforme aux standards WCAG 2.1 AA
- **Légèreté** : compression des assets, lazy loading, code splitting
- **Durée de vie** : compatibilité étendue avec les anciens appareils

## 🏗️ Architecture du projet

Le projet EcoRide suit une architecture modulaire et optimisée pour l'écoconception :

- Séparation claire entre le front-end (dossier `frontend/`) et le back-end (dossier `backend/`)
- Fichiers de configuration spécifiques à chaque partie du projet
- Utilisation de Docker pour isoler les environnements
- Optimisation des outils de vérification (ESLint, Stylelint) pour ne scanner que les parties pertinentes

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

## ✅ Priorités du projet

- **Écoconception** : Impact minimal sur l'environnement numérique
- **Performance et accessibilité** : Expérience utilisateur optimale pour tous
- **Sécurité et robustesse** : Protection des données et fiabilité
- **Maintenabilité et évolutivité** : Architecture facilitant les évolutions futures
