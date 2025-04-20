-- Script de création du schéma EcoRide en 3FN
-- Optimisé pour l'écoconception et la normalisation

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS CreditTransaction;
DROP TABLE IF EXISTS TypeTransaction;
DROP TABLE IF EXISTS CreditBalance;
DROP TABLE IF EXISTS Avis;
DROP TABLE IF EXISTS StatutAvis;
DROP TABLE IF EXISTS Participation;
DROP TABLE IF EXISTS StatutParticipation;
DROP TABLE IF EXISTS Covoiturage;
DROP TABLE IF EXISTS StatutCovoiturage;
DROP TABLE IF EXISTS Voiture;
DROP TABLE IF EXISTS TypeEnergie;
DROP TABLE IF EXISTS Modele;
DROP TABLE IF EXISTS Marque;
DROP TABLE IF EXISTS Lieu;
DROP TABLE IF EXISTS Parametre;
DROP TABLE IF EXISTS Possede;
DROP TABLE IF EXISTS Utilisateur;
DROP TABLE IF EXISTS Role;
DROP TABLE IF EXISTS Adresse;
SET FOREIGN_KEY_CHECKS = 1;

-- Adresses (extraction des données d'adresse)
CREATE TABLE Adresse (
    adresse_id INT AUTO_INCREMENT PRIMARY KEY,
    rue VARCHAR(100) NOT NULL,
    ville VARCHAR(50) NOT NULL,
    code_postal VARCHAR(10) NOT NULL,
    pays VARCHAR(50) DEFAULT 'France',
    coordonnees_gps VARCHAR(50)
);

-- Table des lieux (pour normaliser les points de départ/arrivée)
CREATE TABLE Lieu (
    lieu_id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    adresse_id INT,
    FOREIGN KEY (adresse_id) REFERENCES Adresse(adresse_id)
);

-- Marques de voiture
CREATE TABLE Marque (
    marque_id INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL UNIQUE
);

-- Modèles de voiture (pour éliminer la dépendance transitive entre modèle et marque)
CREATE TABLE Modele (
    modele_id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    marque_id INT NOT NULL,
    FOREIGN KEY (marque_id) REFERENCES Marque(marque_id)
);

-- Types d'énergie (normalisation des valeurs pour énergie)
CREATE TABLE TypeEnergie (
    energie_id INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(30) NOT NULL UNIQUE
);

-- Rôles utilisateurs
CREATE TABLE Role (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL UNIQUE
);

-- Utilisateurs (référence à l'adresse plutôt que stockage direct)
CREATE TABLE Utilisateur (
    utilisateur_id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mot_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    adresse_id INT,
    date_naissance DATE,
    photo_path VARCHAR(255),
    pseudo VARCHAR(50) UNIQUE,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion DATETIME,
    FOREIGN KEY (adresse_id) REFERENCES Adresse(adresse_id)
);

-- Index pour optimiser les recherches d'utilisateurs
CREATE INDEX idx_utilisateur_email ON Utilisateur(email);
CREATE INDEX idx_utilisateur_pseudo ON Utilisateur(pseudo);

-- Association Utilisateur-Rôle
CREATE TABLE Possede (
    utilisateur_id INT NOT NULL,
    role_id INT NOT NULL,
    PRIMARY KEY (utilisateur_id, role_id),
    FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur(utilisateur_id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES Role(role_id)
);

-- Paramètres de configuration (propriété-valeur par utilisateur)
CREATE TABLE Parametre (
    parametre_id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    propriete VARCHAR(50) NOT NULL,
    valeur VARCHAR(255),
    FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur(utilisateur_id) ON DELETE CASCADE,
    UNIQUE (utilisateur_id, propriete)
);

-- Voitures (référence au modèle plutôt que stockage direct)
CREATE TABLE Voiture (
    voiture_id INT AUTO_INCREMENT PRIMARY KEY,
    modele_id INT NOT NULL,
    immatriculation VARCHAR(20) NOT NULL UNIQUE,
    energie_id INT NOT NULL,
    couleur VARCHAR(30),
    date_premiere_immat DATE,
    utilisateur_id INT NOT NULL,
    FOREIGN KEY (modele_id) REFERENCES Modele(modele_id),
    FOREIGN KEY (energie_id) REFERENCES TypeEnergie(energie_id),
    FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur(utilisateur_id) ON DELETE CASCADE
);

-- Statuts de covoiturage (normalisation des valeurs pour statut)
CREATE TABLE StatutCovoiturage (
    statut_id INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(20) NOT NULL UNIQUE
);

-- Covoiturages (références aux lieux plutôt que stockage direct)
CREATE TABLE Covoiturage (
    covoiturage_id INT AUTO_INCREMENT PRIMARY KEY,
    lieu_depart_id INT NOT NULL,
    lieu_arrivee_id INT NOT NULL,
    date_depart DATE NOT NULL,
    heure_depart TIME NOT NULL,
    date_arrivee DATE NOT NULL,
    heure_arrivee TIME NOT NULL,
    statut_id INT NOT NULL,
    nb_place INT NOT NULL CHECK (nb_place > 0),
    prix_personne DECIMAL(6,2) NOT NULL CHECK (prix_personne >= 0),
    voiture_id INT NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    empreinte_carbone DECIMAL(8,2),
    FOREIGN KEY (lieu_depart_id) REFERENCES Lieu(lieu_id),
    FOREIGN KEY (lieu_arrivee_id) REFERENCES Lieu(lieu_id),
    FOREIGN KEY (statut_id) REFERENCES StatutCovoiturage(statut_id),
    FOREIGN KEY (voiture_id) REFERENCES Voiture(voiture_id)
);

-- Index pour optimiser les recherches de covoiturage
CREATE INDEX idx_covoiturage_date_depart ON Covoiturage(date_depart, heure_depart);
CREATE INDEX idx_covoiturage_lieu_depart ON Covoiturage(lieu_depart_id);
CREATE INDEX idx_covoiturage_lieu_arrivee ON Covoiturage(lieu_arrivee_id);
CREATE INDEX idx_covoiturage_statut ON Covoiturage(statut_id);

-- Statuts de participation (normalisation des valeurs)
CREATE TABLE StatutParticipation (
    statut_id INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(20) NOT NULL UNIQUE
);

-- Participations (avec statut normalisé)
CREATE TABLE Participation (
    utilisateur_id INT NOT NULL,
    covoiturage_id INT NOT NULL,
    date_reservation DATETIME NOT NULL,
    statut_id INT NOT NULL,
    PRIMARY KEY (utilisateur_id, covoiturage_id),
    FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur(utilisateur_id) ON DELETE CASCADE,
    FOREIGN KEY (covoiturage_id) REFERENCES Covoiturage(covoiturage_id) ON DELETE CASCADE,
    FOREIGN KEY (statut_id) REFERENCES StatutParticipation(statut_id)
);

-- Index pour optimiser les recherches de participations
CREATE INDEX idx_participation_statut ON Participation(statut_id);

-- Statuts d'avis (normalisation des valeurs)
CREATE TABLE StatutAvis (
    statut_id INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(20) NOT NULL UNIQUE
);

-- Avis (avec statut normalisé)
CREATE TABLE Avis (
    avis_id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    covoiturage_id INT NOT NULL,
    commentaire TEXT,
    note TINYINT NOT NULL CHECK (note BETWEEN 1 AND 5),
    statut_id INT NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur(utilisateur_id) ON DELETE CASCADE,
    FOREIGN KEY (covoiturage_id) REFERENCES Covoiturage(covoiturage_id) ON DELETE CASCADE,
    FOREIGN KEY (statut_id) REFERENCES StatutAvis(statut_id)
);

-- Index pour optimiser les recherches d'avis
CREATE INDEX idx_avis_note ON Avis(note);
CREATE INDEX idx_avis_statut ON Avis(statut_id);

-- Balance de crédits
CREATE TABLE CreditBalance (
    utilisateur_id INT PRIMARY KEY,
    solde DECIMAL(8,2) NOT NULL DEFAULT 0 CHECK (solde >= 0),
    FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur(utilisateur_id) ON DELETE CASCADE
);

-- Types de transaction (normalisation des valeurs)
CREATE TABLE TypeTransaction (
    type_id INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(30) NOT NULL UNIQUE
);

-- Transactions de crédits
CREATE TABLE CreditTransaction (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    montant DECIMAL(8,2) NOT NULL,
    type_id INT NOT NULL,
    date_transaction DATETIME DEFAULT CURRENT_TIMESTAMP,
    description VARCHAR(255),
    FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur(utilisateur_id) ON DELETE CASCADE,
    FOREIGN KEY (type_id) REFERENCES TypeTransaction(type_id)
);

-- Index pour optimiser les recherches de transactions
CREATE INDEX idx_credit_transaction_date ON CreditTransaction(date_transaction);
CREATE INDEX idx_credit_transaction_type ON CreditTransaction(type_id);

-- Insertion des données de référence

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