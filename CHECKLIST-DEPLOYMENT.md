# 📋 Checklist de déploiement EcoRide sur Heroku

## ✅ Pré-déploiement (à faire maintenant)

### 1. Vérification des outils
- [ ] Heroku CLI installé : `heroku --version`
- [ ] Docker installé : `docker --version`
- [ ] Connexion Heroku : `heroku login`
- [ ] Connexion Docker : `heroku container:login`

### 2. Test local (recommandé)
- [ ] Tester l'image Docker localement :
  ```powershell
  .\scripts\test-docker.ps1
  ```

### 3. Création de l'application Heroku
- [ ] Créer l'app : `heroku create ecoride-application`
- [ ] Configurer la stack : `heroku stack:set container -a ecoride-application`

### 4. Configuration de la base de données
- [ ] Ajouter MongoDB : `heroku addons:create mongolab:sandbox -a ecoride-application`
- [ ] Vérifier l'URL : `heroku config:get MONGODB_URI -a ecoride-application`

## 🚀 Déploiement

### Option A: Script automatisé (recommandé)
- [ ] Exécuter le script :
  ```powershell
  .\scripts\deploy-heroku.ps1 ecoride-application
  ```

### Option B: Déploiement manuel
- [ ] Build et push : `heroku container:push web -a ecoride-application`
- [ ] Release : `heroku container:release web -a ecoride-application`

## ✅ Post-déploiement

### 1. Vérification
- [ ] Vérifier les logs : `heroku logs --tail -a ecoride-application`
- [ ] Tester l'application : `heroku open -a ecoride-application`
- [ ] Vérifier les dynos : `heroku ps -a ecoride-application`

### 2. Configuration des variables (si nécessaire)
- [ ] `heroku config:set NODE_ENV=production -a ecoride-application`
- [ ] `heroku config:set PHP_ENV=production -a ecoride-application`

### 3. Déploiement automatique GitHub (optionnel)
- [ ] Aller sur dashboard Heroku > Deploy > GitHub
- [ ] Connecter le repository
- [ ] Activer "Automatic deploys" pour `main`

## 🔧 Commandes utiles

```bash
# Surveillance
heroku logs --tail -a ecoride-application
heroku ps -a ecoride-application
heroku config -a ecoride-application

# Maintenance
heroku restart -a ecoride-application
heroku container:push web -a ecoride-application
heroku container:release web -a ecoride-application

# Debugging
heroku run bash -a ecoride-application
```

## 🌐 URL finale
Votre application sera disponible sur :
**https://ecoride-application.herokuapp.com**

---

✅ **Une fois toutes les cases cochées, votre application EcoRide sera en ligne !** 