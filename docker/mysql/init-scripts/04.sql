-- ======================================================================
-- Script d'insertion des données de référence pour EcoRide
-- ======================================================================

USE ecoride;

-- Transaction pour assurer l'intégrité des insertions
BEGIN;

-- Rôles utilisateurs
INSERT INTO Role (libelle) VALUES 
('visiteur'), ('passager'), ('chauffeur'), ('admin');

-- Types d'énergie
INSERT INTO TypeEnergie (libelle) VALUES 
('Électrique'), ('Essence'), ('Diesel'), ('Hybride'), ('GPL');

-- Statuts de covoiturage
INSERT INTO StatutCovoiturage (libelle) VALUES 
('planifié'), ('en_cours'), ('terminé'), ('annulé');

-- Statuts de participation
INSERT INTO StatutParticipation (libelle) VALUES 
('en_attente'), ('confirmé'), ('annulé');

-- Statuts d'avis
INSERT INTO StatutAvis (libelle) VALUES 
('en_attente'), ('publié'), ('rejeté');

-- Types de transaction
INSERT INTO TypeTransaction (libelle) VALUES 
('initial'), ('achat_trajet'), ('bonus'), ('annulation'), ('autre');

-- Validation des insertions
COMMIT;

-- Vérification des données insérées
SELECT 'Données de référence insérées avec succès' AS Message;
SELECT libelle FROM Role ORDER BY role_id;
SELECT libelle FROM TypeEnergie ORDER BY energie_id;
SELECT libelle FROM StatutCovoiturage ORDER BY statut_id;
SELECT libelle FROM StatutParticipation ORDER BY statut_id;
SELECT libelle FROM StatutAvis ORDER BY statut_id;
SELECT libelle FROM TypeTransaction ORDER BY type_id; 