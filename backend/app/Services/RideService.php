<?php

namespace App\Services;

use App\Core\Database\DatabaseInterface;
use App\Core\Logger;
use PDOException;

/**
 * Service de gestion des trajets
 */
class RideService
{
    /**
     * @var DatabaseInterface Instance de la base de données
     */
    private $db;

    /**
     * @var Logger Instance du logger
     */
    private $logger;

    /**
     * Constructeur du service
     *
     * @param DatabaseInterface $database Instance de la base de données
     * @param Logger $logger Instance du logger
     */
    public function __construct(DatabaseInterface $database, Logger $logger)
    {
        $this->db = $database;
        $this->logger = $logger;
    }

    /**
     * Recherche de trajets selon les critères spécifiés
     *
     * @param array $criteria Critères de recherche (departureLocation, arrivalLocation, date, departureTime, maxPrice)
     * @param string $sortBy Critère de tri (departureTime, price)
     * @param int $page Numéro de page pour la pagination
     * @param int $limit Nombre d'éléments par page
     * @return array Résultats de la recherche avec pagination
     */
    public function searchRides(array $criteria, string $sortBy = 'departureTime', int $page = 1, int $limit = 10): array
    {
        try {
            // Calcul de l'offset pour la pagination
            $offset = ($page - 1) * $limit;
            
            // Construction de la requête de base
            $sql = "
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
                        AND p.statut_id = (SELECT statut_id FROM StatutParticipation WHERE libelle = 'confirmé')
                    ) AS places_reservees,
                    (c.nb_place - (
                        SELECT COUNT(*)
                        FROM Participation p
                        WHERE p.covoiturage_id = c.covoiturage_id
                        AND p.statut_id = (SELECT statut_id FROM StatutParticipation WHERE libelle = 'confirmé')
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
                    c.statut_id = (SELECT statut_id FROM StatutCovoiturage WHERE libelle = 'planifié')
                AND
                    (c.date_depart > CURDATE() OR (c.date_depart = CURDATE() AND c.heure_depart > CURTIME()))
                AND
                    (c.nb_place - (
                        SELECT COUNT(*)
                        FROM Participation p
                        WHERE p.covoiturage_id = c.covoiturage_id
                        AND p.statut_id = (SELECT statut_id FROM StatutParticipation WHERE libelle = 'confirmé')
                    )) > 0
            ";
            
            // Préparation des paramètres
            $params = [];
            
            // Filtrage par lieu de départ
            if (isset($criteria['departureLocation']) && !empty($criteria['departureLocation'])) {
                $sql .= " AND LOWER(ld.nom) LIKE LOWER(:departureLocation)";
                $params[':departureLocation'] = '%' . $criteria['departureLocation'] . '%';
            }
            
            // Filtrage par lieu d'arrivée
            if (isset($criteria['arrivalLocation']) && !empty($criteria['arrivalLocation'])) {
                $sql .= " AND LOWER(la.nom) LIKE LOWER(:arrivalLocation)";
                $params[':arrivalLocation'] = '%' . $criteria['arrivalLocation'] . '%';
            }
            
            // Filtrage par date
            if (isset($criteria['date']) && !empty($criteria['date'])) {
                $sql .= " AND c.date_depart = :date";
                $params[':date'] = $criteria['date'];
            }
            
            // Filtrage par heure de départ
            if (isset($criteria['departureTime']) && !empty($criteria['departureTime'])) {
                $sql .= " AND c.heure_depart >= :departureTime";
                $params[':departureTime'] = $criteria['departureTime'];
            }
            
            // Filtrage par prix maximum
            if (isset($criteria['maxPrice']) && !empty($criteria['maxPrice'])) {
                $sql .= " AND c.prix_personne <= :maxPrice";
                $params[':maxPrice'] = $criteria['maxPrice'];
            }
            
            // Groupement pour l'agrégation des notes
            $sql .= " GROUP BY c.covoiturage_id";
            
            // Tri des résultats
            $sql .= match ($sortBy) {
                'price' => " ORDER BY c.prix_personne ASC, c.date_depart ASC, c.heure_depart ASC",
                default => " ORDER BY c.date_depart ASC, c.heure_depart ASC"
            };
            
            // Requête pour compter le nombre total de résultats (pour la pagination)
            $countSql = "SELECT COUNT(*) as total FROM ($sql) as results";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $totalResults = $countStmt->fetch()['total'] ?? 0;
            
            // Ajout de la pagination à la requête principale
            $sql .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
            
            // Exécution de la requête principale
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $rides = $stmt->fetchAll();
            
            // Calcul du nombre total de pages
            $totalPages = ceil($totalResults / $limit);
            
            // Formatage des résultats pour le front-end
            $formattedRides = [];
            foreach ($rides as $ride) {
                $formattedRides[] = [
                    'id' => $ride['covoiturage_id'],
                    'departure' => [
                        'location' => $ride['lieu_depart'],
                        'date' => $ride['date_depart'],
                        'time' => $ride['heure_depart']
                    ],
                    'arrival' => [
                        'location' => $ride['lieu_arrivee'],
                        'date' => $ride['date_arrivee'],
                        'time' => $ride['heure_arrivee']
                    ],
                    'price' => (float) $ride['prix_personne'],
                    'seats' => [
                        'total' => (int) $ride['nb_place'],
                        'available' => (int) $ride['places_disponibles']
                    ],
                    'driver' => [
                        'id' => $ride['utilisateur_id'],
                        'username' => $ride['pseudo'],
                        'profilePicture' => $ride['photo_path'],
                        'rating' => (float) $ride['note_moyenne']
                    ],
                    'vehicle' => [
                        'model' => $ride['modele'],
                        'brand' => $ride['marque'],
                        'energy' => $ride['type_energie']
                    ],
                    'ecologicalImpact' => [
                        'carbonFootprint' => (float) $ride['empreinte_carbone']
                    ]
                ];
            }
            
            // Retour des résultats avec pagination
            return [
                'rides' => $formattedRides,
                'total' => $totalResults,
                'page' => $page,
                'limit' => $limit,
                'pages' => $totalPages
            ];
            
        } catch (PDOException $e) {
            $this->logger->error('Erreur lors de la recherche de trajets : ' . $e->getMessage(), [
                'criteria' => $criteria,
                'sortBy' => $sortBy,
                'page' => $page,
                'limit' => $limit,
                'trace' => $e->getTraceAsString()
            ]);
            
            // En cas d'erreur, retourner un tableau vide avec la structure attendue
            return [
                'rides' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
                'pages' => 0
            ];
        }
    }
} 