-- ======================================================================
-- Script de création des triggers et fonctions pour EcoRide
-- Routines automatisées pour la cohérence des données
-- Optimisé pour l'écoconception et performance maximale
-- ======================================================================

USE ecoride;

-- ======================================================================
-- FONCTIONS
-- ======================================================================

-- Fonction pour vérifier les places disponibles pour un covoiturage
-- Optimisée avec COUNT pour une meilleure performance
DELIMITER //
CREATE FUNCTION places_disponibles(
    p_covoiturage_id INT
) RETURNS INT
READS SQL DATA
BEGIN
    DECLARE total_places INT;
    DECLARE places_prises INT;
    
    -- Récupérer le nombre total de places du covoiturage avec un WHERE simple
    SELECT nb_place INTO total_places
    FROM Covoiturage
    WHERE covoiturage_id = p_covoiturage_id
    LIMIT 1;
    
    -- Compter le nombre de places déjà prises avec inner join et count optimisé
    SELECT COUNT(*) INTO places_prises
    FROM Participation
    WHERE covoiturage_id = p_covoiturage_id
    AND statut_id = 2; -- confirmé
    
    RETURN total_places - places_prises;
END; //
DELIMITER ;

-- Fonction pour vérifier si un utilisateur est un chauffeur
-- Simple et efficace avec EXISTS
DELIMITER //
CREATE FUNCTION est_chauffeur(
    p_utilisateur_id INT
) RETURNS BOOLEAN
READS SQL DATA
BEGIN
    DECLARE result BOOLEAN;
    
    -- Utilise EXISTS pour une vérification rapide sans avoir à récupérer les données
    SELECT EXISTS (
        SELECT 1
        FROM Voiture v
        JOIN Possede p ON v.voiture_id = p.voiture_id
        WHERE p.utilisateur_id = p_utilisateur_id
        LIMIT 1
    ) INTO result;
    
    RETURN result;
END; //
DELIMITER ;

-- Fonction pour calculer la note moyenne d'un utilisateur
-- Optimisée avec NULL handling et AVG direct
DELIMITER //
CREATE FUNCTION note_moyenne_utilisateur(
    p_utilisateur_id INT
) RETURNS DECIMAL(3,1)
READS SQL DATA
BEGIN
    DECLARE note_moyenne DECIMAL(3,1);
    
    -- Calcul direct avec AVG et COALESCE pour optimisation
    SELECT COALESCE(AVG(note), 0)
    INTO note_moyenne
    FROM Avis
    WHERE utilisateur_id = p_utilisateur_id;
    
    RETURN note_moyenne;
END; //
DELIMITER ;

-- Fonction pour calculer la distance entre deux points GPS
-- Optimisée avec NO SQL car ne dépend pas des données de la base
-- Utilise la formule de Haversine pour une précision maximale
DELIMITER //
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
END; //
DELIMITER ;

-- ======================================================================
-- TRIGGERS
-- ======================================================================

-- Trigger pour initialiser le solde à 0 lors de la création d'un utilisateur
-- Simple, efficace et assurant l'intégrité des données
DROP TRIGGER IF EXISTS after_utilisateur_insert;
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
-- Validation complète des contraintes temporelles
DROP TRIGGER IF EXISTS before_covoiturage_insert;
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
-- Vérifications multiples pour garantir la cohérence des réservations
DROP TRIGGER IF EXISTS before_participation_insert;
DELIMITER //
CREATE TRIGGER before_participation_insert
BEFORE INSERT ON Participation
FOR EACH ROW
BEGIN
    DECLARE places_dispo INT;
    DECLARE erreur_message VARCHAR(255);
    DECLARE prix DECIMAL(6,2);
    DECLARE solde_actuel DECIMAL(8,2);
    
    -- Vérification du nombre de places disponibles avec fonction optimisée
    SELECT places_disponibles(NEW.covoiturage_id) INTO places_dispo;
    
    IF places_dispo <= 0 THEN
        SET erreur_message = 'Aucune place disponible pour ce covoiturage';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = erreur_message;
    END IF;
    
    -- Vérification du solde de crédits - optimisé avec requêtes directes
    IF NEW.statut_id = 2 THEN -- si statut confirmé
        SELECT prix_personne INTO prix 
        FROM Covoiturage 
        WHERE covoiturage_id = NEW.covoiturage_id
        LIMIT 1;
        
        SELECT solde INTO solde_actuel 
        FROM CreditBalance 
        WHERE utilisateur_id = NEW.utilisateur_id
        LIMIT 1;
        
        IF solde_actuel < prix THEN
            SET erreur_message = 'Solde de crédits insuffisant pour cette réservation';
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = erreur_message;
        END IF;
    END IF;
END; //
DELIMITER ;

-- Trigger pour mettre à jour le solde lors d'une transaction de crédit
-- Efficace et direct avec UPDATE ciblé
DROP TRIGGER IF EXISTS after_credit_transaction_insert;
DELIMITER //
CREATE TRIGGER after_credit_transaction_insert
AFTER INSERT ON CreditTransaction
FOR EACH ROW
BEGIN
    -- Utilise UPDATE avec clause WHERE spécifique pour limiter l'impact
    UPDATE CreditBalance
    SET solde = solde + NEW.montant
    WHERE utilisateur_id = NEW.utilisateur_id;
