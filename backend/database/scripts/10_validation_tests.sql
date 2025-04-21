-- ======================================================================
-- Script de validation pour EcoRide
-- Vérifications de la base de données et de la cohérence des données
-- ======================================================================

USE ecoride;

-- ======================================================================
-- VÉRIFICATION DES STRUCTURES
-- ======================================================================

-- Vérification de l'existence des tables
SELECT 'Vérification des tables' AS Etape;
SELECT 
    COUNT(*) AS nb_tables_attendues,
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'ecoride') AS nb_tables_trouvees,
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'ecoride') = 19 AS tables_ok
FROM DUAL;

-- Vérification des contraintes
SELECT 'Vérification des contraintes' AS Etape;
SELECT 
    COUNT(*) AS nb_contraintes,
    (SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_schema = 'ecoride' AND constraint_type = 'FOREIGN KEY') AS nb_foreign_keys,
    (SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_schema = 'ecoride' AND constraint_type = 'PRIMARY KEY') AS nb_primary_keys,
    (SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_schema = 'ecoride' AND constraint_type = 'UNIQUE') AS nb_unique
FROM information_schema.table_constraints 
WHERE table_schema = 'ecoride';

-- Vérification des index
SELECT 'Vérification des index' AS Etape;
SELECT 
    table_name, 
    COUNT(*) AS nb_index
FROM information_schema.statistics
WHERE table_schema = 'ecoride'
GROUP BY table_name;

-- Vérification des vues
SELECT 'Vérification des vues' AS Etape;
SELECT 
    table_name 
FROM information_schema.views
WHERE table_schema = 'ecoride';

-- Vérification des procédures et fonctions
SELECT 'Vérification des procédures et fonctions' AS Etape;
SELECT 
    routine_name, 
    routine_type,
    data_type AS return_type
FROM information_schema.routines
WHERE routine_schema = 'ecoride';

-- Vérification des triggers
SELECT 'Vérification des triggers' AS Etape;
SELECT 
    trigger_name, 
    event_manipulation, 
    event_object_table,
    action_timing
FROM information_schema.triggers
WHERE trigger_schema = 'ecoride';

-- ======================================================================
-- VÉRIFICATION DES DONNÉES
-- ======================================================================

-- Vérification des données de référence
SELECT 'Vérification des données de référence' AS Etape;
SELECT 'Roles' AS Table, COUNT(*) AS Count FROM Role UNION ALL
SELECT 'TypeEnergie', COUNT(*) FROM TypeEnergie UNION ALL
SELECT 'StatutCovoiturage', COUNT(*) FROM StatutCovoiturage UNION ALL
SELECT 'StatutParticipation', COUNT(*) FROM StatutParticipation UNION ALL
SELECT 'StatutAvis', COUNT(*) FROM StatutAvis UNION ALL
SELECT 'TypeTransaction', COUNT(*) FROM TypeTransaction;

-- Vérification de la cohérence des soldes des utilisateurs
SELECT 'Vérification de la cohérence des soldes' AS Etape;
SELECT 
    u.utilisateur_id, 
    u.nom, 
    u.prenom, 
    cb.solde AS solde_credit_balance,
    IFNULL(SUM(ct.montant), 0) AS solde_calcule,
    cb.solde = IFNULL(SUM(ct.montant), 0) AS solde_coherent
FROM 
    Utilisateur u
LEFT JOIN CreditBalance cb ON u.utilisateur_id = cb.utilisateur_id
LEFT JOIN CreditTransaction ct ON u.utilisateur_id = ct.utilisateur_id
GROUP BY u.utilisateur_id
HAVING solde_coherent = 0 OR solde_coherent IS NULL;

-- Vérification des participations (pas de surréservation)
SELECT 'Vérification des places disponibles' AS Etape;
SELECT 
    c.covoiturage_id,
    c.nb_place,
    COUNT(p.utilisateur_id) AS nb_participants,
    c.nb_place >= COUNT(p.utilisateur_id) AS places_ok
FROM 
    Covoiturage c
