-- Migration pour l'ajout d'index liés à la recherche de trajets
-- Ce script améliore les performances de l'API de recherche (/api/rides/search)

-- Index sur les colonnes fréquemment utilisées dans les filtres
-- Index sur la localité de départ et d'arrivée pour des recherches plus rapides
CREATE INDEX IF NOT EXISTS idx_lieu_nom ON Lieu(nom);

-- Index sur les dates et heures de départ (utilisées pour le filtrage et le tri)
CREATE INDEX IF NOT EXISTS idx_covoiturage_date_depart ON Covoiturage(date_depart);
CREATE INDEX IF NOT EXISTS idx_covoiturage_heure_depart ON Covoiturage(heure_depart);

-- Index sur le prix pour le filtrage par prix maximum et le tri par prix
CREATE INDEX IF NOT EXISTS idx_covoiturage_prix ON Covoiturage(prix_personne);

-- Index sur le statut des covoiturages (nous filtrons sur les trajets 'planifiés')
CREATE INDEX IF NOT EXISTS idx_covoiturage_statut ON Covoiturage(statut_id);

-- Index composé pour les trajets futurs (date + heure)
CREATE INDEX IF NOT EXISTS idx_covoiturage_depart_complet ON Covoiturage(date_depart, heure_depart);

-- Index sur les clés étrangères pour optimiser les jointures
CREATE INDEX IF NOT EXISTS idx_covoiturage_lieu_depart ON Covoiturage(lieu_depart_id);
CREATE INDEX IF NOT EXISTS idx_covoiturage_lieu_arrivee ON Covoiturage(lieu_arrivee_id);
CREATE INDEX IF NOT EXISTS idx_covoiturage_voiture ON Covoiturage(voiture_id);
CREATE INDEX IF NOT EXISTS idx_voiture_utilisateur ON Voiture(utilisateur_id);
CREATE INDEX IF NOT EXISTS idx_voiture_modele ON Voiture(modele_id);
CREATE INDEX IF NOT EXISTS idx_participation_covoiturage ON Participation(covoiturage_id);
CREATE INDEX IF NOT EXISTS idx_participation_statut ON Participation(statut_id);

-- Création d'une procédure stockée pour la recherche (optimisation)
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS search_rides(
    IN p_departure_location VARCHAR(255),
    IN p_arrival_location VARCHAR(255),
    IN p_date DATE,
    IN p_departure_time TIME,
    IN p_max_price DECIMAL(10,2),
    IN p_sort_by VARCHAR(20),
    IN p_limit INT,
    IN p_offset INT
)
BEGIN
    -- Variables pour stocker l'ID du statut 'confirmé' pour les participations
    -- et l'ID du statut 'planifié' pour les covoiturages
    DECLARE v_statut_confirme INT;
    DECLARE v_statut_planifie INT;
    
    -- Récupération des IDs de statut
    SELECT statut_id INTO v_statut_confirme FROM StatutParticipation WHERE libelle = 'confirmé' LIMIT 1;
    SELECT statut_id INTO v_statut_planifie FROM StatutCovoiturage WHERE libelle = 'planifié' LIMIT 1;
    
    -- Requête principale pour la recherche
    SELECT 
        c.covoiturage_id, 
        ld.nom AS lieu_depart, 
        la.nom AS lieu_arrivee,
        c.date_depart, 
        c.heure_depart,
        c.date_arrivee,
        c.heure_arrivee,
        c.nb_place,
        c.prix_personne,
        c.empreinte_carbone,
        u.utilisateur_id,
        u.pseudo,
        u.photo_path,
        m.nom AS modele,
        ma.libelle AS marque,
        te.libelle AS type_energie,
        IFNULL(AVG(a.note), 0) AS note_moyenne,
        (
            SELECT COUNT(*)
            FROM Participation p
            WHERE p.covoiturage_id = c.covoiturage_id
            AND p.statut_id = v_statut_confirme
        ) AS places_reservees,
        (c.nb_place - (
            SELECT COUNT(*)
            FROM Participation p
            WHERE p.covoiturage_id = c.covoiturage_id
            AND p.statut_id = v_statut_confirme
        )) AS places_disponibles
    FROM 
        Covoiturage c
    JOIN 
        Lieu ld ON c.lieu_depart_id = ld.lieu_id
    JOIN 
        Lieu la ON c.lieu_arrivee_id = la.lieu_id
    JOIN 
        Voiture v ON c.voiture_id = v.voiture_id
    JOIN 
        Utilisateur u ON v.utilisateur_id = u.utilisateur_id
    JOIN 
        Modele m ON v.modele_id = m.modele_id
    JOIN 
        Marque ma ON m.marque_id = ma.marque_id
    JOIN 
        TypeEnergie te ON v.energie_id = te.energie_id
    LEFT JOIN 
        Avis a ON a.covoiturage_id = c.covoiturage_id
    WHERE 
        c.statut_id = v_statut_planifie
    AND
        (c.date_depart > CURDATE() OR (c.date_depart = CURDATE() AND c.heure_depart > CURTIME()))
    AND
        (c.nb_place - (
            SELECT COUNT(*)
            FROM Participation p
            WHERE p.covoiturage_id = c.covoiturage_id
            AND p.statut_id = v_statut_confirme
        )) > 0
    AND
        (p_departure_location IS NULL OR LOWER(ld.nom) LIKE CONCAT('%', LOWER(p_departure_location), '%'))
    AND
        (p_arrival_location IS NULL OR LOWER(la.nom) LIKE CONCAT('%', LOWER(p_arrival_location), '%'))
    AND
        (p_date IS NULL OR c.date_depart = p_date)
    AND
        (p_departure_time IS NULL OR c.heure_depart >= p_departure_time)
    AND
        (p_max_price IS NULL OR c.prix_personne <= p_max_price)
    GROUP BY 
        c.covoiturage_id
    ORDER BY
        CASE 
            WHEN p_sort_by = 'price' THEN c.prix_personne
            ELSE NULL
        END ASC,
        CASE 
            WHEN p_sort_by = 'price' THEN c.date_depart
            ELSE c.date_depart
        END ASC,
        CASE 
            WHEN p_sort_by = 'price' THEN c.heure_depart
            ELSE c.heure_depart
        END ASC
    LIMIT p_limit
    OFFSET p_offset;
END //
DELIMITER ;

-- Note: Cette procédure stockée peut être utilisée pour remplacer la requête SQL
-- dans le RideService pour améliorer les performances. 