END; //
DELIMITER ;

-- Trigger pour mettre à jour le statut du covoiturage quand toutes les places sont prises
-- Optimisé pour limiter les updates inutiles
DROP TRIGGER IF EXISTS after_participation_insert_update;
DELIMITER //
CREATE TRIGGER after_participation_insert_update
AFTER INSERT ON Participation
FOR EACH ROW
BEGIN
    DECLARE places_dispo INT;
    
    -- Utilisation efficace de la fonction places_disponibles
    SELECT places_disponibles(NEW.covoiturage_id) INTO places_dispo;
    
    -- Exécution conditionnelle pour limiter l'impact sur la base
    IF places_dispo = 0 THEN
        UPDATE Covoiturage
        SET statut_id = 3 -- Complet
        WHERE covoiturage_id = NEW.covoiturage_id
        AND statut_id != 3; -- Évite les updates inutiles si déjà complet
    END IF;
END; //
DELIMITER ;

-- ======================================================================
-- PROCÉDURES STOCKÉES
-- ======================================================================

-- Procédure pour créer une participation et effectuer la transaction de crédit associée
-- Optimisée avec transaction et gestion d'erreurs
DROP PROCEDURE IF EXISTS creer_participation;
DELIMITER //
CREATE PROCEDURE creer_participation(
    IN p_utilisateur_id INT,
    IN p_covoiturage_id INT
)
BEGIN
    DECLARE prix DECIMAL(6,2);
    DECLARE places_dispo INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    -- Vérification préalable pour éviter les transactions inutiles
    SELECT places_disponibles(p_covoiturage_id) INTO places_dispo;
    IF places_dispo <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Aucune place disponible pour ce covoiturage';
    END IF;
    
    START TRANSACTION;
    
    -- Récupération du prix du covoiturage avec LIMIT 1 pour optimisation
    SELECT prix_personne INTO prix FROM Covoiturage WHERE covoiturage_id = p_covoiturage_id LIMIT 1;
    
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
-- Optimisée avec jointure efficace et index
DROP PROCEDURE IF EXISTS calculer_empreinte_economisee;
DELIMITER //
CREATE PROCEDURE calculer_empreinte_economisee(
    IN p_utilisateur_id INT,
    OUT p_total_economise DECIMAL(10,2)
)
BEGIN
    -- Calcul optimisé pour les trajets en tant que passager
    SELECT COALESCE(SUM(c.empreinte_carbone), 0) INTO p_total_economise
    FROM Participation p
    INNER JOIN Covoiturage c ON p.covoiturage_id = c.covoiturage_id
    WHERE p.utilisateur_id = p_utilisateur_id
    AND p.statut_id = 2; -- confirmé
END; //
DELIMITER ;

-- Procédure pour annuler une participation et rembourser les crédits
-- Avec gestion complète des transactions et conditions de remboursement
DROP PROCEDURE IF EXISTS annuler_participation;
DELIMITER //
CREATE PROCEDURE annuler_participation(
    IN p_utilisateur_id INT,
    IN p_covoiturage_id INT
)
BEGIN
    DECLARE v_date_depart DATE;
    DECLARE v_heure_depart TIME;
    DECLARE v_statut_actuel INT;
    DECLARE v_prix DECIMAL(6,2);
    DECLARE v_delai_annulation INT DEFAULT 24; -- Heures avant le départ pour une annulation sans frais
    DECLARE v_remboursement DECIMAL(6,2);
    DECLARE v_erreur_message VARCHAR(255);
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    -- Vérification que la participation existe et récupération des infos
    SELECT c.date_depart, c.heure_depart, p.statut_id, c.prix_personne 
    INTO v_date_depart, v_heure_depart, v_statut_actuel, v_prix
    FROM Participation p
    JOIN Covoiturage c ON p.covoiturage_id = c.covoiturage_id
    WHERE p.utilisateur_id = p_utilisateur_id
    AND p.covoiturage_id = p_covoiturage_id
    LIMIT 1;
    
    -- Vérification que la participation existe
    IF v_statut_actuel IS NULL THEN
        SET v_erreur_message = 'Participation inexistante';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_erreur_message;
    END IF;
    
    -- Vérification que la participation n'est pas déjà annulée
    IF v_statut_actuel = 3 THEN -- annulé
        SET v_erreur_message = 'Participation déjà annulée';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_erreur_message;
    END IF;
    
    -- Calcul du remboursement en fonction du délai avant départ
    -- Si plus de v_delai_annulation heures avant le départ : remboursement total
    -- Sinon : remboursement partiel ou nul selon politique
    IF TIMESTAMPDIFF(HOUR, NOW(), CONCAT(v_date_depart, ' ', v_heure_depart)) >= v_delai_annulation THEN
        SET v_remboursement = v_prix; -- Remboursement total
    ELSE
        SET v_remboursement = v_prix * 0.5; -- Remboursement partiel (50%)
    END IF;
    
    START TRANSACTION;
    
    -- Mise à jour du statut de la participation
    UPDATE Participation
    SET statut_id = 3 -- annulé
    WHERE utilisateur_id = p_utilisateur_id
    AND covoiturage_id = p_covoiturage_id;
    
    -- Création de la transaction de crédit (remboursement)
    IF v_remboursement > 0 THEN
        INSERT INTO CreditTransaction (utilisateur_id, montant, type_id, description)
        VALUES (p_utilisateur_id, v_remboursement, 3, CONCAT('Remboursement covoiturage #', p_covoiturage_id));
    END IF;
    
    COMMIT;
