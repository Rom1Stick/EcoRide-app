-- ======================================================================
-- Script de création des fonctions pour EcoRide (version corrigée)
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