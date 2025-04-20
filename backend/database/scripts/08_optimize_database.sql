-- ======================================================================
-- Script d'optimisation de la base de données EcoRide
-- À exécuter uniquement en environnement de production
-- ======================================================================

USE ecoride;

-- ======================================================================
-- PARTITIONNEMENT DES TABLES
-- ======================================================================

-- Partitionnement de la table CreditTransaction par mois
-- Utile pour les requêtes historiques et l'archivage
ALTER TABLE CreditTransaction PARTITION BY RANGE (YEAR(date_transaction)*100 + MONTH(date_transaction)) (
    PARTITION p_start VALUES LESS THAN (202301),
    PARTITION p_202301 VALUES LESS THAN (202302),
    PARTITION p_202302 VALUES LESS THAN (202303),
    PARTITION p_202303 VALUES LESS THAN (202304),
    PARTITION p_202304 VALUES LESS THAN (202305),
    PARTITION p_202305 VALUES LESS THAN (202306),
    PARTITION p_202306 VALUES LESS THAN (202307),
    PARTITION p_202307 VALUES LESS THAN (202308),
    PARTITION p_202308 VALUES LESS THAN (202309),
    PARTITION p_202309 VALUES LESS THAN (202310),
    PARTITION p_202310 VALUES LESS THAN (202311),
    PARTITION p_202311 VALUES LESS THAN (202312),
    PARTITION p_202312 VALUES LESS THAN (202401),
    PARTITION p_max VALUES LESS THAN MAXVALUE
);

-- Partitionnement de la table Covoiturage par mois de départ
ALTER TABLE Covoiturage PARTITION BY RANGE (YEAR(date_depart)*100 + MONTH(date_depart)) (
    PARTITION p_start VALUES LESS THAN (202301),
    PARTITION p_202301 VALUES LESS THAN (202302),
    PARTITION p_202302 VALUES LESS THAN (202303),
    PARTITION p_202303 VALUES LESS THAN (202304),
    PARTITION p_202304 VALUES LESS THAN (202305),
    PARTITION p_202305 VALUES LESS THAN (202306),
    PARTITION p_202306 VALUES LESS THAN (202307),
    PARTITION p_202307 VALUES LESS THAN (202308),
    PARTITION p_202308 VALUES LESS THAN (202309),
    PARTITION p_202309 VALUES LESS THAN (202310),
    PARTITION p_202310 VALUES LESS THAN (202311),
    PARTITION p_202311 VALUES LESS THAN (202312),
    PARTITION p_202312 VALUES LESS THAN (202401),
    PARTITION p_max VALUES LESS THAN MAXVALUE
);

-- ======================================================================
-- OPTIMISATION DES REQUÊTES FRÉQUENTES
-- ======================================================================

-- Vues matérialisées (implémentées comme tables)
-- Vue matérialisée pour les covoiturages à venir avec places disponibles
CREATE TABLE mv_covoiturages_disponibles (
    covoiturage_id INT PRIMARY KEY,
    lieu_depart_id INT NOT NULL,
    lieu_arrivee_id INT NOT NULL,
    lieu_depart_nom VARCHAR(100),
    lieu_arrivee_nom VARCHAR(100),
    date_depart DATE NOT NULL,
    heure_depart TIME NOT NULL,
    date_arrivee DATE NOT NULL,
    heure_arrivee TIME NOT NULL,
    prix_personne DECIMAL(6,2) NOT NULL,
    places_total INT NOT NULL,
    places_restantes INT NOT NULL,
    chauffeur_id INT NOT NULL,
    chauffeur_nom VARCHAR(50),
    chauffeur_prenom VARCHAR(50),
    empreinte_carbone DECIMAL(8,2),
    derniere_maj TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Procédure pour mettre à jour la vue matérialisée
DELIMITER //
CREATE PROCEDURE maj_mv_covoiturages_disponibles()
BEGIN
    -- Vider la table
    TRUNCATE TABLE mv_covoiturages_disponibles;
    
    -- Remplir avec les données à jour
    INSERT INTO mv_covoiturages_disponibles
    SELECT 
        c.covoiturage_id,
        c.lieu_depart_id,
        c.lieu_arrivee_id,
        ld.nom AS lieu_depart_nom,
        la.nom AS lieu_arrivee_nom,
        c.date_depart,
        c.heure_depart,
        c.date_arrivee,
        c.heure_arrivee,
        c.prix_personne,
        c.nb_place AS places_total,
        c.nb_place - COALESCE(COUNT(p.utilisateur_id), 0) AS places_restantes,
        u.utilisateur_id AS chauffeur_id,
        u.nom AS chauffeur_nom,
        u.prenom AS chauffeur_prenom,
        c.empreinte_carbone,
        NOW() AS derniere_maj
    FROM Covoiturage c
    JOIN Lieu ld ON c.lieu_depart_id = ld.lieu_id
    JOIN Lieu la ON c.lieu_arrivee_id = la.lieu_id
    JOIN Voiture v ON c.voiture_id = v.voiture_id
    JOIN Utilisateur u ON v.utilisateur_id = u.utilisateur_id
    LEFT JOIN Participation p ON c.covoiturage_id = p.covoiturage_id AND p.statut_id = 2
    WHERE c.statut_id = 1 -- planifiés
      AND c.date_depart >= CURRENT_DATE()
      AND (c.date_depart > CURRENT_DATE() OR c.heure_depart > CURRENT_TIME())
    GROUP BY c.covoiturage_id
    HAVING places_restantes > 0;
END //
DELIMITER ;

-- Exécution initiale de la mise à jour
CALL maj_mv_covoiturages_disponibles();

-- Événement pour mettre à jour la vue matérialisée toutes les heures
DELIMITER //
CREATE EVENT IF NOT EXISTS event_maj_covoiturages_disponibles
ON SCHEDULE EVERY 1 HOUR
DO
BEGIN
    CALL maj_mv_covoiturages_disponibles();
END //
DELIMITER ;

-- ======================================================================
-- OPTIMISATION DE LA MÉMOIRE
-- ======================================================================

-- Configuration du buffer pool pour les tables fréquemment utilisées
-- Cette commande charge les index et données de ces tables en mémoire
SET GLOBAL innodb_buffer_pool_dump_now = ON;  -- Sauvegarde l'état actuel

-- Analyse et optimisation des tables principales
ANALYZE TABLE Utilisateur, Covoiturage, Participation;
OPTIMIZE TABLE CreditTransaction, Avis;

-- ======================================================================
-- STATISTIQUES POUR LE PLANIFICATEUR DE REQUÊTES
-- ======================================================================

-- Mise à jour des statistiques d'index pour améliorer les plans d'exécution
ANALYZE TABLE Utilisateur, Covoiturage, Participation, CreditTransaction, Avis;

-- Mise en place de vérifications de santé périodiques
DELIMITER //
CREATE EVENT IF NOT EXISTS event_verifier_sante_db
ON SCHEDULE EVERY 1 DAY
DO
BEGIN
    -- Vérification de fragmentation des tables principales
    OPTIMIZE TABLE Covoiturage, Participation, CreditTransaction;
    
    -- Mise à jour des statistiques
    ANALYZE TABLE Utilisateur, Voiture, Covoiturage, Participation;
END //
DELIMITER ;

-- Activation de l'ordonnanceur d'événements
SET GLOBAL event_scheduler = ON; 