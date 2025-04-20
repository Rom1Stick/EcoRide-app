-- ======================================================================
-- Script principal pour créer toutes les fonctions, procédures et triggers
-- ======================================================================

USE ecoride;

-- FONCTIONS
-- ======================================================================

-- Fonction pour obtenir le nombre de places disponibles pour un covoiturage
DROP FUNCTION IF EXISTS places_disponibles;
DELIMITER $$
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
END$$
DELIMITER ;

-- Fonction pour vérifier si un utilisateur a le rôle chauffeur
DROP FUNCTION IF EXISTS est_chauffeur;
DELIMITER $$
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
END$$
DELIMITER ;

-- Fonction pour calculer la note moyenne d'un utilisateur
DROP FUNCTION IF EXISTS note_moyenne_utilisateur;
DELIMITER $$
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
END$$
DELIMITER ;

-- PROCÉDURES
-- ======================================================================

-- Procédure pour créer une participation et effectuer la transaction de crédit associée
DROP PROCEDURE IF EXISTS creer_participation;
DELIMITER $$
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
END$$
DELIMITER ;

-- TRIGGERS
-- ======================================================================

-- Trigger pour initialiser le solde à 0 lors de la création d'un utilisateur
DROP TRIGGER IF EXISTS after_utilisateur_insert;
DELIMITER $$
CREATE TRIGGER after_utilisateur_insert
AFTER INSERT ON Utilisateur
FOR EACH ROW
BEGIN
    INSERT INTO CreditBalance (utilisateur_id, solde)
    VALUES (NEW.utilisateur_id, 0);
END$$
DELIMITER ;

-- Trigger pour mettre à jour le solde lors d'une transaction de crédit
DROP TRIGGER IF EXISTS after_credit_transaction_insert;
DELIMITER $$
CREATE TRIGGER after_credit_transaction_insert
AFTER INSERT ON CreditTransaction
FOR EACH ROW
BEGIN
    UPDATE CreditBalance
    SET solde = solde + NEW.montant
    WHERE utilisateur_id = NEW.utilisateur_id;
END$$
DELIMITER ;

-- Trigger pour vérifier la validité des dates de covoiturage
DROP TRIGGER IF EXISTS before_covoiturage_insert;
DELIMITER $$
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
END$$
DELIMITER ; 