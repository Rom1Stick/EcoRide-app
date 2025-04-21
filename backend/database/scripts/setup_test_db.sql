-- Script de configuration de la base de données de test
-- À exécuter avant les tests d'intégration

-- Création de la base de données de test
CREATE DATABASE IF NOT EXISTS ecoride_test;

-- Création de l'utilisateur de test
CREATE USER IF NOT EXISTS 'ecoride_test'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON ecoride_test.* TO 'ecoride_test'@'localhost';
FLUSH PRIVILEGES;

-- Utilisation de la base de données de test
USE ecoride_test;

-- Suppression des tables existantes si nécessaire
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS covoiturage;
DROP TABLE IF EXISTS Voiture;
DROP TABLE IF EXISTS Modele;
DROP TABLE IF EXISTS Marque;
DROP TABLE IF EXISTS Energie;
DROP TABLE IF EXISTS Utilisateur;
DROP TABLE IF EXISTS lieu;
DROP TABLE IF EXISTS statut;
SET FOREIGN_KEY_CHECKS = 1;

-- Création des tables pour les tests
CREATE TABLE Marque (
    marque_id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL
);

CREATE TABLE Modele (
    modele_id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    marque_id INT NOT NULL,
    FOREIGN KEY (marque_id) REFERENCES Marque(marque_id)
);

CREATE TABLE Energie (
    energie_id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL
);

CREATE TABLE Utilisateur (
    utilisateur_id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL
);

CREATE TABLE Voiture (
    voiture_id INT AUTO_INCREMENT PRIMARY KEY,
    modele_id INT NOT NULL,
    immatriculation VARCHAR(10) NOT NULL UNIQUE,
    energie_id INT NOT NULL,
    couleur VARCHAR(30),
    date_premiere_immat DATE,
    utilisateur_id INT NOT NULL,
    FOREIGN KEY (modele_id) REFERENCES Modele(modele_id),
    FOREIGN KEY (energie_id) REFERENCES Energie(energie_id),
    FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur(utilisateur_id)
);

CREATE TABLE lieu (
    lieu_id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    code_postal VARCHAR(10) NOT NULL
);

CREATE TABLE statut (
    statut_id INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL
);

CREATE TABLE covoiturage (
    covoiturage_id INT AUTO_INCREMENT PRIMARY KEY,
    lieu_depart_id INT NOT NULL,
    lieu_arrivee_id INT NOT NULL,
    date_depart DATE NOT NULL,
    heure_depart TIME NOT NULL,
    date_arrivee DATE,
    heure_arrivee TIME,
    statut_id INT NOT NULL,
    nb_place INT NOT NULL,
    prix_personne DECIMAL(10, 2) NOT NULL,
    voiture_id INT NOT NULL,
    date_creation DATETIME NOT NULL,
    empreinte_carbone DECIMAL(10, 2),
    FOREIGN KEY (lieu_depart_id) REFERENCES lieu(lieu_id),
    FOREIGN KEY (lieu_arrivee_id) REFERENCES lieu(lieu_id),
    FOREIGN KEY (statut_id) REFERENCES statut(statut_id),
    FOREIGN KEY (voiture_id) REFERENCES Voiture(voiture_id)
);

-- Insertion de données de base pour les tests
INSERT INTO Marque (marque_id, nom) VALUES 
(1, 'Volkswagen'),
(2, 'Renault');

INSERT INTO Modele (modele_id, nom, marque_id) VALUES 
(1, 'Golf', 1),
(2, 'Clio', 2);

INSERT INTO Energie (energie_id, nom) VALUES 
(1, 'Essence'),
(2, 'Diesel'),
(3, 'Électrique');

INSERT INTO lieu (lieu_id, nom, code_postal) VALUES 
(1, 'Paris', '75000'),
(2, 'Lyon', '69000'),
(3, 'Marseille', '13000');

INSERT INTO statut (statut_id, libelle) VALUES 
(1, 'En attente'),
(2, 'Confirmé'),
(3, 'Annulé'); 