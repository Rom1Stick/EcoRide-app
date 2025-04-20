-- ======================================================================
-- Script de création des triggers pour EcoRide
-- ======================================================================

USE ecoride;

-- Trigger pour initialiser le solde à 0 lors de la création d'un utilisateur
DROP TRIGGER IF EXISTS after_utilisateur_insert;
CREATE TRIGGER after_utilisateur_insert
AFTER INSERT ON Utilisateur
FOR EACH ROW
BEGIN
    INSERT INTO CreditBalance (utilisateur_id, solde)
    VALUES (NEW.utilisateur_id, 0);
END;

-- Trigger pour vérifier la validité des dates de covoiturage
DROP TRIGGER IF EXISTS before_covoiturage_insert;
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
DROP TRIGGER IF EXISTS after_credit_transaction_insert;
CREATE TRIGGER after_credit_transaction_insert
AFTER INSERT ON CreditTransaction
FOR EACH ROW
BEGIN
    UPDATE CreditBalance
    SET solde = solde + NEW.montant
    WHERE utilisateur_id = NEW.utilisateur_id;
END;

-- Trigger pour vérifier les places disponibles lors d'une participation
DROP TRIGGER IF EXISTS before_participation_insert;
CREATE TRIGGER before_participation_insert
BEFORE INSERT ON Participation
FOR EACH ROW
BEGIN
    DECLARE places_dispo INT;
    DECLARE erreur_message VARCHAR(255);
    
    -- Vérification des places disponibles
    SELECT places_disponibles(NEW.covoiturage_id) INTO places_dispo;
    
    IF places_dispo <= 0 THEN
        SET erreur_message = 'Aucune place disponible pour ce covoiturage';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = erreur_message;
    END IF;
END;

-- Trigger pour mettre à jour le statut du covoiturage quand toutes les places sont prises
DROP TRIGGER IF EXISTS after_participation_insert_update;
CREATE TRIGGER after_participation_insert_update
AFTER INSERT ON Participation
FOR EACH ROW
BEGIN
    DECLARE places_dispo INT;
    
    -- Récupération des places disponibles
    SELECT places_disponibles(NEW.covoiturage_id) INTO places_dispo;
    
    -- Si plus de places disponibles, mettre à jour le statut du covoiturage
    IF places_dispo = 0 THEN
        UPDATE Covoiturage
        SET statut_id = 3 -- Complet
        WHERE covoiturage_id = NEW.covoiturage_id;
    END IF;
END; 