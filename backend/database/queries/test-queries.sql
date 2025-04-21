-- Requêtes de test pour le schéma EcoRide
-- Ces requêtes permettent de tester le bon fonctionnement du modèle de données

-- 1. Insertion de données de test
BEGIN;

-- Adresses
INSERT INTO Adresse (rue, ville, code_postal, pays, coordonnees_gps) VALUES
('12 rue de Paris', 'Paris', '75001', 'France', '48.8566,2.3522'),
('25 avenue des Champs-Élysées', 'Paris', '75008', 'France', '48.8738,2.2950'),
('5 place Bellecour', 'Lyon', '69002', 'France', '45.7578,4.8320');

-- Lieux
INSERT INTO Lieu (nom, adresse_id) VALUES
('Gare de Lyon', 1),
('Arc de Triomphe', 2),
('Place Bellecour', 3);

-- Marques
INSERT INTO Marque (libelle) VALUES
('Renault'),
('Peugeot'),
('Tesla');

-- Modèles
INSERT INTO Modele (nom, marque_id) VALUES
('Zoe', 1),
('208', 2),
('Model 3', 3);

-- Rôles déjà insérés dans le schéma principal

-- Utilisateurs
INSERT INTO Utilisateur (nom, prenom, email, mot_passe, telephone, adresse_id, date_naissance, pseudo) VALUES
('Dupont', 'Jean', 'jean.dupont@example.com', 'motdepasse123', '0612345678', 1, '1985-03-15', 'jeandupont'),
('Martin', 'Sophie', 'sophie.martin@example.com', 'motdepasse456', '0687654321', 2, '1990-07-22', 'sophiemartin'),
('Durand', 'Pierre', 'pierre.durand@example.com', 'motdepasse789', '0678912345', 3, '1978-11-05', 'pierredurand');

-- Attribution des rôles
INSERT INTO Possede (utilisateur_id, role_id) VALUES
(1, 3), -- Jean est chauffeur
(2, 2), -- Sophie est passager
(3, 2), -- Pierre est passager
(3, 3); -- Pierre est aussi chauffeur

-- Voitures
INSERT INTO Voiture (modele_id, immatriculation, energie_id, couleur, date_premiere_immat, utilisateur_id) VALUES
(1, 'AB-123-CD', 1, 'Bleu', '2020-01-15', 1), -- Renault Zoe électrique de Jean
(3, 'EF-456-GH', 1, 'Blanc', '2021-05-10', 3); -- Tesla Model 3 électrique de Pierre

-- Covoiturages
INSERT INTO Covoiturage (lieu_depart_id, lieu_arrivee_id, date_depart, heure_depart, date_arrivee, heure_arrivee, 
                          statut_id, nb_place, prix_personne, voiture_id, empreinte_carbone) VALUES
(1, 2, '2023-06-15', '08:00:00', '2023-06-15', '09:30:00', 1, 3, 15.50, 1, 2.5),
(2, 3, '2023-06-20', '14:00:00', '2023-06-20', '17:30:00', 1, 4, 25.00, 2, 5.2);

-- Participations
INSERT INTO Participation (utilisateur_id, covoiturage_id, date_reservation, statut_id) VALUES
(2, 1, '2023-06-10 15:30:00', 2), -- Sophie participe au covoiturage 1
(3, 1, '2023-06-11 10:15:00', 2); -- Pierre participe au covoiturage 1

-- Avis
INSERT INTO Avis (utilisateur_id, covoiturage_id, commentaire, note, statut_id) VALUES
(2, 1, 'Très bon trajet, chauffeur ponctuel et sympathique.', 5, 2); -- Avis de Sophie sur le covoiturage 1

-- CreditBalance
INSERT INTO CreditBalance (utilisateur_id, solde) VALUES
(1, 100.00),
(2, 50.00),
(3, 75.00);

-- CreditTransaction
INSERT INTO CreditTransaction (utilisateur_id, montant, type_id, description) VALUES
(1, 50.00, 1, 'Crédit initial'),
(1, 50.00, 5, 'Bonus de bienvenue'),
(2, 50.00, 1, 'Crédit initial'),
(2, -15.50, 2, 'Réservation covoiturage #1'),
(3, 75.00, 1, 'Crédit initial'),
(3, -15.50, 2, 'Réservation covoiturage #1');

-- Valider toutes les insertions de données
COMMIT;

-- 2. Requêtes de test

