# Frontend EcoRide 🌱

Interface utilisateur légère et éco-conçue pour l'application EcoRide de covoiturage écologique.

## Approche d'éco-conception

Notre frontend est conçu selon les principes d'éco-conception suivants :

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

## Structure du projet

```
frontend/
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
```

## Performance et optimisations

Plusieurs stratégies d'optimisation ont été mises en place :

- **Zéro framework JavaScript** pour réduire drastiquement la taille de bundle
- **SCSS compilé en CSS optimisé** pour minimiser les styles inutiles
- **Cache optimisé** pour les ressources statiques
- **Fetch API native** pour des appels HTTP sans dépendances

## Installation et développement

### Prérequis

- Node.js 16+ (uniquement pour compilation SCSS)
- npm ou yarn

### Installation locale

```bash
# Installer les dépendances
cd frontend
npm install

# Compiler le SCSS en CSS
npm run build:scss

# Lancer en mode watch (SCSS → CSS automatique)
npm run watch:scss
```

### Scripts disponibles

- `npm run build:scss` : Compile le SCSS en CSS minifié
- `npm run watch:scss` : Surveille les changements SCSS et compile en continu
- `npm run lint` : Vérifie le code avec ESLint

## Docker

L'application est conçue pour fonctionner dans un environnement Docker :

- Nginx sert les fichiers statiques de manière optimisée
- Le proxy API est configuré pour communiquer avec le backend
- La compilation SCSS peut être intégrée dans le pipeline de build

## Mesures d'éco-conception

Nous surveillons activement les métriques suivantes :

- Taille du bundle CSS < 10KB
- Taille du bundle JS < 15KB
- Score Lighthouse Performance > 95
- First Contentful Paint < 1s
- Largest Contentful Paint < 1.5s
- Temps d'interaction total < 100ms

Ces mesures reflètent notre engagement écologique et notre approche sobre du développement web.

## Licence

Ce projet est sous licence MIT. Voir le fichier LICENSE pour plus de détails.
