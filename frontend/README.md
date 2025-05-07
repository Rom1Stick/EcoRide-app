# Frontend EcoRide 🌱

Interface utilisateur légère et éco-conçue pour l'application EcoRide de covoiturage écologique.

<<<<<<< HEAD
## Test réussi

Test de validation des hooks Git et de la configuration de qualité de code.

=======
>>>>>>> develop
## Approche d'éco-conception

Notre frontend est conçu selon les principes d'éco-conception suivants :

<<<<<<< HEAD
- **Architecture légère** : Utilisation du framework Vue.js avec une approche minimaliste
- **Bundle optimisé** : Code-splitting, tree-shaking et compression pour réduire la taille
- **Performances optimales** : Respect des Web Vitals et optimisation des rendus
- **Accessibilité** : Conception accessible répondant aux normes WCAG 2.1 AA
- **Réduction des requêtes** : Stratégies de cache et optimisation des chargements
- **Optimisation des médias** : Images au format optimisé (WebP), lazy loading, et dimensionnement adapté

## Outils de qualité de code

Le frontend d'EcoRide intègre un ensemble complet d'outils de qualité pour maintenir les standards élevés du code :

- **ESLint** : Analyse statique du code JavaScript/TypeScript/Vue
- **Prettier** : Formatage cohérent et automatique du code
- **Stylelint** : Linting des fichiers CSS/SCSS
- **lint-staged** : Optimisation des analyses en ne vérifiant que les fichiers modifiés
- **Husky** : Hooks Git pour automatiser les vérifications avant chaque commit/push
- **Jest** : Tests unitaires des composants et services
- **Cypress** : Tests d'intégration et end-to-end
- **Vue Test Utils** : Bibliothèque de test spécifique à Vue.js

Ces outils sont configurés pour fonctionner ensemble de manière optimale. Les hooks pre-commit ont été configurés pour vérifier automatiquement le formatage et les erreurs de lint avant chaque commit, et notre configuration lint-staged assure que seuls les fichiers modifiés sont vérifiés pour de meilleures performances.

## Architecture

L'architecture du frontend est basée sur Vue.js avec une organisation modulaire :

- Vue 3 avec Composition API pour une meilleure maintenabilité
- TypeScript pour la robustesse du code
- Vue Router pour la navigation
- Pinia pour la gestion de l'état
- Sass pour les styles CSS avec une approche modulaire
- Vite comme outil de build pour des performances optimales
=======
- **Architecture ultra-légère** : HTML sémantique + SCSS + JavaScript vanilla sans aucun framework
- **Performance optimale** : Réduction des traitements côté client et de la charge du navigateur
- **Accessibilité** : Conception accessible répondant aux normes WCAG 2.1 AA
- **Réduction des requêtes** : Minimisation des appels HTTP et optimisation des chargements
- **Optimisation des médias** : Images au format optimisé, dimensionnement adapté, lazy loading

## Stack technique

L'application frontend utilise une stack minimaliste pour maximiser les performances et l'écoconception :

- **HTML sémantique** : Structure claire, accessible et SEO-friendly
- **SCSS (avec méthode BEM)** : Préprocesseur CSS pour styles modulaires et maintenables
- **JavaScript Vanilla** : Code JS natif moderne sans dépendances externes
- **Fetch API** : Communication avec le back-end via requêtes HTTP
>>>>>>> develop

## Structure du projet

```
frontend/
<<<<<<< HEAD
├── src/
│   ├── assets/          # Ressources statiques (images, fonts, etc.)
│   ├── components/      # Composants Vue réutilisables
│   ├── pages/           # Composants de page
│   ├── router/          # Configuration du routeur
│   ├── store/           # Magasins Pinia pour la gestion d'état
│   ├── styles/          # Styles globaux et variables
│   ├── services/        # Services et logique métier
│   ├── utils/           # Fonctions utilitaires
│   ├── App.vue          # Composant racine
│   └── main.ts          # Point d'entrée de l'application
├── public/              # Fichiers statiques non traités par Vite
├── config/              # Fichiers de configuration
│   ├── .eslintrc.cjs    # Configuration ESLint spécifique au frontend
│   ├── .prettierrc      # Configuration Prettier
│   ├── jest.config.cjs  # Configuration Jest
│   ├── cypress.config.js # Configuration Cypress
│   └── vite.config.js   # Configuration Vite
├── __tests__/           # Tests unitaires
├── package.json         # Dépendances et scripts
└── README.md            # Documentation
=======
├── pages/
│   ├── public/                   # Pages accessibles aux visiteurs et utilisateurs
│   │   ├── index.html
│   │   ├── register.html
│   │   ├── login.html
│   │   └── ...
│   └── admin/                    # Pages d'administration uniquement
│       ├── dashboard.html
│       ├── manage-users.html
│       └── ...
├── assets/
│   ├── js/
│   │   ├── public/               # Scripts liés aux pages publiques
│   │   ├── admin/                # Scripts liés à l'admin
│   │   └── common/               # Modules partagés (API, validations, etc.)
│   ├── scss/
│   │   ├── public/               # SCSS pour le front public
│   │   ├── admin/                # SCSS pour l'admin
│   │   ├── components/           # Boutons, formulaires, alertes, etc.
│   │   ├── abstracts/            # Variables, mixins, fonctions...
│   │   └── main.scss             # Point d'entrée SCSS
│   ├── css/                      # CSS généré (compilé depuis SCSS)
│   ├── images/                   # Images optimisées
│   └── fonts/                    # Polices web
├── README.md                     # Documentation
├── Dockerfile                    # Configuration Docker
└── package.json                  # Dépendances minimales (sass uniquement)
>>>>>>> develop
```