-- Recherche de covoiturages disponibles entre deux lieux à une date donnée
SELECT c.covoiturage_id, u.nom, u.prenom, ld.nom AS lieu_depart, la.nom AS lieu_arrivee, 
       c.date_depart, c.heure_depart, c.prix_personne, c.nb_place,
       (c.nb_place - COUNT(p.utilisateur_id)) AS places_restantes
FROM Covoiturage c
JOIN Lieu ld ON c.lieu_depart_id = ld.lieu_id
JOIN Lieu la ON c.lieu_arrivee_id = la.lieu_id
JOIN Voiture v ON c.voiture_id = v.voiture_id
JOIN Utilisateur u ON v.utilisateur_id = u.utilisateur_id
LEFT JOIN Participation p ON c.covoiturage_id = p.covoiturage_id AND p.statut_id = 2
WHERE c.date_depart = '2023-06-15'
  AND c.statut_id = 1
  AND c.lieu_depart_id = 1
GROUP BY c.covoiturage_id
HAVING places_restantes > 0;

-- Profil utilisateur avec ses préférences
SELECT u.utilisateur_id, u.nom, u.prenom, u.email, u.pseudo, u.telephone,
       a.rue, a.ville, a.code_postal, a.pays,
       r.libelle AS role,
       p.propriete, p.valeur
FROM Utilisateur u
JOIN Adresse a ON u.adresse_id = a.adresse_id
JOIN Possede po ON u.utilisateur_id = po.utilisateur_id
JOIN Role r ON po.role_id = r.role_id
LEFT JOIN Parametre p ON u.utilisateur_id = p.utilisateur_id
WHERE u.utilisateur_id = 1;

-- Historique des transactions d'un utilisateur
SELECT t.transaction_id, t.montant, t.date_transaction, 
       tt.libelle AS type_transaction, t.description,
       cb.solde AS solde_actuel
FROM CreditTransaction t
JOIN TypeTransaction tt ON t.type_id = tt.type_id
JOIN CreditBalance cb ON t.utilisateur_id = cb.utilisateur_id
WHERE t.utilisateur_id = 1
ORDER BY t.date_transaction DESC;

-- Trajets effectués par un utilisateur avec note moyenne
SELECT c.covoiturage_id, c.date_depart, c.heure_depart,
       ld.nom AS lieu_depart, la.nom AS lieu_arrivee,
       sc.libelle AS statut,
       AVG(a.note) AS note_moyenne,
       COUNT(DISTINCT p.utilisateur_id) AS nb_passagers
FROM Covoiturage c
JOIN Lieu ld ON c.lieu_depart_id = ld.lieu_id
JOIN Lieu la ON c.lieu_arrivee_id = la.lieu_id
JOIN StatutCovoiturage sc ON c.statut_id = sc.statut_id
JOIN Voiture v ON c.voiture_id = v.voiture_id
LEFT JOIN Participation p ON c.covoiturage_id = p.covoiturage_id AND p.statut_id = 2
LEFT JOIN Avis a ON c.covoiturage_id = a.covoiturage_id AND a.statut_id = 2
WHERE v.utilisateur_id = 1  -- Trajets de Jean en tant que chauffeur
GROUP BY c.covoiturage_id
ORDER BY c.date_depart DESC, c.heure_depart DESC;

-- Recherche des voitures électriques disponibles
SELECT v.voiture_id, m.nom AS modele, ma.libelle AS marque, 
       v.immatriculation, te.libelle AS energie,
       u.nom, u.prenom
FROM Voiture v
JOIN Modele m ON v.modele_id = m.modele_id
JOIN Marque ma ON m.marque_id = ma.marque_id
JOIN TypeEnergie te ON v.energie_id = te.energie_id
JOIN Utilisateur u ON v.utilisateur_id = u.utilisateur_id
WHERE te.libelle = 'Électrique';

-- Utilisateurs ayant plusieurs rôles
SELECT u.utilisateur_id, u.nom, u.prenom, u.email, 
       GROUP_CONCAT(r.libelle SEPARATOR ', ') AS roles
FROM Utilisateur u
JOIN Possede p ON u.utilisateur_id = p.utilisateur_id
JOIN Role r ON p.role_id = r.role_id
GROUP BY u.utilisateur_id
HAVING COUNT(r.role_id) > 1;

-- Calcul de l'empreinte carbone économisée
SELECT SUM(c.empreinte_carbone * COUNT(p.utilisateur_id)) AS total_empreinte_economisee
FROM Covoiturage c
JOIN Participation p ON c.covoiturage_id = p.covoiturage_id
WHERE p.statut_id = 2  -- Participations confirmées
  AND c.statut_id IN (1, 2);  -- Covoiturages planifiés ou en cours 