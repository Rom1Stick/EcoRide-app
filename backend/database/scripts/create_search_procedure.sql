DELIMITER //

CREATE PROCEDURE IF NOT EXISTS search_rides(
    IN p_lieu_depart VARCHAR(100),
    IN p_lieu_arrivee VARCHAR(100),
    IN p_date DATE,
    IN p_heure_depart TIME,
    IN p_prix_max DECIMAL(6,2),
    IN p_sort_by VARCHAR(20),
    IN p_page INT,
    IN p_limit INT
)
BEGIN
    DECLARE v_offset INT;
    DECLARE v_total INT;
    DECLARE v_total_pages INT;
    
    -- Calcul de l'offset pour la pagination
    SET v_offset = (p_page - 1) * p_limit;
    
    -- Construction de la requête de base pour le comptage
    SET @count_sql = CONCAT('
        SELECT COUNT(*) INTO @total_count
        FROM Covoiturage c
        JOIN Lieu ld ON c.lieu_depart_id = ld.lieu_id
        JOIN Lieu la ON c.lieu_arrivee_id = la.lieu_id
        JOIN Voiture v ON c.voiture_id = v.voiture_id
        JOIN Utilisateur u ON v.utilisateur_id = u.utilisateur_id
        WHERE c.statut_id = (SELECT statut_id FROM StatutCovoiturage WHERE libelle = "planifié")
        AND (c.date_depart > CURDATE() OR (c.date_depart = CURDATE() AND c.heure_depart > CURTIME()))
        AND (c.nb_place - (
            SELECT COUNT(*)
            FROM Participation p
            WHERE p.covoiturage_id = c.covoiturage_id
            AND p.statut_id = (SELECT statut_id FROM StatutParticipation WHERE libelle = "confirmé")
        )) > 0'
    );
    
    -- Ajout des filtres conditionnels
    IF p_lieu_depart IS NOT NULL THEN
        SET @count_sql = CONCAT(@count_sql, ' AND LOWER(ld.nom) LIKE LOWER("%', p_lieu_depart, '%")');
    END IF;
    
    IF p_lieu_arrivee IS NOT NULL THEN
        SET @count_sql = CONCAT(@count_sql, ' AND LOWER(la.nom) LIKE LOWER("%', p_lieu_arrivee, '%")');
    END IF;
    
    IF p_date IS NOT NULL THEN
        SET @count_sql = CONCAT(@count_sql, ' AND c.date_depart = "', p_date, '"');
    END IF;
    
    IF p_heure_depart IS NOT NULL THEN
        SET @count_sql = CONCAT(@count_sql, ' AND c.heure_depart >= "', p_heure_depart, '"');
    END IF;
    
    IF p_prix_max IS NOT NULL THEN
        SET @count_sql = CONCAT(@count_sql, ' AND c.prix_personne <= ', p_prix_max);
    END IF;
    
    -- Exécution de la requête de comptage
    PREPARE count_stmt FROM @count_sql;
    EXECUTE count_stmt;
    DEALLOCATE PREPARE count_stmt;
    
    -- Récupération du total de résultats
    SET v_total = @total_count;
    SET v_total_pages = CEILING(v_total / p_limit);
    
    -- Construction de la requête principale
    SET @main_sql = CONCAT('
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
                AND p.statut_id = (SELECT statut_id FROM StatutParticipation WHERE libelle = "confirmé")
            ) AS places_reservees,
            (c.nb_place - (
                SELECT COUNT(*)
                FROM Participation p
                WHERE p.covoiturage_id = c.covoiturage_id
                AND p.statut_id = (SELECT statut_id FROM StatutParticipation WHERE libelle = "confirmé")
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
            c.statut_id = (SELECT statut_id FROM StatutCovoiturage WHERE libelle = "planifié")
        AND
            (c.date_depart > CURDATE() OR (c.date_depart = CURDATE() AND c.heure_depart > CURTIME()))
        AND
            (c.nb_place - (
                SELECT COUNT(*)
                FROM Participation p
                WHERE p.covoiturage_id = c.covoiturage_id
                AND p.statut_id = (SELECT statut_id FROM StatutParticipation WHERE libelle = "confirmé")
            )) > 0'
    );
    
    -- Ajout des filtres conditionnels
    IF p_lieu_depart IS NOT NULL THEN
        SET @main_sql = CONCAT(@main_sql, ' AND LOWER(ld.nom) LIKE LOWER("%', p_lieu_depart, '%")');
    END IF;
    
    IF p_lieu_arrivee IS NOT NULL THEN
        SET @main_sql = CONCAT(@main_sql, ' AND LOWER(la.nom) LIKE LOWER("%', p_lieu_arrivee, '%")');
    END IF;
    
    IF p_date IS NOT NULL THEN
        SET @main_sql = CONCAT(@main_sql, ' AND c.date_depart = "', p_date, '"');
    END IF;
    
    IF p_heure_depart IS NOT NULL THEN
        SET @main_sql = CONCAT(@main_sql, ' AND c.heure_depart >= "', p_heure_depart, '"');
    END IF;
    
    IF p_prix_max IS NOT NULL THEN
        SET @main_sql = CONCAT(@main_sql, ' AND c.prix_personne <= ', p_prix_max);
    END IF;
    
    -- Ajout du groupe by
    SET @main_sql = CONCAT(@main_sql, ' GROUP BY c.covoiturage_id');
    
    -- Ajout du tri
    IF p_sort_by = 'price' THEN
        SET @main_sql = CONCAT(@main_sql, ' ORDER BY c.prix_personne ASC, c.date_depart ASC, c.heure_depart ASC');
    ELSE
        SET @main_sql = CONCAT(@main_sql, ' ORDER BY c.date_depart ASC, c.heure_depart ASC');
    END IF;
    
    -- Ajout de la pagination
    SET @main_sql = CONCAT(@main_sql, ' LIMIT ', p_limit, ' OFFSET ', v_offset);
    
    -- Exécution de la requête principale
    PREPARE main_stmt FROM @main_sql;
    EXECUTE main_stmt;
    DEALLOCATE PREPARE main_stmt;
    
    -- Retour des informations de pagination
    SELECT v_total AS total_results, p_page AS current_page, p_limit AS results_per_page, v_total_pages AS total_pages;
END //

DELIMITER ; 