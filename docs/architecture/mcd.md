# Modèle Conceptuel de Données EcoRide (3FN)

Ce document présente le Modèle Conceptuel de Données (MCD) normalisé en troisième forme normale (3FN) pour l'application EcoRide.

## Principes de conception

Le modèle est conçu selon les principes suivants :

1. **Normalisation complète en 3FN** : Élimination des dépendances transitives pour éviter la redondance.
2. **Optimisation écologique** : Choix des types de données et des structures pour minimiser l'empreinte de stockage.
3. **Indexation sélective** : Uniquement sur les colonnes essentielles aux requêtes fréquentes.
4. **Intégrité référentielle** : Relations clairement définies avec gestion adaptée des cascades.
5. **Évolutivité** : Modèle facilement extensible pour de nouvelles fonctionnalités.

## Entités principales

### Utilisateur
- Représente tous les types d'utilisateurs de la plateforme
- Types gérés via l'entité Role (visiteur, passager, chauffeur, admin)
- Stocke les informations de profil et d'authentification

### Covoiturage
- Représente un trajet proposé par un chauffeur
- Associé à une voiture, des lieux de départ/arrivée et un statut
- Calcul d'empreinte carbone supporté via le champ dédié

### Voiture
- Appartient à un utilisateur (chauffeur)
- Référence un modèle et un type d'énergie
- Utilisée pour les covoiturages

### Participation
- Association entre utilisateurs et covoiturages
- Gère les réservations avec statuts de participation
- Clé composite (utilisateur_id, covoiturage_id)

### Crédit
- Composé de CreditBalance (solde actuel) et CreditTransaction (historique)
- Traçabilité complète des opérations sur les crédits
- Intégrité garantie par les contraintes de vérification

### Avis
- Déposé par un utilisateur sur un covoiturage
- Système de modération via statut (en attente, publié, rejeté)
- Note de 1 à 5 avec commentaire

## Relations clés

1. **Utilisateur ↔ Rôle** (N:N via Possede)
   - Un utilisateur peut avoir plusieurs rôles
   - Un rôle peut être attribué à plusieurs utilisateurs

2. **Utilisateur → Adresse** (N:1)
   - Un utilisateur a une adresse
   - Une adresse peut être associée à plusieurs utilisateurs

3. **Lieu → Adresse** (N:1)
   - Un lieu est associé à une adresse
   - Une adresse peut correspondre à plusieurs lieux

4. **Utilisateur → Voiture** (1:N)
   - Un utilisateur peut gérer plusieurs voitures
   - Une voiture appartient à un seul utilisateur

5. **Voiture → Modèle → Marque** (N:1:1)
   - Une voiture correspond à un modèle
   - Un modèle appartient à une marque

6. **Covoiturage → Voiture** (N:1)
   - Un covoiturage utilise une voiture
   - Une voiture peut être utilisée pour plusieurs covoiturages

7. **Covoiturage ↔ Utilisateur** (N:N via Participation)
   - Un utilisateur peut participer à plusieurs covoiturages
   - Un covoiturage peut avoir plusieurs participants

8. **Utilisateur → Avis** (1:N)
   - Un utilisateur peut déposer plusieurs avis
   - Un avis est déposé par un seul utilisateur

9. **Covoiturage → Avis** (1:N)
   - Un covoiturage peut recevoir plusieurs avis
   - Un avis concerne un seul covoiturage

10. **Utilisateur → CreditBalance** (1:1)
    - Un utilisateur a un seul solde de crédits
    - Un solde appartient à un seul utilisateur

11. **Utilisateur → CreditTransaction** (1:N)
    - Un utilisateur peut avoir plusieurs transactions
    - Une transaction concerne un seul utilisateur

## Aspects d'écoconception

1. **Optimisation du stockage**
   - Utilisation de VARCHAR avec longueurs appropriées
   - Stockage de chemins vers les images plutôt que les images elles-mêmes
   - Normalisation des données d'adresse pour éviter la duplication

2. **Indexation écologique**
   - Index limités aux colonnes essentielles pour les requêtes fréquentes
   - Index composites pour optimiser les recherches combinées
   - Pas d'indexation sur les colonnes rarement utilisées en recherche

3. **Gestion intelligente des cascades**
   - ON DELETE CASCADE uniquement quand nécessaire (ex: suppression utilisateur)
   - Préservation de l'historique quand pertinent

4. **Contraintes intégrées**
   - Contraintes CHECK pour garantir l'intégrité (nb_place > 0, solde >= 0)
   - UNIQUE pour éviter les doublons inutiles
   - NOT NULL sur les colonnes essentielles

## Diagramme simplifié des relations

```
Utilisateur --1:N--> Voiture --N:1--> Modèle --N:1--> Marque
     |            |
     |            |
     v            v
Participation <--N:1-- Covoiturage
     |                    |
     |                    |
     v                    v
    Avis               TypeEnergie
```

## Évolutions possibles

1. **Géolocalisation avancée**
   - Intégration d'un système de géocodage pour les adresses
   - Calcul automatique des distances et de l'empreinte carbone

2. **Système de fidélité**
   - Extension du système de crédits avec niveaux et avantages
   - Ajout de statuts spéciaux pour les utilisateurs réguliers

3. **Covoiturage régulier**
   - Ajout d'un système de récurrence pour les trajets quotidiens
   - Gestion des exceptions et des congés 