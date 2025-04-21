-- ======================================================================
-- Script de création des index pour EcoRide
-- Optimisé pour l'écoconception (indexes ciblés uniquement)
-- ======================================================================

USE ecoride;

-- Index pour optimiser les recherches d'utilisateurs
CREATE INDEX idx_utilisateur_email ON Utilisateur(email);
CREATE INDEX idx_utilisateur_pseudo ON Utilisateur(pseudo);

-- Index pour optimiser les recherches de covoiturage
CREATE INDEX idx_covoiturage_date_depart ON Covoiturage(date_depart, heure_depart);
CREATE INDEX idx_covoiturage_lieu_depart ON Covoiturage(lieu_depart_id);
CREATE INDEX idx_covoiturage_lieu_arrivee ON Covoiturage(lieu_arrivee_id);
CREATE INDEX idx_covoiturage_statut ON Covoiturage(statut_id);

-- Index pour optimiser les recherches de participations
CREATE INDEX idx_participation_statut ON Participation(statut_id);

-- Index pour optimiser les recherches d'avis
CREATE INDEX idx_avis_note ON Avis(note);
CREATE INDEX idx_avis_statut ON Avis(statut_id);

-- Index pour optimiser les recherches de transactions
CREATE INDEX idx_credit_transaction_date ON CreditTransaction(date_transaction);
CREATE INDEX idx_credit_transaction_type ON CreditTransaction(type_id);

-- Création d'index pour optimiser les performances des requêtes
-- Utilisation cohérente du format idx_[table]_[colonne] pour les noms d'index
-- Ajout commentaires explicatifs pour faciliter la maintenance

-- Index sur les clés étrangères de la table Covoiturage
ALTER TABLE Covoiturage ADD INDEX idx_covoiturage_lieu_depart (lieu_depart_id);
ALTER TABLE Covoiturage ADD INDEX idx_covoiturage_lieu_arrivee (lieu_arrivee_id);
ALTER TABLE Covoiturage ADD INDEX idx_covoiturage_statut (statut_id);
ALTER TABLE Covoiturage ADD INDEX idx_covoiturage_voiture (voiture_id);
ALTER TABLE Covoiturage ADD INDEX idx_covoiturage_date_depart (date_depart); -- Index pour les recherches par date

-- Index sur les clés étrangères de la table Participation
ALTER TABLE Participation ADD INDEX idx_participation_utilisateur (utilisateur_id);
ALTER TABLE Participation ADD INDEX idx_participation_covoiturage (covoiturage_id);
ALTER TABLE Participation ADD INDEX idx_participation_statut (statut_id);

-- Index sur les clés étrangères de la table Utilisateur
ALTER TABLE Utilisateur ADD INDEX idx_utilisateur_adresse (adresse_id);

-- Index sur les clés étrangères de la table Avis
ALTER TABLE Avis ADD INDEX idx_avis_utilisateur (utilisateur_id);
ALTER TABLE Avis ADD INDEX idx_avis_covoiturage (covoiturage_id);
ALTER TABLE Avis ADD INDEX idx_avis_statut (statut_id);

-- Index sur les clés étrangères de la table CreditTransaction
ALTER TABLE CreditTransaction ADD INDEX idx_credit_transaction_utilisateur (utilisateur_id);
ALTER TABLE CreditTransaction ADD INDEX idx_credit_transaction_type (type_id);
ALTER TABLE CreditTransaction ADD INDEX idx_credit_transaction_date (date_transaction); -- Pour les recherches historiques

-- Index sur les clés étrangères de la table Voiture
ALTER TABLE Voiture ADD INDEX idx_voiture_modele (modele_id);
ALTER TABLE Voiture ADD INDEX idx_voiture_type_energie (type_energie_id);

-- Index sur les clés étrangères de la table Possede
ALTER TABLE Possede ADD INDEX idx_possede_utilisateur (utilisateur_id);
ALTER TABLE Possede ADD INDEX idx_possede_voiture (voiture_id);

-- Index sur les clés étrangères de la table Modele
ALTER TABLE Modele ADD INDEX idx_modele_marque (marque_id);

-- Index pour optimiser les recherches fréquentes
ALTER TABLE Covoiturage ADD INDEX idx_covoiturage_date_places (date_depart, nb_place, statut_id); -- Index composite pour recherche de covoiturages disponibles
ALTER TABLE Utilisateur ADD INDEX idx_utilisateur_email_mot_passe (email, mot_passe); -- Index pour l'authentification
ALTER TABLE Adresse ADD INDEX idx_adresse_ville (ville); -- Index pour la recherche par ville

-- Index pour optimiser les calculs de statistiques
ALTER TABLE CreditTransaction ADD INDEX idx_credit_transaction_montant_type (montant, type_id);
ALTER TABLE Avis ADD INDEX idx_avis_note_statut (note, statut_id);

-- Commentaire explicatif pour la maintenance
-- Ces index sont conçus pour optimiser les requêtes les plus fréquentes de l'application
-- Assurez-vous de les maintenir à jour si le schéma de la base de données évolue
-- Les index peuvent légèrement ralentir les opérations d'écriture, mais améliorent considérablement les performances de lecture

-- Commentaire sur l'approche d'indexation écologique
-- 1. Nous n'indexons que les colonnes souvent utilisées en recherche/tri/jointure
-- 2. Les index composites sont utilisés pour les requêtes fréquentes combinant plusieurs champs
-- 3. Nous évitons l'over-indexation qui augmenterait l'empreinte de stockage 