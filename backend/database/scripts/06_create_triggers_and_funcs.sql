-- ======================================================================
-- Script de création des triggers et fonctions pour EcoRide
-- Routines automatisées pour la cohérence des données
-- ======================================================================

USE ecoride;

-- ======================================================================
-- TRIGGERS
-- ======================================================================

-- Trigger pour initialiser le solde à 0 lors de la création d'un utilisateur
DELIMITER //
CREATE TRIGGER after_utilisateur_insert
AFTER INSERT ON Utilisateur
FOR EACH ROW
BEGIN
    INSERT INTO CreditBalance (utilisateur_id, solde)
    VALUES (NEW.utilisateur_id, 0);
END; //
DELIMITER ;

-- Trigger pour vérifier la validité des dates de covoiturage
DELIMITER //
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
END; //
DELIMITER ;

-- Trigger pour gérer les places disponibles lors d'une participation
DELIMITER //
CREATE TRIGGER before_participation_insert
BEFORE INSERT ON Participation
FOR EACH ROW
BEGIN
    DECLARE places_dispo INT;
    DECLARE erreur_message VARCHAR(255);
    
    -- Vérification du nombre de places disponibles
    SELECT (c.nb_place - COUNT(p.utilisateur_id)) INTO places_dispo
    FROM Covoiturage c
    LEFT JOIN Participation p ON c.covoiturage_id = p.covoiturage_id AND p.statut_id = 2 -- confirmé
    WHERE c.covoiturage_id = NEW.covoiturage_id
    GROUP BY c.covoiturage_id;
    
    IF places_dispo <= 0 THEN
        SET erreur_message = 'Aucune place disponible pour ce covoiturage';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = erreur_message;
    END IF;
    
    -- Vérification du solde de crédits
    IF NEW.statut_id = 2 THEN -- si statut confirmé
        DECLARE prix DECIMAL(6,2);
        DECLARE solde_actuel DECIMAL(8,2);
        
        SELECT prix_personne INTO prix FROM Covoiturage WHERE covoiturage_id = NEW.covoiturage_id;
        SELECT solde INTO solde_actuel FROM CreditBalance WHERE utilisateur_id = NEW.utilisateur_id;
        
        IF solde_actuel < prix THEN
            SET erreur_message = 'Solde de crédits insuffisant pour cette réservation';
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = erreur_message;
        END IF;
    END IF;
END; //
DELIMITER ;

-- Trigger pour mettre à jour le solde lors d'une transaction de crédit
DELIMITER //
CREATE TRIGGER after_credit_transaction_insert
AFTER INSERT ON CreditTransaction
FOR EACH ROW
BEGIN
    UPDATE CreditBalance
    SET solde = solde + NEW.montant
    WHERE utilisateur_id = NEW.utilisateur_id;
END; //
DELIMITER ;

-- ======================================================================
-- PROCEDURES STOCKÉES
-- ======================================================================

-- Procédure pour créer une participation et effectuer la transaction de crédit associée
DELIMITER //
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
END; //
DELIMITER ;

-- Procédure pour calculer l'empreinte carbone épargnée par un utilisateur
DELIMITER //
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
END; //
DELIMITER ;

-- ======================================================================
-- FONCTIONS
-- ======================================================================

-- Fonction pour obtenir le nombre de places disponibles pour un covoiturage
DELIMITER //
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
END; //
DELIMITER ;

-- Fonction pour vérifier si un utilisateur a le rôle chauffeur
DELIMITER //
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
END; //
DELIMITER ;

-- Fonction pour calculer la note moyenne d'un utilisateur
DELIMITER //
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
END; //
DELIMITER ; 