LEFT JOIN Participation p ON c.covoiturage_id = p.covoiturage_id AND p.statut_id = 2
GROUP BY c.covoiturage_id
HAVING places_ok = 0;

-- Vérification des contraintes d'intégrité référentielle
SELECT 'Vérification de l''intégrité référentielle' AS Etape;

-- Vérification des utilisateurs et CreditBalance
SELECT 
    'CreditBalance-Utilisateur' AS Test,
    COUNT(*) AS nb_problemes
FROM 
    Utilisateur u
LEFT JOIN CreditBalance cb ON u.utilisateur_id = cb.utilisateur_id
WHERE cb.utilisateur_id IS NULL
UNION ALL
-- Vérification des voitures et utilisateurs
SELECT 
    'Voiture-Utilisateur' AS Test,
    COUNT(*) AS nb_problemes
FROM 
    Voiture v
LEFT JOIN Utilisateur u ON v.utilisateur_id = u.utilisateur_id
WHERE u.utilisateur_id IS NULL
UNION ALL
-- Vérification des participations et covoiturages
SELECT 
    'Participation-Covoiturage' AS Test,
    COUNT(*) AS nb_problemes
FROM 
    Participation p
LEFT JOIN Covoiturage c ON p.covoiturage_id = c.covoiturage_id
WHERE c.covoiturage_id IS NULL
UNION ALL
-- Vérification des avis et covoiturages
SELECT 
    'Avis-Covoiturage' AS Test,
    COUNT(*) AS nb_problemes
FROM 
    Avis a
LEFT JOIN Covoiturage c ON a.covoiturage_id = c.covoiturage_id
WHERE c.covoiturage_id IS NULL;

-- ======================================================================
-- TESTS DE PERFORMANCE
-- ======================================================================

-- Mesure du temps de réponse sur les requêtes fréquentes
SELECT 'Tests de performance' AS Etape;

-- Test de recherche de covoiturages
SET @start = NOW(6);
SELECT COUNT(*) FROM vw_covoiturages_disponibles;
SELECT CONCAT('Temps requête covoiturages disponibles: ', (TIMESTAMPDIFF(MICROSECOND, @start, NOW(6)) / 1000), ' ms') AS Performance;

-- Test de recherche utilisateur
SET @start = NOW(6);
SELECT * FROM Utilisateur WHERE email LIKE 'j%';
SELECT CONCAT('Temps requête recherche utilisateur: ', (TIMESTAMPDIFF(MICROSECOND, @start, NOW(6)) / 1000), ' ms') AS Performance;

-- Test de statistiques
SET @start = NOW(6);
SELECT * FROM vw_admin_statistiques_covoiturages;
SELECT CONCAT('Temps requête statistiques: ', (TIMESTAMPDIFF(MICROSECOND, @start, NOW(6)) / 1000), ' ms') AS Performance;

-- ======================================================================
-- VALIDATION FINALE
-- ======================================================================

SELECT 'Validation finale' AS Etape;
SELECT
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'ecoride') = 19 AS structure_ok,
    (SELECT COUNT(*) FROM information_schema.views WHERE table_schema = 'ecoride') >= 7 AS vues_ok,
    (SELECT COUNT(*) FROM information_schema.routines WHERE routine_schema = 'ecoride') >= 5 AS procedures_ok,
    (SELECT COUNT(*) FROM information_schema.triggers WHERE trigger_schema = 'ecoride') = 4 AS triggers_ok,
    (SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_schema = 'ecoride' AND constraint_type = 'FOREIGN KEY') >= 15 AS contraintes_ok,
    (SELECT COUNT(*) FROM Role) = 4 AS donnees_ref_ok,
    (SELECT COUNT(*) FROM Utilisateur) > 0 AS donnees_test_ok,
    (SELECT COUNT(*) 
     FROM Utilisateur u 
     LEFT JOIN CreditBalance cb ON u.utilisateur_id = cb.utilisateur_id
     WHERE cb.utilisateur_id IS NULL) = 0 AS coherence_donnees_ok,
    'Base de données EcoRide vérifiée avec succès !' AS resultat
FROM DUAL; 