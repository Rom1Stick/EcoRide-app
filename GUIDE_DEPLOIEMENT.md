# 🚀 Guide de Déploiement EcoRide sur Heroku

Ce guide vous explique comment déployer vos modifications locales vers votre application EcoRide publique sur Heroku.

## 📋 Table des matières

1. [Prérequis](#prérequis)
2. [Procédure complète de déploiement](#procédure-complète-de-déploiement)
3. [Version express](#version-express)
4. [Informations utiles](#informations-utiles)
5. [Dépannage](#dépannage)
6. [Base de données](#base-de-données)
7. [Fonctionnalités](#fonctionnalités)
8. [Système d'auto-confirmation](#système-d'auto-confirmation)

## 🔧 Prérequis

- Git configuré
- Heroku CLI installé
- Docker Desktop en cours d'exécution
- Compte Heroku connecté

```powershell
# Vérifier les installations
git --version
heroku --version
docker --version
```

## 🎯 Procédure complète de déploiement

### **Étape 1 : Finaliser vos modifications locales**

```powershell
# Vérifier l'état de votre dépôt
git status

# Ajouter tous vos changements
git add .

# Faire un commit avec un message descriptif
git commit -m "feat: description de vos modifications"
```

### **Étape 2 : Pousser vers le dépôt Git**

```powershell
# Pousser vers la branche principale
git push origin main
```

### **Étape 3 : Construire et déployer sur Heroku**

```powershell
# Construire l'image Docker et l'envoyer vers Heroku
heroku container:push web --app ecoride-application

# Déployer la nouvelle version
heroku container:release web --app ecoride-application
```

### **Étape 4 : Vérifier le déploiement**

```powershell
# Tester l'API de santé
Invoke-WebRequest -Uri "https://ecoride-application-9b4ee584e982.herokuapp.com/api/health" -UseBasicParsing

# Voir les logs en temps réel (optionnel)
heroku logs --tail --app ecoride-application
```

## ⚡ Version express

Pour les déploiements rapides, utilisez cette commande unique :

```powershell
git add . && git commit -m "update: modifications" && git push origin main && heroku container:push web --app ecoride-application && heroku container:release web --app ecoride-application
```

## 🕒 Temps de déploiement

| Étape | Durée estimée |
|-------|---------------|
| Build Docker | ~1-2 minutes |
| Upload vers Heroku | ~1 minute |
| Déploiement | ~30 secondes |
| **Total** | **~3-4 minutes** |

## 📱 Liens de vérification

- **Application principale** : https://ecoride-application-9b4ee584e982.herokuapp.com
- **API Health Check** : https://ecoride-application-9b4ee584e982.herokuapp.com/api/health
- **Debug Tables** : https://ecoride-application-9b4ee584e982.herokuapp.com/api/debug/tables
- **Debug Users** : https://ecoride-application-9b4ee584e982.herokuapp.com/api/debug/users

## 🗄️ Base de données

### **Informations de connexion MySQL**

```
Host: nba02whlntki5w2p.cbetxkdyhwsb.us-east-1.rds.amazonaws.com
Port: 3306
Database: dh5ntjg2se45iumw
Username: mrtvvs5cqjmom0nt
Password: qeagzaet2fgyoxef
```

### **Configuration MySQL Workbench**

1. **Connection Name** : `EcoRide Heroku Database`
2. **Connection Method** : `Standard (TCP/IP)`
3. **Hostname** : `nba02whlntki5w2p.cbetxkdyhwsb.us-east-1.rds.amazonaws.com`
4. **Port** : `3306`
5. **Username** : `mrtvvs5cqjmom0nt`
6. **Password** : `qeagzaet2fgyoxef` (Store in Vault)
7. **Default Schema** : `dh5ntjg2se45iumw`

### **Tables principales**

- `Utilisateur` - Utilisateurs inscrits
- `Role` - Rôles système (visiteur, passager, chauffeur, admin)
- `Possede` - Liaison utilisateur-rôle
- `Covoiturage` - Trajets de covoiturage
- `Voiture` - Véhicules des utilisateurs
- `Participation` - Réservations de trajets
- `CreditBalance` - Solde des crédits
- `CreditTransaction` - Historique des transactions

## 🚨 Dépannage

### **En cas de problème de déploiement**

```powershell
# Voir les logs en temps réel
heroku logs --tail --app ecoride-application

# Redémarrer l'application
heroku restart --app ecoride-application

# Vérifier l'état de l'application
heroku ps --app ecoride-application
```

### **Problèmes courants**

#### **Build Docker échoue**
```powershell
# Nettoyer le cache Docker
docker system prune -f

# Reconstruire l'image
heroku container:push web --app ecoride-application
```

#### **L'application ne démarre pas**
```powershell
# Vérifier les variables d'environnement
heroku config --app ecoride-application

# Consulter les logs de démarrage
heroku logs --app ecoride-application
```

#### **Erreurs de base de données**
```powershell
# Tester la connexion à la base
Invoke-WebRequest -Uri "https://ecoride-application-9b4ee584e982.herokuapp.com/api/test-db" -UseBasicParsing
```

## 🔄 Workflow de développement recommandé

1. **Développement local** : Faites vos modifications
2. **Test local** : Vérifiez que tout fonctionne
3. **Commit** : `git add . && git commit -m "description"`
4. **Push** : `git push origin main`
5. **Build** : `heroku container:push web --app ecoride-application`
6. **Deploy** : `heroku container:release web --app ecoride-application`
7. **Verify** : Testez l'application en ligne

## 📊 Monitoring

### **Commandes utiles**

```powershell
# Voir les métriques
heroku logs --app ecoride-application

# Voir l'utilisation des ressources
heroku ps --app ecoride-application

# Voir les addons
heroku addons --app ecoride-application
```

### **Endpoints de monitoring**

- **Health** : `/api/health` - État de l'API
- **Database Test** : `/api/test-db` - Test de connexion base
- **Debug Tables** : `/api/debug/tables` - Liste des tables
- **Debug Users** : `/api/debug/users` - Utilisateurs actuels

## 🔐 Sécurité

⚠️ **Important** : Les endpoints de debug (`/api/debug/*`) doivent être supprimés en production !

```php
// À supprimer dans backend/routes/api.php avant la mise en production
$router->get('/api/debug/tables', 'HomeController@debugTables');
$router->get('/api/debug/users', 'HomeController@debugUsers');
```

## 📞 Support

En cas de problème, consultez :

1. Les logs Heroku : `heroku logs --tail --app ecoride-application`
2. La documentation Heroku : https://devcenter.heroku.com/
3. La documentation Docker : https://docs.docker.com/

## 🎯 Fonctionnalités

- ✅ **Inscription et authentification** des utilisateurs
- ✅ **Auto-confirmation automatique** des nouveaux comptes (plus besoin de confirmation par email)
- ✅ **Attribution automatique du rôle "passager"** lors de l'inscription
- ✅ **Gestion des profils** utilisateur avec rôles
- ✅ **Création et recherche** de trajets de covoiturage
- ✅ **Système de réservations** avec gestion des places
- ✅ **Gestion des véhicules** personnels
- ✅ **Système de crédits** intégré (20€ de bienvenue)
- ✅ **Interface responsive** moderne
- ✅ **API REST** complète

## 🔄 Système d'auto-confirmation

**Nouveau système mis en place** : Les utilisateurs sont automatiquement confirmés lors de l'inscription, simplifiant grandement le processus.

### **Fonctionnement :**
- ✅ **Inscription** : L'utilisateur s'inscrit normalement
- ✅ **Auto-confirmation** : Le compte est automatiquement confirmé (`confirmed = 1`)
- ✅ **Rôles assignés** : "visiteur" + "passager" automatiquement
- ✅ **Crédits** : 20€ de bienvenue automatiques
- ✅ **Connexion immédiate** : L'utilisateur peut se connecter directement

### **Avantages :**
- 🚀 **Pas d'étape de confirmation email**
- 🎯 **Expérience utilisateur simplifiée**
- ⚡ **Inscription et utilisation immédiates**
- 🔧 **Moins de support client**

---

**🎉 Votre application EcoRide est maintenant prête pour le déploiement continu !** 