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

-- Réglages de performance du SGBD
-- Ces paramètres peuvent être ajustés selon la configuration serveur
SET GLOBAL innodb_buffer_pool_size = 1073741824; -- 1GB (ajustez selon disponibilité RAM)
SET GLOBAL innodb_log_file_size = 268435456;     -- 256MB
SET GLOBAL innodb_flush_log_at_trx_commit = 2;   -- Compromis performance/durabilité
SET GLOBAL max_connections = 200;                -- Ajustez selon charge utilisateurs

-- Optimisation pour l'écoconception
SET GLOBAL innodb_file_per_table = ON;           -- Libération d'espace lors de suppressions
SET GLOBAL innodb_stats_on_metadata = OFF;       -- Réduction des opérations I/O 