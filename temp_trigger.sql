USE ecoride;

DROP TRIGGER IF EXISTS after_utilisateur_insert;
CREATE TRIGGER after_utilisateur_insert AFTER INSERT ON Utilisateur 
FOR EACH ROW 
BEGIN 
    INSERT INTO CreditBalance (utilisateur_id, solde) VALUES (NEW.utilisateur_id, 0); 
END; 