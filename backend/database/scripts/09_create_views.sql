-- ======================================================================
-- Script de création des vues pour EcoRide
-- Vues pour simplifier les requêtes du front-end
-- ======================================================================

USE ecoride;

-- ======================================================================
-- VUES POUR L'AFFICHAGE DES COVOITURAGES
-- ======================================================================

-- Vue pour les covoiturages disponibles (avec détails)
CREATE OR REPLACE VIEW vw_covoiturages_disponibles AS
SELECT 
    c.covoiturage_id,
    c.date_depart,
    c.heure_depart,
    c.date_arrivee,
    c.heure_arrivee,
    c.prix_personne,
    c.nb_place,
    c.empreinte_carbone,
    ld.nom AS lieu_depart,
    ld.adresse_id AS lieu_depart_adresse_id,
    la.nom AS lieu_arrivee,
    la.adresse_id AS lieu_arrivee_adresse_id,
    ad.ville AS ville_depart,
    ad.code_postal AS cp_depart,
    ad.coordonnees_gps AS gps_depart,
    aa.ville AS ville_arrivee,
    aa.code_postal AS cp_arrivee,
    aa.coordonnees_gps AS gps_arrivee,
    u.utilisateur_id AS chauffeur_id,
    u.nom AS chauffeur_nom,
    u.prenom AS chauffeur_prenom,
    u.photo_path AS chauffeur_photo,
    v.voiture_id,
    m.nom AS modele_voiture,
    ma.libelle AS marque_voiture,
    e.libelle AS energie_voiture,
    v.couleur AS couleur_voiture,
    (c.nb_place - COUNT(p.utilisateur_id)) AS places_restantes,
    (SELECT AVG(note) FROM Avis WHERE utilisateur_id = u.utilisateur_id AND statut_id = 2) AS note_moyenne_chauffeur,
    sc.libelle AS statut
FROM 
    Covoiturage c
JOIN Lieu ld ON c.lieu_depart_id = ld.lieu_id
JOIN Lieu la ON c.lieu_arrivee_id = la.lieu_id
JOIN Adresse ad ON ld.adresse_id = ad.adresse_id
JOIN Adresse aa ON la.adresse_id = aa.adresse_id
JOIN Voiture v ON c.voiture_id = v.voiture_id
JOIN Utilisateur u ON v.utilisateur_id = u.utilisateur_id
JOIN Modele m ON v.modele_id = m.modele_id
JOIN Marque ma ON m.marque_id = ma.marque_id
JOIN TypeEnergie e ON v.energie_id = e.energie_id
JOIN StatutCovoiturage sc ON c.statut_id = sc.statut_id
LEFT JOIN Participation p ON c.covoiturage_id = p.covoiturage_id AND p.statut_id = 2
WHERE 
    c.statut_id = 1 -- Planifié
    AND (c.date_depart > CURRENT_DATE() OR (c.date_depart = CURRENT_DATE() AND c.heure_depart > CURRENT_TIME()))
GROUP BY 
    c.covoiturage_id
HAVING 
    places_restantes > 0;

-- Vue simplifiée pour la recherche rapide de covoiturages
CREATE OR REPLACE VIEW vw_recherche_covoiturages AS
SELECT 
    c.covoiturage_id,
    ld.nom AS lieu_depart,
    ad.ville AS ville_depart,
    ad.code_postal AS cp_depart,
    ad.coordonnees_gps AS gps_depart,
    la.nom AS lieu_arrivee,
    aa.ville AS ville_arrivee,
    aa.code_postal AS cp_arrivee,
    aa.coordonnees_gps AS gps_arrivee,
    c.date_depart,
    c.heure_depart,
    c.prix_personne,
    (c.nb_place - COUNT(p.utilisateur_id)) AS places_restantes,
    e.libelle AS type_energie
FROM 
    Covoiturage c
JOIN Lieu ld ON c.lieu_depart_id = ld.lieu_id
JOIN Lieu la ON c.lieu_arrivee_id = la.lieu_id
JOIN Adresse ad ON ld.adresse_id = ad.adresse_id
JOIN Adresse aa ON la.adresse_id = aa.adresse_id
JOIN Voiture v ON c.voiture_id = v.voiture_id
JOIN TypeEnergie e ON v.energie_id = e.energie_id
LEFT JOIN Participation p ON c.covoiturage_id = p.covoiturage_id AND p.statut_id = 2
WHERE 
    c.statut_id = 1
    AND (c.date_depart > CURRENT_DATE() OR (c.date_depart = CURRENT_DATE() AND c.heure_depart > CURRENT_TIME()))
