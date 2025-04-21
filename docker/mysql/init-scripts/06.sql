-- ======================================================================
-- Script d'insertion des données de test pour EcoRide
-- À utiliser uniquement en environnement de développement
-- ======================================================================

USE ecoride;

-- Transaction pour assurer l'intégrité des insertions
BEGIN;

-- Adresses
INSERT INTO Adresse (rue, ville, code_postal, pays, coordonnees_gps) VALUES
('12 rue de Paris', 'Paris', '75001', 'France', '48.8566,2.3522'),
('25 avenue des Champs-Élysées', 'Paris', '75008', 'France', '48.8738,2.2950'),
('5 place Bellecour', 'Lyon', '69002', 'France', '45.7578,4.8320'),
('8 rue Garibaldi', 'Lyon', '69007', 'France', '45.7485,4.8427'),
('15 rue du Faubourg Saint-Antoine', 'Paris', '75011', 'France', '48.8513,2.3736');

-- Lieux
INSERT INTO Lieu (nom, adresse_id) VALUES
('Gare de Lyon', 1),
('Arc de Triomphe', 2),
('Place Bellecour', 3),
('Part-Dieu', 4),
('Bastille', 5);

-- Marques
INSERT INTO Marque (libelle) VALUES
('Renault'),
('Peugeot'),
('Tesla'),
('Toyota'),
('Volkswagen');

-- Modèles
INSERT INTO Modele (nom, marque_id) VALUES
('Zoe', 1),
('208', 2),
('Model 3', 3),
('Prius', 4),
('ID.3', 5);

-- Utilisateurs
INSERT INTO Utilisateur (nom, prenom, email, mot_passe, telephone, adresse_id, date_naissance, pseudo) VALUES
('Dupont', 'Jean', 'jean.dupont@example.com', 'motdepasse123', '0612345678', 1, '1985-03-15', 'jeandupont'),
('Martin', 'Sophie', 'sophie.martin@example.com', 'motdepasse456', '0687654321', 2, '1990-07-22', 'sophiemartin'),
('Durand', 'Pierre', 'pierre.durand@example.com', 'motdepasse789', '0678912345', 3, '1978-11-05', 'pierredurand'),
('Lefebvre', 'Marie', 'marie.lefebvre@example.com', 'motdepasse321', '0654321987', 4, '1992-04-18', 'marielefebvre'),
('Moreau', 'Thomas', 'thomas.moreau@example.com', 'motdepasse654', '0687123456', 5, '1983-09-30', 'thomasmoreau');

-- Attribution des rôles
INSERT INTO Possede (utilisateur_id, role_id) VALUES
(1, 3), -- Jean est chauffeur
(2, 2), -- Sophie est passager
(3, 2), -- Pierre est passager
(3, 3), -- Pierre est aussi chauffeur
(4, 2), -- Marie est passager
(4, 3), -- Marie est aussi chauffeur
(5, 4); -- Thomas est admin

-- Voitures
INSERT INTO Voiture (modele_id, immatriculation, energie_id, couleur, date_premiere_immat, utilisateur_id) VALUES
(1, 'AB-123-CD', 1, 'Bleu', '2020-01-15', 1),   -- Renault Zoe électrique de Jean
(3, 'EF-456-GH', 1, 'Blanc', '2021-05-10', 3),  -- Tesla Model 3 électrique de Pierre
(4, 'IJ-789-KL', 4, 'Gris', '2019-08-20', 4),   -- Toyota Prius hybride de Marie
(2, 'MN-012-OP', 2, 'Rouge', '2022-02-28', 1),  -- Peugeot 208 essence de Jean
(5, 'QR-345-ST', 1, 'Noir', '2022-11-05', 3);   -- Volkswagen ID.3 électrique de Pierre

-- Paramètres utilisateur
INSERT INTO Parametre (utilisateur_id, propriete, valeur) VALUES
(1, 'notification_email', 'true'),
(1, 'theme', 'sombre'),
(2, 'notification_email', 'false'),
(2, 'langue', 'fr'),
(3, 'notification_sms', 'true');

-- Covoiturages
INSERT INTO Covoiturage (lieu_depart_id, lieu_arrivee_id, date_depart, heure_depart, date_arrivee, heure_arrivee, 
                          statut_id, nb_place, prix_personne, voiture_id, empreinte_carbone) VALUES
