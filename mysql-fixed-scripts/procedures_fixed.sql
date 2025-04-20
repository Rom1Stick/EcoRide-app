-- ======================================================================
-- Script de création des procédures stockées pour EcoRide (version corrigée)
-- ======================================================================

USE ecoride;

-- Procédure pour créer une participation et effectuer la transaction de crédit associée
DROP PROCEDURE IF EXISTS creer_participation;
CREATE PROCEDURE creer_participation(
    IN p_utilisateur_id INT,
    IN p_covoiturage_id INT
)
BEGIN
    DECLARE prix DECIMAL(6,2);
    DECLARE tx_id INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Récupération du prix du covoiturage
    SELECT prix_personne INTO prix FROM Covoiturage WHERE covoiturage_id = p_covoiturage_id;
    
    -- Création de la participation
    INSERT INTO Participation (utilisateur_id, covoiturage_id, date_reservation, statut_id)
    VALUES (p_utilisateur_id, p_covoiturage_id, NOW(), 2); -- statut confirmé
    
    -- Création de la transaction de crédit (débit)
    INSERT INTO CreditTransaction (utilisateur_id, montant, type_id, description)
    VALUES (p_utilisateur_id, -prix, 2, CONCAT('Réservation covoiturage #', p_covoiturage_id));
    
    COMMIT;
END;

-- Procédure pour calculer l'empreinte carbone épargnée par un utilisateur
DROP PROCEDURE IF EXISTS calculer_empreinte_economisee;
CREATE PROCEDURE calculer_empreinte_economisee(
    IN p_utilisateur_id INT,
    OUT p_total_economise DECIMAL(10,2)
)
BEGIN
    -- Calcul pour les trajets en tant que passager
    SELECT COALESCE(SUM(c.empreinte_carbone), 0) INTO p_total_economise
    FROM Participation p
    JOIN Covoiturage c ON p.covoiturage_id = c.covoiturage_id
    WHERE p.utilisateur_id = p_utilisateur_id
    AND p.statut_id = 2; -- confirmé
END; 