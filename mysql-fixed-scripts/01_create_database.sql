-- ======================================================================
-- Script de création de la base de données EcoRide
-- Base optimisée pour l'écoconception et la 3FN
-- ======================================================================

-- Vérification d'existence et création de la base de données
CREATE DATABASE IF NOT EXISTS ecoride 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Sélection de la base de données
USE ecoride;

-- Configuration de l'encodage
SET NAMES utf8mb4;
SET character_set_server = utf8mb4;
SET collation_server = utf8mb4_unicode_ci;

-- Création de l'utilisateur applicatif (si inexistant)
-- Note: Nous utilisons une approche compatible avec les versions récentes de MySQL
CREATE USER IF NOT EXISTS 'ecorider'@'%' IDENTIFIED BY 'securepass';

-- Attribution des droits
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER ON ecoride.* TO 'ecorider'@'%';
FLUSH PRIVILEGES;

-- Optimisations de performance appliquées via fichier de configuration my.cnf 