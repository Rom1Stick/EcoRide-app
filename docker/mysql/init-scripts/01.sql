-- ======================================================================
-- Script de crÃ©ation de la base de donnÃ©es EcoRide
-- Base optimisÃ©e pour l'Ã©coconception et la 3FN
-- ======================================================================

-- VÃ©rification d'existence et crÃ©ation de la base de donnÃ©es
CREATE DATABASE IF NOT EXISTS ecoride 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- SÃ©lection de la base de donnÃ©es
USE ecoride;

-- Configuration de l'encodage
SET NAMES utf8mb4;
SET character_set_server = utf8mb4;
SET collation_server = utf8mb4_unicode_ci;

-- CrÃ©ation de l'utilisateur applicatif (si inexistant)
-- Note: Nous utilisons une approche compatible avec les versions rÃ©centes de MySQL
CREATE USER IF NOT EXISTS 'ecorider'@'%' IDENTIFIED BY 'securepass';

-- Attribution des droits
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER ON ecoride.* TO 'ecorider'@'%';
FLUSH PRIVILEGES;

-- RÃ©glages de performance du SGBD
-- Ces paramÃ¨tres peuvent Ãªtre ajustÃ©s selon la configuration serveur

-- Optimisation pour l'Ã©coconception
