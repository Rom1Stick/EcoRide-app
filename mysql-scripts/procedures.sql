-- ======================================================================
-- Script de création des procédures stockées pour EcoRide
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

-- Procédure pour annuler une participation et rembourser les crédits
DROP PROCEDURE IF EXISTS annuler_participation;
CREATE PROCEDURE annuler_participation(
    IN p_participation_id INT
)
BEGIN
    DECLARE v_utilisateur_id INT;
    DECLARE v_covoiturage_id INT;
    DECLARE v_prix DECIMAL(6,2);
    DECLARE v_date_depart DATE;
    DECLARE v_heure_depart TIME;
    DECLARE v_delai_annulation INT DEFAULT 24; -- Heures avant le départ pour une annulation sans frais
    DECLARE v_frais_annulation DECIMAL(6,2);
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Récupération des informations nécessaires
    SELECT p.utilisateur_id, p.covoiturage_id, c.prix_personne, c.date_depart, c.heure_depart
    INTO v_utilisateur_id, v_covoiturage_id, v_prix, v_date_depart, v_heure_depart
    FROM Participation p
    JOIN Covoiturage c ON p.covoiturage_id = c.covoiturage_id
    WHERE p.participation_id = p_participation_id;
    
    -- Calcul des frais d'annulation en fonction du délai
    IF TIMESTAMPDIFF(HOUR, NOW(), CONCAT(v_date_depart, ' ', v_heure_depart)) >= v_delai_annulation THEN
        -- Annulation sans frais
        SET v_frais_annulation = 0;
    ELSE
        -- Frais d'annulation de 50%
        SET v_frais_annulation = v_prix * 0.5;
    END IF;
    
    -- Mise à jour du statut de la participation
    UPDATE Participation
    SET statut_id = 3 -- Annulé
    WHERE participation_id = p_participation_id;
    
    -- Remboursement des crédits (moins les frais d'annulation)
    INSERT INTO CreditTransaction (utilisateur_id, montant, type_id, description)
    VALUES (v_utilisateur_id, v_prix - v_frais_annulation, 3, 
            CONCAT('Remboursement covoiturage #', v_covoiturage_id, ' (frais: ', v_frais_annulation, '€)'));
    
    COMMIT;
END;

-- Procédure pour finaliser un covoiturage et effectuer les paiements au chauffeur
DROP PROCEDURE IF EXISTS finaliser_covoiturage;
CREATE PROCEDURE finaliser_covoiturage(
    IN p_covoiturage_id INT
)
BEGIN
    DECLARE v_chauffeur_id INT;
    DECLARE v_montant_total DECIMAL(8,2);
    DECLARE v_commission DECIMAL(8,2);
    DECLARE v_montant_net DECIMAL(8,2);
    DECLARE v_commission_taux DECIMAL(4,2) DEFAULT 0.10; -- 10% de commission
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Récupération de l'ID du chauffeur
    SELECT utilisateur_id INTO v_chauffeur_id
    FROM Covoiturage
    WHERE covoiturage_id = p_covoiturage_id;
    
    -- Calcul du montant total à payer au chauffeur
    SELECT SUM(c.prix_personne) INTO v_montant_total
    FROM Participation p
    JOIN Covoiturage c ON p.covoiturage_id = c.covoiturage_id
    WHERE p.covoiturage_id = p_covoiturage_id
    AND p.statut_id = 2; -- Confirmé
    
    -- Calcul de la commission et du montant net
    SET v_commission = v_montant_total * v_commission_taux;
    SET v_montant_net = v_montant_total - v_commission;
    
    -- Mise à jour du statut du covoiturage
    UPDATE Covoiturage
    SET statut_id = 4 -- Terminé
    WHERE covoiturage_id = p_covoiturage_id;
    
    -- Paiement au chauffeur
    INSERT INTO CreditTransaction (utilisateur_id, montant, type_id, description)
    VALUES (v_chauffeur_id, v_montant_net, 1, 
            CONCAT('Paiement covoiturage #', p_covoiturage_id, ' (commission: ', v_commission, '€)'));
    
    COMMIT;
END; 