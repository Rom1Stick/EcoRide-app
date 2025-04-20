-- ======================================================================
-- Script de création des triggers pour EcoRide
-- ======================================================================

USE ecoride;

-- Trigger pour initialiser le solde à 0 lors de la création d'un utilisateur
CREATE TRIGGER after_utilisateur_insert
AFTER INSERT ON Utilisateur
FOR EACH ROW
BEGIN
    INSERT INTO CreditBalance (utilisateur_id, solde)
    VALUES (NEW.utilisateur_id, 0);
END;

-- Trigger pour vérifier la validité des dates de covoiturage
CREATE TRIGGER before_covoiturage_insert
BEFORE INSERT ON Covoiturage
FOR EACH ROW
BEGIN
    DECLARE erreur_message VARCHAR(255);
    
    -- Vérification que la date de départ n'est pas dans le passé
    IF NEW.date_depart < CURRENT_DATE() OR 
       (NEW.date_depart = CURRENT_DATE() AND NEW.heure_depart < CURRENT_TIME()) THEN
        SET erreur_message = 'La date et heure de départ ne peuvent pas être dans le passé';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = erreur_message;
    END IF;
    
    -- Vérification que la date d'arrivée est cohérente avec la date de départ
    IF NEW.date_arrivee < NEW.date_depart OR 
       (NEW.date_arrivee = NEW.date_depart AND NEW.heure_arrivee <= NEW.heure_depart) THEN
        SET erreur_message = 'La date et heure d\'arrivée doivent être postérieures à la date et heure de départ';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = erreur_message;
    END IF;
END;

-- Trigger pour mettre à jour le solde lors d'une transaction de crédit
CREATE TRIGGER after_credit_transaction_insert
AFTER INSERT ON CreditTransaction
FOR EACH ROW
BEGIN
    UPDATE CreditBalance
    SET solde = solde + NEW.montant
    WHERE utilisateur_id = NEW.utilisateur_id;
END; 