GROUP BY 
    c.covoiturage_id
HAVING 
    places_restantes > 0;

-- ======================================================================
-- VUES POUR LE PROFIL UTILISATEUR
-- ======================================================================

-- Vue pour les covoiturages d'un utilisateur en tant que chauffeur
CREATE OR REPLACE VIEW vw_mes_covoiturages_chauffeur AS
SELECT 
    c.covoiturage_id,
    c.date_depart,
    c.heure_depart,
    ld.nom AS lieu_depart,
    la.nom AS lieu_arrivee,
    c.prix_personne,
    c.nb_place,
    COUNT(p.utilisateur_id) AS nb_passagers,
    sc.libelle AS statut,
    v.voiture_id,
    CONCAT(m.nom, ' ', ma.libelle) AS voiture,
    c.empreinte_carbone
FROM 
    Covoiturage c
JOIN Lieu ld ON c.lieu_depart_id = ld.lieu_id
JOIN Lieu la ON c.lieu_arrivee_id = la.lieu_id
JOIN Voiture v ON c.voiture_id = v.voiture_id
JOIN Modele m ON v.modele_id = m.modele_id
JOIN Marque ma ON m.marque_id = ma.marque_id
JOIN StatutCovoiturage sc ON c.statut_id = sc.statut_id
LEFT JOIN Participation p ON c.covoiturage_id = p.covoiturage_id AND p.statut_id = 2
WHERE 
    v.utilisateur_id = @utilisateur_id  -- Paramètre à remplacer en utilisation
GROUP BY 
    c.covoiturage_id
ORDER BY 
    c.date_depart DESC, c.heure_depart;

-- Vue pour les participations d'un utilisateur
CREATE OR REPLACE VIEW vw_mes_participations AS
SELECT 
    p.covoiturage_id,
    p.utilisateur_id,
    c.date_depart,
    c.heure_depart,
    ld.nom AS lieu_depart,
    la.nom AS lieu_arrivee,
    c.prix_personne,
    u.nom AS chauffeur_nom,
    u.prenom AS chauffeur_prenom,
    p.date_reservation,
    sp.libelle AS statut_participation,
    sc.libelle AS statut_covoiturage,
    (SELECT COUNT(*) FROM Avis a WHERE a.utilisateur_id = p.utilisateur_id AND a.covoiturage_id = p.covoiturage_id) > 0 AS avis_donne
FROM 
    Participation p
JOIN Covoiturage c ON p.covoiturage_id = c.covoiturage_id
JOIN Lieu ld ON c.lieu_depart_id = ld.lieu_id
JOIN Lieu la ON c.lieu_arrivee_id = la.lieu_id
JOIN Voiture v ON c.voiture_id = v.voiture_id
JOIN Utilisateur u ON v.utilisateur_id = u.utilisateur_id
JOIN StatutParticipation sp ON p.statut_id = sp.statut_id
JOIN StatutCovoiturage sc ON c.statut_id = sc.statut_id
WHERE 
    p.utilisateur_id = @utilisateur_id  -- Paramètre à remplacer en utilisation
ORDER BY 
    c.date_depart DESC, c.heure_depart;

-- Vue pour l'historique des crédits
CREATE OR REPLACE VIEW vw_historique_credits AS
SELECT 
    t.transaction_id,
    t.utilisateur_id,
    t.montant,
    t.date_transaction,
    t.description,
    tt.libelle AS type_transaction,
    (SELECT SUM(t2.montant) 
     FROM CreditTransaction t2 
     WHERE t2.utilisateur_id = t.utilisateur_id AND t2.date_transaction <= t.date_transaction) AS solde_apres
FROM 
    CreditTransaction t
JOIN TypeTransaction tt ON t.type_id = tt.type_id
ORDER BY 
    t.date_transaction DESC;

-- ======================================================================
-- VUES POUR L'ADMINISTRATION
-- ======================================================================

