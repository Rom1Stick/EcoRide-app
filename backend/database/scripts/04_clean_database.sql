-- ======================================================================
-- Script de nettoyage des tables EcoRide
-- Utilisé pour réinitialiser la base de données
-- ======================================================================

USE ecoride;

-- Désactivation temporaire de la vérification des clés étrangères
SET FOREIGN_KEY_CHECKS = 0;

-- Suppression des tables dans l'ordre inverse des dépendances
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

-- Réactivation de la vérification des clés étrangères
SET FOREIGN_KEY_CHECKS = 1;

-- Notification de fin de nettoyage
SELECT 'Toutes les tables ont été supprimées avec succès.' AS Message; 