1. Analyse des besoins

1.1. Recenser les entités métier

Utilisateur : Visiteurs, Passagers, Chauffeurs, Administrateurs.

Rôle : Rôle(s) attribué(s) à un utilisateur.

Configuration et Paramètre : Préférences utilisateur.

Voiture : Véhicules gérés ou utilisés.

Marque : Constructeur des véhicules.

Covoiturage : Trajets proposés.

Participation : Lien entre un utilisateur et un covoiturage.

Avis : Commentaires et notes laissés par un utilisateur sur un trajet.

Crédit (optionnel) : Historique des transactions de crédits.

1.2. Dictionnaire d’attributs (exemple partiel)

Entité

Attribut

Type

Description

Utilisateur

utilisateur_id (PK)

INT AI

Identifiant unique



nom

VARCHAR(50)

Nom de famille



prenom

VARCHAR(50)

Prénom



email

VARCHAR(100)

Adresse mail



password

VARCHAR(255)

Mot de passe haché



telephone

VARCHAR(20)

Numéro de téléphone



adresse

VARCHAR(255)

Adresse postale



date_naissance

DATE

Date de naissance



photo

BLOB

Photo de profil



pseudo

VARCHAR(50)

Identifiant public

Role

role_id (PK)

INT AI

Identifiant unique



libelle

VARCHAR(50)

Nom du rôle (e.g. "passager")

Configuration

configuration_id (PK)

INT AI

Clé primaire

Paramètre

parametre_id (PK)

INT AI

Clé primaire



propriete

VARCHAR(50)

Nom de la propriété (e.g. "theme")



valeur

VARCHAR(50)

Valeur associée (e.g. "sombre")

Voiture

voiture_id (PK)

INT AI

Identifiant unique



modele

VARCHAR(50)

Modèle du véhicule



immatriculation

VARCHAR(20)

Plaque d’immatriculation



energie

VARCHAR(20)

Type de carburant (électrique, essence, etc.)



couleur

VARCHAR(30)

Couleur du véhicule



date_premiere_immat

DATE

Date de première immatriculation

Marque

marque_id (PK)

INT AI

Identifiant unique



libelle

VARCHAR(50)

Nom de la marque

Covoiturage

covoiturage_id (PK)

INT AI

Identifiant unique



date_depart

DATE

Date de départ



heure_depart

TIME

Heure de départ



lieu_depart

VARCHAR(100)

Ville de départ



date_arrivee

DATE

Date d’arrivée



heure_arrivee

TIME

Heure d’arrivée



lieu_arrivee

VARCHAR(100)

Ville d’arrivée



statut

VARCHAR(20)

Statut du trajet ("actif","terminé")



nb_place

INT

Nombre de places initial



prix_personne

DECIMAL(6,2)

Prix par passager

Participation

utilisateur_id (FK)

INT

Référence Utilisateur



covoiturage_id (FK)

INT

Référence Covoiturage



date_reservation

DATETIME

Date de réservation

Avis

avis_id (PK)

INT AI

Identifiant unique



utilisateur_id (FK)

INT

Auteur de l’avis



covoiturage_id (FK)

INT

Trajet évalué



commentaire

TEXT

Texte de l’avis



note

TINYINT

Note (1–5)



statut

VARCHAR(20)

Statut de modération

2. Modèle Conceptuel de Données (MCD) détaillé



2.1. Relations et cardinalités

Utilisateur–Rôle (possède) : 1,1 Utilisateur → 0,n RôleUn utilisateur peut avoir plusieurs rôles (chauffeur + passager).

Utilisateur–Configuration (config) : 1,1 Utilisateur → 1,1 ConfigurationChaque utilisateur a une configuration unique.

Configuration–Paramètre (dispose) : 1,1 Configuration → 0,n ParamètreUne configuration rassemble plusieurs paramètres.

Utilisateur–Voiture (gère) : 1,1 Utilisateur → 1,n VoitureUn conducteur gère au moins une voiture.

Voiture–Marque (détient) : 1,n Voiture → 1,1 MarqueChaque véhicule appartient à une marque.

Voiture–Covoiturage (utilise) : 1,1 Voiture → 0,n CovoiturageUn véhicule peut servir à plusieurs trajets.

Utilisateur–Covoiturage (participe) : n,n (via Participation)Plusieurs utilisateurs peuvent participer à plusieurs covoiturages.

Utilisateur–Avis (dépose) : 1,1 Utilisateur → 0,n AvisUn utilisateur peut déposer plusieurs avis.

Covoiturage–Avis (associe) : 1,1 Covoiturage → 0,n AvisUn trajet peut recevoir plusieurs avis.