(1, 2, DATE_ADD(CURRENT_DATE(), INTERVAL 1 DAY), '08:00:00', DATE_ADD(CURRENT_DATE(), INTERVAL 1 DAY), '09:30:00', 1, 3, 15.50, 1, 2.5),
(2, 3, DATE_ADD(CURRENT_DATE(), INTERVAL 2 DAY), '14:00:00', DATE_ADD(CURRENT_DATE(), INTERVAL 2 DAY), '17:30:00', 1, 4, 25.00, 2, 5.2),
(3, 4, DATE_ADD(CURRENT_DATE(), INTERVAL 1 DAY), '10:00:00', DATE_ADD(CURRENT_DATE(), INTERVAL 1 DAY), '10:30:00', 1, 2, 5.00, 3, 1.2),
(5, 1, DATE_ADD(CURRENT_DATE(), INTERVAL 3 DAY), '18:00:00', DATE_ADD(CURRENT_DATE(), INTERVAL 3 DAY), '19:00:00', 1, 3, 12.00, 4, 3.1),
(4, 2, DATE_ADD(CURRENT_DATE(), INTERVAL 4 DAY), '07:30:00', DATE_ADD(CURRENT_DATE(), INTERVAL 4 DAY), '12:00:00', 1, 4, 32.50, 5, 8.7);

-- Participations
INSERT INTO Participation (utilisateur_id, covoiturage_id, date_reservation, statut_id) VALUES
(2, 1, DATE_SUB(NOW(), INTERVAL 2 DAY), 2), -- Sophie participe au covoiturage 1
(3, 1, DATE_SUB(NOW(), INTERVAL 1 DAY), 2), -- Pierre participe au covoiturage 1
(2, 3, DATE_SUB(NOW(), INTERVAL 3 DAY), 2), -- Sophie participe au covoiturage 3
(4, 2, DATE_SUB(NOW(), INTERVAL 2 DAY), 2), -- Marie participe au covoiturage 2
(3, 5, DATE_SUB(NOW(), INTERVAL 1 DAY), 1); -- Pierre a demandé à participer au covoiturage 5 (en attente)

-- Avis
INSERT INTO Avis (utilisateur_id, covoiturage_id, commentaire, note, statut_id) VALUES
(2, 1, 'Très bon trajet, chauffeur ponctuel et sympathique.', 5, 2), -- Avis de Sophie sur le covoiturage 1
(3, 1, 'Véhicule propre et agréable, bonne ambiance.', 4, 2),      -- Avis de Pierre sur le covoiturage 1
(4, 2, 'Conduite prudente, mais un peu de retard au départ.', 3, 2), -- Avis de Marie sur le covoiturage 2
(2, 3, 'Parfait, rapide et efficace !', 5, 2);                     -- Avis de Sophie sur le covoiturage 3

-- CreditBalance (soldes déjà initialisés par le trigger)
UPDATE CreditBalance SET solde = 100.00 WHERE utilisateur_id = 1;
UPDATE CreditBalance SET solde = 50.00 WHERE utilisateur_id = 2;
UPDATE CreditBalance SET solde = 75.00 WHERE utilisateur_id = 3;
UPDATE CreditBalance SET solde = 125.00 WHERE utilisateur_id = 4;
UPDATE CreditBalance SET solde = 200.00 WHERE utilisateur_id = 5;

-- CreditTransaction
INSERT INTO CreditTransaction (utilisateur_id, montant, type_id, description) VALUES
(1, 50.00, 1, 'Crédit initial'),
(1, 50.00, 5, 'Bonus de bienvenue'),
(2, 50.00, 1, 'Crédit initial'),
(2, -15.50, 2, 'Réservation covoiturage #1'),
(2, -5.00, 2, 'Réservation covoiturage #3'),
(3, 75.00, 1, 'Crédit initial'),
(3, -15.50, 2, 'Réservation covoiturage #1'),
(4, 125.00, 1, 'Crédit initial'),
(4, -25.00, 2, 'Réservation covoiturage #2'),
(5, 200.00, 1, 'Crédit initial administrateur');

-- Valider toutes les insertions de données
COMMIT;

-- Vérification des données insérées
SELECT 'Données de test insérées avec succès' AS Message;
SELECT COUNT(*) AS nb_utilisateurs FROM Utilisateur;
SELECT COUNT(*) AS nb_covoiturages FROM Covoiturage;
SELECT COUNT(*) AS nb_participations FROM Participation; 