END; //
DELIMITER ;

-- Ajoute ici des commentaires pour la documentation des fonctions et procédures
/*
 * DOCUMENTATION DES FONCTIONS ET PROCÉDURES 
 * =========================================
 *
 * Fonction: places_disponibles
 * ----------------------------
 * Calcule le nombre de places encore disponibles pour un covoiturage donné.
 * 
 * Paramètres:
 *   p_covoiturage_id (INT) - L'identifiant du covoiturage
 * 
 * Retourne:
 *   INT - Le nombre de places disponibles
 *
 * Exemple d'utilisation:
 *   SELECT places_disponibles(5); -- Retourne le nombre de places disponibles pour le covoiturage #5
 *
 * 
 * Fonction: est_chauffeur
 * ----------------------
 * Vérifie si un utilisateur est enregistré comme chauffeur (possède au moins une voiture).
 * 
 * Paramètres:
 *   p_utilisateur_id (INT) - L'identifiant de l'utilisateur à vérifier
 * 
 * Retourne:
 *   BOOLEAN - TRUE si l'utilisateur est chauffeur, FALSE sinon
 *
 * Exemple d'utilisation:
 *   SELECT est_chauffeur(3); -- Vérifie si l'utilisateur #3 est chauffeur
 * 
 * 
 * Fonction: note_moyenne_utilisateur
 * ---------------------------------
 * Calcule la note moyenne d'un utilisateur basée sur les avis reçus.
 * 
 * Paramètres:
 *   p_utilisateur_id (INT) - L'identifiant de l'utilisateur
 * 
 * Retourne:
 *   DECIMAL(3,1) - La note moyenne (0 si aucun avis)
 *
 * Exemple d'utilisation:
 *   SELECT note_moyenne_utilisateur(8); -- Retourne la note moyenne de l'utilisateur #8
 * 
 * 
 * Fonction: calculer_distance_km
 * ----------------------------
 * Calcule la distance en kilomètres entre deux points GPS en utilisant la formule de Haversine.
 * Cette fonction est très précise et optimisée pour les calculs géographiques.
 * 
 * Paramètres:
 *   lat1 (DECIMAL(10,8)) - Latitude du point de départ
 *   lon1 (DECIMAL(11,8)) - Longitude du point de départ
 *   lat2 (DECIMAL(10,8)) - Latitude du point d'arrivée
 *   lon2 (DECIMAL(11,8)) - Longitude du point d'arrivée
 * 
 * Retourne:
 *   DECIMAL(8,2) - La distance en kilomètres, arrondie à 2 décimales
 *
 * Exemple d'utilisation:
 *   SELECT calculer_distance_km(48.8566, 2.3522, 43.2965, 5.3698); -- Distance Paris-Marseille
 *
 *
 * Procédure: creer_participation
 * -----------------------------
 * Crée une participation à un covoiturage et effectue la transaction financière correspondante.
 * Utilise une transaction pour garantir l'intégrité des données.
 * 
 * Paramètres:
 *   p_utilisateur_id (INT) - L'identifiant de l'utilisateur
 *   p_covoiturage_id (INT) - L'identifiant du covoiturage
 *
 * Exemple d'utilisation:
 *   CALL creer_participation(12, 7); -- L'utilisateur #12 participe au covoiturage #7
 * 
 * 
 * Procédure: calculer_empreinte_economisee
 * ---------------------------------------
 * Calcule l'empreinte carbone totale économisée par un utilisateur grâce aux covoiturages.
 * 
 * Paramètres:
 *   p_utilisateur_id (INT) - L'identifiant de l'utilisateur
 *   p_total_economise (DECIMAL(10,2)) - OUT - L'empreinte totale économisée
 *
 * Exemple d'utilisation:
 *   CALL calculer_empreinte_economisee(5, @total);
 *   SELECT @total AS empreinte_economisee;
 * 
 * 
 * Procédure: annuler_participation
 * -------------------------------
 * Annule une participation à un covoiturage avec remboursement selon les délais.
 * Gère les politiques de remboursement (total, partiel ou nul) selon le délai avant départ.
 * 
 * Paramètres:
 *   p_utilisateur_id (INT) - L'identifiant de l'utilisateur
 *   p_covoiturage_id (INT) - L'identifiant du covoiturage
 *
 * Exemple d'utilisation:
 *   CALL annuler_participation(4, 9); -- Annule la participation de l'utilisateur #4 au covoiturage #9
 */ 