## Performance et optimisations

Plusieurs stratégies d'optimisation ont été mises en place :

<<<<<<< HEAD
- **Lazy loading** des composants pour réduire le bundle initial
- **Prefetching** intelligent des routes pour améliorer la navigation
- **Compression** des assets pour réduire la bande passante
- **Cache** optimisé pour les ressources statiques
- Utilisation d'un **CDN** pour la distribution des assets
- **Code-splitting** pour charger uniquement le nécessaire
=======
- **Zéro framework JavaScript** pour réduire drastiquement la taille de bundle
- **SCSS compilé en CSS optimisé** pour minimiser les styles inutiles
- **Cache optimisé** pour les ressources statiques
- **Fetch API native** pour des appels HTTP sans dépendances
>>>>>>> develop

## Installation et développement

### Prérequis

<<<<<<< HEAD
- Node.js 16+
=======
- Node.js 16+ (uniquement pour compilation SCSS)
>>>>>>> develop
- npm ou yarn

### Installation locale

```bash
# Installer les dépendances
cd frontend
npm install

<<<<<<< HEAD
# Lancer le serveur de développement
npm run dev
=======
# Compiler le SCSS en CSS
npm run build:scss

# Lancer en mode watch (SCSS → CSS automatique)
npm run watch:scss
>>>>>>> develop
```

### Scripts disponibles

<<<<<<< HEAD
- `npm run dev` : Démarre le serveur de développement
- `npm run build` : Compile l'application pour la production
- `npm run preview` : Prévisualise la version de production localement
- `npm run lint` : Vérifie le code avec ESLint
- `npm run format` : Formate le code avec Prettier
- `npm run test` : Exécute les tests unitaires avec Jest
- `npm run test:e2e` : Exécute les tests e2e avec Cypress
- `npm run test:coverage` : Génère un rapport de couverture de tests

## Intégration CI/CD

Le frontend est intégré au pipeline CI/CD global qui :

- Vérifie la qualité du code à chaque commit
- Exécute les tests unitaires et e2e
- Analyse les performances avec Lighthouse
- Vérifie la taille du bundle
- Déploie automatiquement en production si tout est validé

## Contribution

Pour contribuer au frontend, veuillez consulter le fichier CONTRIBUTING.md à la racine du projet. Voici les points spécifiques au frontend :

1. Assurez-vous de respecter les conventions de code établies
2. Les hooks Git installés via Husky vérifient automatiquement votre code
3. Écrivez des tests pour toute nouvelle fonctionnalité
4. Suivez la convention de nommage des composants et des fichiers
5. Utilisez les composants existants plutôt que d'en créer de nouveaux si possible
=======
- `npm run build:scss` : Compile le SCSS en CSS minifié
- `npm run watch:scss` : Surveille les changements SCSS et compile en continu
- `npm run lint` : Vérifie le code avec ESLint

## Docker

L'application est conçue pour fonctionner dans un environnement Docker :

- Nginx sert les fichiers statiques de manière optimisée
- Le proxy API est configuré pour communiquer avec le backend
- La compilation SCSS peut être intégrée dans le pipeline de build
>>>>>>> develop

## Mesures d'éco-conception

Nous surveillons activement les métriques suivantes :

<<<<<<< HEAD
- Taille de bundle < 150KB
- Score Lighthouse Performance > 90
- First Contentful Paint < 1.5s
- Largest Contentful Paint < 2.5s
- Temps d'interaction total < 200ms

Ces mesures sont vérifiées à chaque déploiement pour garantir notre engagement écologique.
=======
- Taille du bundle CSS < 10KB
- Taille du bundle JS < 15KB
- Score Lighthouse Performance > 95
- First Contentful Paint < 1s
- Largest Contentful Paint < 1.5s
- Temps d'interaction total < 100ms

Ces mesures reflètent notre engagement écologique et notre approche sobre du développement web.
>>>>>>> develop

## Licence

Ce projet est sous licence MIT. Voir le fichier LICENSE pour plus de détails.
