-- ======================================================================
-- Script de création des fonctions pour EcoRide
-- ======================================================================

USE ecoride;

-- Fonction pour obtenir le nombre de places disponibles pour un covoiturage
DROP FUNCTION IF EXISTS places_disponibles;
CREATE FUNCTION places_disponibles(p_covoiturage_id INT) RETURNS INT
DETERMINISTIC READS SQL DATA
BEGIN
    DECLARE places_total INT;
    DECLARE places_prises INT;
    
    -- Récupération du nombre total de places
    SELECT nb_place INTO places_total
    FROM Covoiturage WHERE covoiturage_id = p_covoiturage_id;
    
    -- Comptage des participants confirmés
    SELECT COUNT(*) INTO places_prises
    FROM Participation 
    WHERE covoiturage_id = p_covoiturage_id AND statut_id = 2;
    
    RETURN places_total - places_prises;
END;

-- Fonction pour vérifier si un utilisateur a le rôle chauffeur
DROP FUNCTION IF EXISTS est_chauffeur;
CREATE FUNCTION est_chauffeur(p_utilisateur_id INT) RETURNS BOOLEAN
DETERMINISTIC READS SQL DATA
BEGIN
    DECLARE result BOOLEAN;
    
    SELECT EXISTS (
        SELECT 1 FROM Possede 
        WHERE utilisateur_id = p_utilisateur_id 
        AND role_id = (SELECT role_id FROM Role WHERE libelle = 'chauffeur')
    ) INTO result;
    
    RETURN result;
END;

-- Fonction pour calculer la note moyenne d'un utilisateur
DROP FUNCTION IF EXISTS note_moyenne_utilisateur;
CREATE FUNCTION note_moyenne_utilisateur(p_utilisateur_id INT) RETURNS DECIMAL(3,2)
DETERMINISTIC READS SQL DATA
BEGIN
    DECLARE note_avg DECIMAL(3,2);
    
    -- Calcul de la note moyenne des avis publiés
    SELECT COALESCE(AVG(note), 0) INTO note_avg
    FROM Avis
    WHERE utilisateur_id = p_utilisateur_id
    AND statut_id = 2; -- publié
    
    RETURN note_avg;
END;

-- Fonction pour calculer la distance entre deux points GPS
DROP FUNCTION IF EXISTS calculer_distance_km;
CREATE FUNCTION calculer_distance_km(
    lat1 DECIMAL(10,8), 
    lon1 DECIMAL(11,8), 
    lat2 DECIMAL(10,8), 
    lon2 DECIMAL(11,8)
) RETURNS DECIMAL(8,2)
DETERMINISTIC NO SQL
BEGIN
    DECLARE r DECIMAL(10,2) DEFAULT 6371; -- Rayon de la Terre en km
    DECLARE dLat DECIMAL(10,8);
    DECLARE dLon DECIMAL(10,8);
    DECLARE a DECIMAL(20,18);
    DECLARE c DECIMAL(20,18);
    DECLARE d DECIMAL(10,2);
    
    SET dLat = RADIANS(lat2 - lat1);
    SET dLon = RADIANS(lon2 - lon1);
    
    SET a = SIN(dLat/2) * SIN(dLat/2) + 
            COS(RADIANS(lat1)) * COS(RADIANS(lat2)) * 
            SIN(dLon/2) * SIN(dLon/2);
    SET c = 2 * ATAN2(SQRT(a), SQRT(1-a));
    SET d = r * c;
    
    RETURN ROUND(d, 2);
END;

-- Fonction pour calculer le prix recommandé d'un trajet
DROP FUNCTION IF EXISTS calculer_prix_recommande;
CREATE FUNCTION calculer_prix_recommande(
    p_distance_km DECIMAL(8,2),
    p_nb_place INT
) RETURNS DECIMAL(6,2)
DETERMINISTIC NO SQL
BEGIN
    DECLARE prix_base DECIMAL(6,2);
    DECLARE prix_recommande DECIMAL(6,2);
    
    -- Prix de base: 0.25€/km avec un minimum de 1€
    SET prix_base = GREATEST(p_distance_km * 0.25, 1.00);
    
    -- Ajustement selon le nombre de places
    SET prix_recommande = prix_base * (1 - (p_nb_place - 1) * 0.05);
    
    RETURN ROUND(prix_recommande, 2);
END; 