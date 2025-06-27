# Guide de déploiement EcoRide sur Heroku

Ce guide détaille les étapes pour déployer l'application EcoRide sur Heroku en utilisant Docker.

## Pré-requis

### 1. Outils nécessaires
- [Heroku CLI](https://devcenter.heroku.com/articles/heroku-cli) installé
- [Docker](https://docker.com) installé et en fonctionnement
- Un compte Heroku actif

### 2. Vérification des outils
```bash
# Vérifier Heroku CLI
heroku --version

# Vérifier Docker
docker --version

# Se connecter à Heroku
heroku login
heroku container:login
```

## Configuration de l'application Heroku

### 1. Créer l'application (si pas encore fait)
```bash
heroku create ecoride-application
```

### 2. Configurer la stack container
```bash
heroku stack:set container -a ecoride-application
```

### 3. Ajouter la base de données MongoDB
```bash
# Option 1: MongoDB Atlas (recommandé)
heroku addons:create mongolab:sandbox -a ecoride-application

# Option 2: mLab (alternative)
# heroku addons:create mlab:sandbox -a ecoride-application
```

### 4. Configurer les variables d'environnement
```bash
# Récupérer l'URL MongoDB
heroku config:get MONGODB_URI -a ecoride-application

# Ajouter des variables personnalisées si nécessaire
heroku config:set NODE_ENV=production -a ecoride-application
heroku config:set PHP_ENV=production -a ecoride-application
```

## Déploiement automatisé

### Option 1: Script automatisé (recommandé)

#### Sur Linux/macOS:
```bash
chmod +x scripts/deploy-heroku.sh
./scripts/deploy-heroku.sh ecoride-application
```

#### Sur Windows PowerShell:
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
.\scripts\deploy-heroku.ps1 ecoride-application
```

### Option 2: Déploiement manuel

```bash
# 1. Build et push de l'image
heroku container:push web -a ecoride-application

# 2. Release de l'application
heroku container:release web -a ecoride-application

# 3. Vérifier le déploiement
heroku logs --tail -a ecoride-application
```

## Structure de l'application déployée

```
https://ecoride-application.herokuapp.com/
├── /                    # Redirection vers /pages/
├── /pages/              # Frontend (HTML, CSS, JS)
├── /assets/             # Assets statiques (CSS, JS, images)
└── /api/                # API Backend PHP
```

## Configuration de la base de données

### MongoDB (recommandé)
L'application utilise MongoDB. Heroku configure automatiquement la variable `MONGODB_URI`.

Votre code PHP doit utiliser cette variable :
```php
$mongoUri = getenv('MONGODB_URI');
$client = new MongoDB\Client($mongoUri);
```

### Variables d'environnement importantes
- `MONGODB_URI` : URL de connexion MongoDB
- `PORT` : Port dynamique défini par Heroku
- `NODE_ENV` : Environnement Node.js
- `PHP_ENV` : Environnement PHP

## Déploiement automatique via GitHub

### 1. Créer heroku.yml (déjà fait)
Le fichier `heroku.yml` est configuré pour le déploiement Docker.

### 2. Connecter GitHub
1. Aller sur le dashboard Heroku
2. Onglet "Deploy" > "GitHub"
3. Connecter votre repository
4. Activer "Automatic deploys" pour la branche `main`

### 3. Déploiement automatique
Chaque push sur `main` déclenche automatiquement un déploiement.

## Surveillance et maintenance

### Commandes utiles
```bash
# Voir les logs en temps réel
heroku logs --tail -a ecoride-application

# Voir le statut des dynos
heroku ps -a ecoride-application

# Voir les variables d'environnement
heroku config -a ecoride-application

# Redémarrer l'application
heroku restart -a ecoride-application

# Ouvrir l'application dans le navigateur
heroku open -a ecoride-application
```

### Scaling
```bash
# Voir l'utilisation actuelle
heroku ps:scale -a ecoride-application

# Scaler le dyno web (si nécessaire)
heroku ps:scale web=1 -a ecoride-application
```

### Debugging
```bash
# Se connecter au conteneur (pour debug)
heroku run bash -a ecoride-application

# Voir les informations de l'application
heroku apps:info -a ecoride-application
```

## Optimisations et bonnes pratiques

### 1. Performance
- Les assets CSS/JS sont minifiés lors du build
- Le cache HTTP est configuré pour les assets statiques
- L'image Docker est optimisée avec multi-stage build

### 2. Sécurité
- Headers de sécurité configurés dans `.htaccess`
- Protection contre les attaques XSS, CSRF
- Files sensibles protégés

### 3. Monitoring
- Logs centralisés via Heroku
- Variables d'environnement sécurisées
- Healthcheck automatique

## Dépannage

### Problèmes courants

#### Erreur de build Docker
```bash
# Nettoyer les images locales
docker system prune -f

# Rebuild depuis zéro
heroku container:push web -a ecoride-application --verbose
```

#### Erreur de connexion base de données
```bash
# Vérifier la variable MongoDB
heroku config:get MONGODB_URI -a ecoride-application

# Recréer l'addon si nécessaire
heroku addons:destroy mongolab -a ecoride-application
heroku addons:create mongolab:sandbox -a ecoride-application
```

#### Application ne démarre pas
```bash
# Voir les logs détaillés
heroku logs --tail -a ecoride-application

# Vérifier le port
heroku config:get PORT -a ecoride-application
```

### Support
- [Documentation Heroku](https://devcenter.heroku.com/)
- [Support Heroku](https://help.heroku.com/)
- Logs de l'application : `heroku logs --tail -a ecoride-application`

---

✅ **Votre application EcoRide est maintenant prête pour le déploiement sur Heroku !** 