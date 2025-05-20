-- Script pour modifier la table Voiture et ajouter les champs nécessaires
ALTER TABLE Voiture 
ADD COLUMN marque VARCHAR(50) AFTER voiture_id,
ADD COLUMN modele VARCHAR(50) AFTER marque,
ADD COLUMN annee INT AFTER modele,
ADD COLUMN places INT DEFAULT 5 AFTER couleur;

-- Mettre à jour les contraintes
ALTER TABLE Voiture 
MODIFY COLUMN modele_id INT NULL,
MODIFY COLUMN energie_id INT NULL; 