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

-- Commentaire sur l'approche d'indexation écologique
-- 1. Nous n'indexons que les colonnes souvent utilisées en recherche/tri/jointure
-- 2. Les index composites sont utilisés pour les requêtes fréquentes combinant plusieurs champs
-- 3. Nous évitons l'over-indexation qui augmenterait l'empreinte de stockage 