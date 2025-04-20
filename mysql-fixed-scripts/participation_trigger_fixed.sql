-- ======================================================================
-- Script de création du trigger de participation pour EcoRide (version corrigée)
-- ======================================================================

USE ecoride;

-- Trigger pour gérer les places disponibles lors d'une participation
DROP TRIGGER IF EXISTS before_participation_insert;
CREATE TRIGGER before_participation_insert
BEFORE INSERT ON Participation
FOR EACH ROW
BEGIN
    DECLARE places_dispo INT DEFAULT 0;
    DECLARE erreur_message VARCHAR(255);
    DECLARE prix DECIMAL(6,2);
    DECLARE solde_actuel DECIMAL(8,2);
    
    -- Vérification du nombre de places disponibles
    SELECT (c.nb_place - COUNT(p.utilisateur_id)) INTO places_dispo
    FROM Covoiturage c
    LEFT JOIN Participation p ON c.covoiturage_id = p.covoiturage_id AND p.statut_id = 2
    WHERE c.covoiturage_id = NEW.covoiturage_id
    GROUP BY c.covoiturage_id;
    
    IF places_dispo <= 0 THEN
        SET erreur_message = 'Aucune place disponible pour ce covoiturage';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = erreur_message;
    END IF;
    
    -- Vérification du solde de crédits
    IF NEW.statut_id = 2 THEN
        SELECT prix_personne INTO prix FROM Covoiturage WHERE covoiturage_id = NEW.covoiturage_id;
        SELECT solde INTO solde_actuel FROM CreditBalance WHERE utilisateur_id = NEW.utilisateur_id;
        
        IF solde_actuel < prix THEN
            SET erreur_message = 'Solde de crédits insuffisant pour cette réservation';
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = erreur_message;
        END IF;
    END IF;
END; 