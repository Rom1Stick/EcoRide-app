-- ======================================================================
-- Script de création des vues simplifiées pour EcoRide
-- ======================================================================

USE ecoride;

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