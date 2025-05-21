# Tableau de Bord Administrateur EcoRide

Ce document décrit les fonctionnalités du tableau de bord administrateur d'EcoRide.

## Vue générale

Le tableau de bord administrateur permet de :
- Visualiser des statistiques clés sur la plateforme
- Consulter deux graphiques d'activité (covoiturages et crédits)
- Gérer les utilisateurs (confirmation, suspension, gestion des rôles)

## Fonctionnalités principales

### 1. Visualisation des statistiques

Le tableau de bord présente plusieurs cartes d'information :
- Nombre total d'utilisateurs
- Nombre total de trajets
- **Total des crédits** gagnés par la plateforme
- CO2 économisé

### 2. Graphiques d'analyse

Deux graphiques sont disponibles :
- **Graphique des covoiturages par jour** : Affiche le nombre de covoiturages effectués pour chaque jour sur les 30 derniers jours
- **Graphique des crédits gagnés par jour** : Montre l'évolution des crédits gagnés par la plateforme par jour

### 3. Gestion des utilisateurs

Sur la page de gestion des utilisateurs, l'administrateur peut :
- Voir la liste complète des utilisateurs
- Consulter les rôles attribués à chaque utilisateur
- Ajouter des rôles aux utilisateurs
- Suspendre un compte utilisateur
- Réactiver un compte utilisateur précédemment suspendu

## Gestion des comptes utilisateur

### Suspension de compte

La fonctionnalité de suspension de compte permet à l'administrateur de désactiver temporairement l'accès d'un utilisateur à la plateforme. Un utilisateur suspendu ne peut plus se connecter ni utiliser les services d'EcoRide.

Pour suspendre un compte :
1. Accédez à la page "Gestion des utilisateurs"
2. Localisez l'utilisateur concerné
3. Cliquez sur le bouton "Suspendre" dans la colonne Actions
4. Confirmez la suspension

### Réactivation de compte

Pour réactiver un compte suspendu :
1. Accédez à la page "Gestion des utilisateurs"
2. Localisez l'utilisateur concerné
3. Cliquez sur le bouton "Réactiver" dans la colonne Actions
4. Confirmez la réactivation

## Suivi des crédits

Le tableau de bord met en évidence le total des crédits gagnés par la plateforme. Ces crédits correspondent aux commissions prélevées sur les transactions entre utilisateurs.

La carte "Crédits Plateforme" affiche le montant total des crédits, et le graphique dédié permet de suivre l'évolution journalière des gains.

## Implémentation technique

Ces fonctionnalités sont implémentées via :
- Chart.js pour les graphiques
- API RESTful pour récupérer les données
- Contrôleur AdminController côté serveur
- Modèles de données avec suivi des actions administratives

## Routes API disponibles

- `/api/admin/stats/rides` : Statistiques des covoiturages
- `/api/admin/stats/credits` : Statistiques des crédits
- `/api/admin/users/{userId}/suspend` : Suspendre un compte
- `/api/admin/users/{userId}/activate` : Réactiver un compte

## Base de données

La table `Utilisateur` inclut maintenant deux colonnes supplémentaires :
- `suspended` : Indique si le compte est suspendu (0/1)
- `suspended_at` : Date de la dernière suspension

Une table de journalisation `UserActionLog` permet de suivre les actions administratives. 