-- Vue sur la synthèse des utilisateurs
CREATE OR REPLACE VIEW vw_admin_utilisateurs AS
SELECT 
    u.utilisateur_id,
    u.nom,
    u.prenom,
    u.email,
    u.pseudo,
    u.date_creation,
    u.derniere_connexion,
    GROUP_CONCAT(DISTINCT r.libelle SEPARATOR ', ') AS roles,
    (SELECT COUNT(*) FROM Voiture v WHERE v.utilisateur_id = u.utilisateur_id) AS nb_voitures,
    (SELECT COUNT(*) FROM Covoiturage c JOIN Voiture v ON c.voiture_id = v.voiture_id WHERE v.utilisateur_id = u.utilisateur_id) AS nb_covoiturages_crees,
    (SELECT COUNT(*) FROM Participation p WHERE p.utilisateur_id = u.utilisateur_id) AS nb_participations,
    cb.solde AS credit_balance
FROM 
    Utilisateur u
LEFT JOIN Possede po ON u.utilisateur_id = po.utilisateur_id
LEFT JOIN Role r ON po.role_id = r.role_id
LEFT JOIN CreditBalance cb ON u.utilisateur_id = cb.utilisateur_id
GROUP BY 
    u.utilisateur_id;

-- Vue sur la performance des covoiturages
CREATE OR REPLACE VIEW vw_admin_statistiques_covoiturages AS
SELECT 
    c.statut_id,
    sc.libelle AS statut,
    COUNT(c.covoiturage_id) AS nombre_covoiturages,
    AVG(c.nb_place) AS moyenne_places_proposees,
    SUM(c.nb_place) AS total_places_proposees,
    COUNT(p.utilisateur_id) AS total_reservations,
    SUM(c.nb_place) - COUNT(p.utilisateur_id) AS total_places_inoccupees,
    CASE WHEN SUM(c.nb_place) > 0 
        THEN ROUND(COUNT(p.utilisateur_id) / SUM(c.nb_place) * 100, 2) 
        ELSE 0 
    END AS taux_remplissage,
    AVG(c.prix_personne) AS prix_moyen,
    SUM(c.prix_personne * (SELECT COUNT(*) FROM Participation WHERE covoiturage_id = c.covoiturage_id AND statut_id = 2)) AS chiffre_affaires,
    SUM(c.empreinte_carbone * (SELECT COUNT(*) FROM Participation WHERE covoiturage_id = c.covoiturage_id AND statut_id = 2)) AS total_empreinte_economisee
FROM 
    Covoiturage c
JOIN StatutCovoiturage sc ON c.statut_id = sc.statut_id
LEFT JOIN Participation p ON c.covoiturage_id = p.covoiturage_id AND p.statut_id = 2
GROUP BY 
    c.statut_id, sc.libelle;

-- Vue sur les trajets populaires
CREATE OR REPLACE VIEW vw_trajets_populaires AS
SELECT 
    CONCAT(ad.ville, ' - ', aa.ville) AS trajet,
    COUNT(DISTINCT c.covoiturage_id) AS nb_covoiturages,
    COUNT(p.utilisateur_id) AS nb_participations,
    AVG(c.prix_personne) AS prix_moyen,
    AVG(c.empreinte_carbone) AS empreinte_moyenne
FROM 
    Covoiturage c
JOIN Lieu ld ON c.lieu_depart_id = ld.lieu_id
JOIN Lieu la ON c.lieu_arrivee_id = la.lieu_id
JOIN Adresse ad ON ld.adresse_id = ad.adresse_id
JOIN Adresse aa ON la.adresse_id = aa.adresse_id
LEFT JOIN Participation p ON c.covoiturage_id = p.covoiturage_id AND p.statut_id = 2
GROUP BY 
    trajet
HAVING 
    nb_covoiturages > 1
ORDER BY 
    nb_participations DESC;

-- Vue des avis récents
CREATE OR REPLACE VIEW vw_avis_recents AS
SELECT 
    a.avis_id,
    a.covoiturage_id,
    a.utilisateur_id,
    u.pseudo AS auteur,
    CONCAT(ld.nom, ' → ', la.nom) AS trajet,
    c.date_depart,
    a.commentaire,
    a.note,
    sa.libelle AS statut,
    a.date_creation
FROM 
    Avis a
JOIN Utilisateur u ON a.utilisateur_id = u.utilisateur_id
JOIN Covoiturage c ON a.covoiturage_id = c.covoiturage_id
JOIN Lieu ld ON c.lieu_depart_id = ld.lieu_id
JOIN Lieu la ON c.lieu_arrivee_id = la.lieu_id
JOIN StatutAvis sa ON a.statut_id = sa.statut_id
ORDER BY 
    a.date_creation DESC; 