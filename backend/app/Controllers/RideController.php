<?php

namespace App\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Validator;
use App\Services\RideService;
use App\Core\Logger;
use App\Core\Database\MySQLDatabase;
use PDO;
use Exception;

/**
 * Contrôleur pour la gestion des trajets de covoiturage
 */
class RideController extends Controller
{
    /**
     * Service de gestion des trajets
     * @var RideService
     */
    private $rideService;

    /**
     * Instance de la réponse HTTP
     * @var Response
     */
    protected $response;

    /**
     * Instance du logger
     * @var Logger
     */
    protected $logger;

    /**
     * Constructeur du contrôleur de trajets
     */
    public function __construct()
    {
        parent::__construct();
        
        // Initialiser la réponse HTTP
        $this->response = new Response();
        
        // Initialiser le logger
        $logPath = BASE_PATH . '/logs/rides.log';
        $this->logger = new Logger($logPath);
        
        // Obtenir l'instance de la base de données
        $dbWrapper = new MySQLDatabase(
            env('DB_HOST', 'localhost'),
            env('DB_DATABASE', 'ecoride'),
            env('DB_USERNAME', 'root'),
            env('DB_PASSWORD', ''),
            $this->logger
        );
        
        // Initialiser le service de trajets avec les dépendances
        $this->rideService = new RideService($dbWrapper, $this->logger);
    }

    /**
     * Liste des trajets disponibles
     */
    public function index(): array
    {
        try {
            // Paramètres de pagination
            $page = (int) ($_GET['page'] ?? 1);
            $limit = (int) ($_GET['limit'] ?? 10);
            
            // Critères de recherche (vides par défaut)
            $criteria = [];
            
            // Appel au service pour rechercher les trajets
            $searchResults = $this->rideService->searchRides(
                $criteria,
                'departureTime',
                $page,
                $limit
            );
            
            // Retourner les résultats
            return $this->success([
                'rides' => $searchResults['rides'],
                'pagination' => [
                    'total' => $searchResults['total'],
                    'page' => $searchResults['page'],
                    'limit' => $searchResults['limit'],
                    'pages' => $searchResults['pages']
                ]
            ]);
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la récupération des trajets: ' . $e->getMessage());
            return $this->error('Une erreur est survenue lors de la récupération des trajets', 500);
        }
    }

    /**
     * Détails d'un trajet spécifique
     */
    public function show(int $id): array
    {
        try {
            $db = $this->app->getDatabase()->getMysqlConnection();
            
            // Requête pour récupérer les détails du trajet
            $stmt = $db->prepare(
                'SELECT 
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
                    te.energie_id,
                    te.libelle AS type_energie,
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
                WHERE 
                    c.covoiturage_id = ?'
            );
            
            $stmt->execute([$id]);
            $ride = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$ride) {
                return $this->error('Trajet non trouvé', 404);
            }
            
            // Formater les données pour le frontend
            $formattedRide = [
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
                ],
                'vehicle' => [
                    'model' => $ride['modele'],
                    'brand' => $ride['marque'],
                    'energy' => $ride['type_energie'],
                    'energyId' => (int) $ride['energie_id']
                ],
                'ecologicalImpact' => [
                    'carbonFootprint' => (float) $ride['empreinte_carbone']
                ]
            ];
            
            return $this->success($formattedRide);
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la récupération du trajet: ' . $e->getMessage());
            return $this->error('Une erreur est survenue lors de la récupération du trajet', 500);
        }
    }

    /**
     * Création d'un nouveau trajet
     */
    public function store(): array
    {
        try {
            // Débogage: Enregistrer dans un fichier que le contrôleur est exécuté
            $logPath = BASE_PATH . '/logs/ride_debug.log';
            file_put_contents($logPath, "RideController::store exécuté à " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
            
            // Débogage: Enregistrer toutes les variables de $_SERVER pour l'authentification
            file_put_contents($logPath, "Variables AUTH dans \$_SERVER:\n", FILE_APPEND);
            foreach ($_SERVER as $key => $value) {
                if (strpos($key, 'AUTH_') === 0) {
                    if (is_array($value)) {
                        file_put_contents($logPath, "$key: " . json_encode($value) . "\n", FILE_APPEND);
                    } else {
                        file_put_contents($logPath, "$key: $value\n", FILE_APPEND);
                    }
                }
            }
            
            // Vérifier que l'utilisateur est connecté et a le rôle chauffeur
            $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
            $userRoles = $_SERVER['AUTH_USER_ROLES'] ?? [];
            
            file_put_contents($logPath, "UserID extrait: $userId\n", FILE_APPEND);
            file_put_contents($logPath, "UserRoles extraits: " . json_encode($userRoles) . "\n", FILE_APPEND);
            
            if (!$userId) {
                file_put_contents($logPath, "ERREUR: Utilisateur non authentifié\n\n", FILE_APPEND);
                return $this->error('Utilisateur non authentifié', 401);
            }
            
            // Vérifier si l'utilisateur a le rôle "chauffeur" parmi ses rôles
            $isDriver = false;
            if (is_array($userRoles)) {
                $isDriver = in_array('chauffeur', $userRoles);
                file_put_contents($logPath, "Vérification du rôle 'chauffeur' dans le tableau: " . ($isDriver ? 'trouvé' : 'non trouvé') . "\n", FILE_APPEND);
            } elseif (($_SERVER['AUTH_USER_ROLE'] ?? '') === 'chauffeur') {
                // Rétrocompatibilité avec l'ancien format
                $isDriver = true;
                file_put_contents($logPath, "Vérification du rôle 'chauffeur' en format unique: trouvé\n", FILE_APPEND);
            }
            
            if (!$isDriver) {
                file_put_contents($logPath, "ERREUR: L'utilisateur n'a pas le rôle chauffeur\n\n", FILE_APPEND);
                return $this->error('Seuls les chauffeurs peuvent créer des trajets', 403);
            }
            
            // Récupérer les données du formulaire
            $data = $this->getJsonData();
            file_put_contents($logPath, "Données de formulaire reçues: " . json_encode($data) . "\n", FILE_APPEND);
            
            // Valider les données
            $validator = new Validator($data);
            $validator->required('departure', 'Le lieu de départ est requis');
            $validator->required('destination', 'Le lieu d\'arrivée est requis');
            $validator->required('date', 'La date est requise');
            $validator->required('departureTime', 'L\'heure de départ est requise');
            $validator->required('price', 'Le prix est requis');
            $validator->numeric('price', 'Le prix doit être un nombre');
            $validator->min('price', 0, 'Le prix ne peut pas être négatif');
            $validator->required('totalSeats', 'Le nombre de places est requis');
            $validator->integer('totalSeats', 'Le nombre de places doit être un nombre entier');
            $validator->min('totalSeats', 1, 'Le nombre de places doit être d\'au moins 1');
            
            if (!$validator->isValid()) {
                return $this->error([
                    'message' => 'Données invalides',
                    'errors' => $validator->getErrors()
                ], 400);
            }
            
            // Récupération de la connexion à la base de données
            $db = $this->app->getDatabase()->getMysqlConnection();
            
            // Gestion des transactions
            $db->beginTransaction();
            
            try {
                // 1. Récupérer l'ID du véhicule du chauffeur (en utilisant le premier véhicule trouvé)
                $stmtVehicle = $db->prepare('SELECT voiture_id FROM Voiture WHERE utilisateur_id = ? LIMIT 1');
                $stmtVehicle->execute([$userId]);
                $vehicleId = $stmtVehicle->fetchColumn();
                
                if (!$vehicleId) {
                    // Aucun véhicule trouvé, donc on en crée un par défaut
                    file_put_contents($logPath, "Aucun véhicule trouvé, création d'un véhicule par défaut\n", FILE_APPEND);
                    
                    // Récupérer les IDs nécessaires
                    // 1. ID de la marque par défaut (Toyota)
                    $stmtBrand = $db->prepare('SELECT marque_id FROM Marque WHERE libelle = ?');
                    $stmtBrand->execute(['Toyota']);
                    $brandId = $stmtBrand->fetchColumn();
                    
                    if (!$brandId) {
                        // Créer la marque si elle n'existe pas
                        $stmtInsertBrand = $db->prepare('INSERT INTO Marque (libelle) VALUES (?)');
                        $stmtInsertBrand->execute(['Toyota']);
                        $brandId = $db->lastInsertId();
                    }
                    
                    // 2. ID du modèle par défaut (Prius)
                    $stmtModel = $db->prepare('SELECT modele_id FROM Modele WHERE nom = ? AND marque_id = ?');
                    $stmtModel->execute(['Prius', $brandId]);
                    $modelId = $stmtModel->fetchColumn();
                    
                    if (!$modelId) {
                        // Créer le modèle s'il n'existe pas
                        $stmtInsertModel = $db->prepare('INSERT INTO Modele (nom, marque_id) VALUES (?, ?)');
                        $stmtInsertModel->execute(['Prius', $brandId]);
                        $modelId = $db->lastInsertId();
                    }
                    
                    // 3. ID du type d'énergie par défaut (Hybride)
                    $stmtEnergy = $db->prepare('SELECT energie_id FROM TypeEnergie WHERE libelle = ?');
                    $stmtEnergy->execute(['Hybride']);
                    $energyId = $stmtEnergy->fetchColumn();
                    
                    if (!$energyId) {
                        // Créer le type d'énergie s'il n'existe pas
                        $stmtInsertEnergy = $db->prepare('INSERT INTO TypeEnergie (libelle) VALUES (?)');
                        $stmtInsertEnergy->execute(['Hybride']);
                        $energyId = $db->lastInsertId();
                    }
                    
                    // Générer une plaque d'immatriculation aléatoire
                    $randomChars = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 2));
                    $randomNumbers = substr(str_shuffle('0123456789'), 0, 3);
                    $randomChars2 = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 2));
                    $licencePlate = $randomChars . '-' . $randomNumbers . '-' . $randomChars2;
                    
                    // Créer le véhicule
                    $stmtInsertVehicle = $db->prepare('
                        INSERT INTO Voiture (
                            modele_id, immatriculation, energie_id, 
                            couleur, date_premiere_immat, utilisateur_id
                        ) VALUES (?, ?, ?, ?, ?, ?)
                    ');
                    
                    $stmtInsertVehicle->execute([
                        $modelId,
                        $licencePlate,
                        $energyId,
                        'Blanc',
                        date('Y-m-d', strtotime('-3 years')),
                        $userId
                    ]);
                    
                    $vehicleId = $db->lastInsertId();
                    file_put_contents($logPath, "Véhicule créé avec ID: $vehicleId\n", FILE_APPEND);
                    
                    if (!$vehicleId) {
                        $db->rollBack();
                        return $this->error('Erreur lors de la création du véhicule par défaut', 500);
                    }
                }
                
                // 2. Récupérer ou créer les lieux de départ et d'arrivée
                $departureId = $this->getOrCreateLocation($db, $data['departure']);
                $destinationId = $this->getOrCreateLocation($db, $data['destination']);
                
                // 3. Récupérer l'ID du statut "planifié"
                $stmtStatus = $db->prepare('SELECT statut_id FROM StatutCovoiturage WHERE libelle = ?');
                $stmtStatus->execute(['planifié']);
                $statusId = $stmtStatus->fetchColumn();
                
                if (!$statusId) {
                    // Statut non trouvé, le créer automatiquement
                    file_put_contents($logPath, "Statut 'planifié' non trouvé, création automatique\n", FILE_APPEND);
                    
                    $stmtInsertStatus = $db->prepare('INSERT INTO StatutCovoiturage (libelle) VALUES (?)');
                    $stmtInsertStatus->execute(['planifié']);
                    $statusId = $db->lastInsertId();
                    
                    file_put_contents($logPath, "Statut 'planifié' créé avec ID: $statusId\n", FILE_APPEND);
                    
                    if (!$statusId) {
                        $db->rollBack();
                        return $this->error('Impossible de créer le statut de covoiturage', 500);
                    }
                }
                
                // Initialisation des tables de statuts requises
                // S'assurer que les statuts nécessaires existent dans StatutParticipation
                $stmtParticipationStatus = $db->prepare('SELECT COUNT(*) FROM StatutParticipation WHERE libelle IN (?, ?, ?)');
                $stmtParticipationStatus->execute(['en attente', 'confirmé', 'annulé']);
                $participationStatusCount = $stmtParticipationStatus->fetchColumn();
                
                if ($participationStatusCount < 3) {
                    file_put_contents($logPath, "Certains statuts de participation manquent, création automatique\n", FILE_APPEND);
                    
                    // Vérifier et créer chaque statut individuellement
                    $statuses = ['en attente', 'confirmé', 'annulé'];
                    $stmtCheckStatus = $db->prepare('SELECT statut_id FROM StatutParticipation WHERE libelle = ?');
                    $stmtInsertStatus = $db->prepare('INSERT INTO StatutParticipation (libelle) VALUES (?)');
                    
                    foreach ($statuses as $status) {
                        $stmtCheckStatus->execute([$status]);
                        if (!$stmtCheckStatus->fetchColumn()) {
                            $stmtInsertStatus->execute([$status]);
                            file_put_contents($logPath, "Statut de participation '$status' créé\n", FILE_APPEND);
                        }
                    }
                }
                
                // 4. Calculer l'empreinte carbone (exemple simple basé sur la distance)
                $carbonFootprint = 120; // Valeur par défaut en g/km
                
                // 5. Insérer le covoiturage
                $stmt = $db->prepare(
                    'INSERT INTO Covoiturage (
                        voiture_id, lieu_depart_id, lieu_arrivee_id, date_depart, heure_depart, 
                        date_arrivee, heure_arrivee, nb_place, empreinte_carbone, prix_personne, 
                        statut_id, date_creation
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
                );
                
                $result = $stmt->execute([
                    $vehicleId,
                    $departureId,
                    $destinationId,
                    $data['date'],
                    $data['departureTime'],
                    $data['date'], // Même date pour l'arrivée par simplicité
                    $data['arrivalTime'] ?? $this->calculateArrivalTime($data['departureTime']),
                    $data['totalSeats'],
                    $carbonFootprint,
                    $data['price'],
                    $statusId
                ]);
                
                if (!$result) {
                    $db->rollBack();
                    return $this->error('Erreur lors de la création du trajet', 500);
                }
                
                $rideId = $db->lastInsertId();
                
                // Commit de la transaction
                $db->commit();
                
                return $this->success([
                    'id' => $rideId,
                    'message' => 'Trajet créé avec succès'
                ]);
                
            } catch (Exception $e) {
                $db->rollBack();
                $this->logger->error('Erreur lors de la création du trajet: ' . $e->getMessage());
                return $this->error('Une erreur est survenue lors de la création du trajet', 500);
            }
            
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la création du trajet: ' . $e->getMessage());
            return $this->error('Une erreur est survenue lors de la création du trajet', 500);
        }
    }

    /**
     * Mise à jour d'un trajet existant
     */
    public function update(int $id): array
    {
        try {
            // Vérifier que l'utilisateur est connecté
            $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
            
            if (!$userId) {
                return $this->error('Utilisateur non authentifié', 401);
            }
            
            // Récupérer les données JSON de la requête
            $request = new Request();
            $data = $request->json();
            
            // Journaliser les données reçues pour débogage
            $this->logger->info('Données JSON reçues pour mise à jour: ' . json_encode($data));
            
            // Vérifier si les données JSON ont été correctement reçues
            if ($data === null) {
                // Tentative de récupération des données via php://input directement
                $rawData = file_get_contents('php://input');
                $this->logger->info('Récupération alternative des données brutes: ' . $rawData);
                $data = json_decode($rawData, true);
                
                if ($data === null) {
                    return $this->error('Données JSON non valides ou absentes', 400);
                }
            }
            
            // Valider les données
            $validator = new Validator($data);
            $validator->required('departure', 'destination', 'date', 'departureTime', 'price', 'totalSeats');
            $validator->numeric('price', 'totalSeats');
            $validator->min('price', 0, 'Le prix ne peut pas être négatif');
            $validator->min('totalSeats', 1, 'Le nombre de places doit être d\'au moins 1');
            
            if (!$validator->isValid()) {
                return $this->error('Données invalides: ' . implode(', ', $validator->getErrors()), 400);
            }
            
            $db = $this->app->getDatabase()->getMysqlConnection();
            
            // Vérifier que l'utilisateur est bien le propriétaire du covoiturage
            $stmt = $db->prepare(
                'SELECT c.covoiturage_id, c.voiture_id
                FROM Covoiturage c
                JOIN Voiture v ON c.voiture_id = v.voiture_id
                WHERE c.covoiturage_id = ? AND v.utilisateur_id = ?'
            );
            
            $stmt->execute([$id, $userId]);
            $ride = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$ride) {
                return $this->error('Vous n\'êtes pas autorisé à modifier ce trajet ou le trajet n\'existe pas', 403);
            }
            
            // Commencer une transaction
            $db->beginTransaction();
            
            try {
                // 1. Récupérer ou créer les lieux
                $departureId = $this->getOrCreateLocation($db, $data['departure']);
                $destinationId = $this->getOrCreateLocation($db, $data['destination']);
                
                // 2. Calculer heure d'arrivée si non fournie
                $arrivalTime = $data['arrivalTime'] ?? $this->calculateArrivalTime($data['departureTime']);
                
                // 3. Vérifier le nombre de places déjà réservées
                $stmtBookedSeats = $db->prepare(
                    'SELECT COUNT(*) FROM Participation 
                     WHERE covoiturage_id = ? 
                     AND statut_id = (SELECT statut_id FROM StatutParticipation WHERE libelle = "confirmé")'
                );
                $stmtBookedSeats->execute([$id]);
                $bookedSeats = (int) $stmtBookedSeats->fetchColumn();
                
                // Vérifier que le nouveau nombre de places total est suffisant
                if ($data['totalSeats'] < $bookedSeats) {
                    $db->rollBack();
                    return $this->error(
                        "Impossible de réduire le nombre de places en dessous du nombre de réservations déjà confirmées ($bookedSeats)",
                        400
                    );
                }
                
                // 4. Mettre à jour le covoiturage
                $stmt = $db->prepare(
                    'UPDATE Covoiturage SET 
                        lieu_depart_id = ?, 
                        lieu_arrivee_id = ?, 
                        date_depart = ?, 
                        heure_depart = ?,
                        date_arrivee = ?,
                        heure_arrivee = ?,
                        nb_place = ?,
                        prix_personne = ?
                    WHERE covoiturage_id = ?'
                );
                
                $result = $stmt->execute([
                    $departureId,
                    $destinationId,
                    $data['date'],
                    $data['departureTime'],
                    $data['date'], // Même date pour l'arrivée par simplicité
                    $arrivalTime,
                    $data['totalSeats'],
                    $data['price'],
                    $id
                ]);
                
                if (!$result) {
                    $db->rollBack();
                    return $this->error('Erreur lors de la mise à jour du trajet', 500);
                }
                
                // Valider la transaction
                $db->commit();
                
                // Retourner en format JSON
                header('Content-Type: application/json');
                return $this->success([
                    'id' => $id,
                    'message' => 'Trajet mis à jour avec succès'
                ]);
                
            } catch (Exception $e) {
                $db->rollBack();
                $this->logger->error('Erreur lors de la mise à jour du trajet: ' . $e->getMessage());
                return $this->error('Une erreur est survenue lors de la mise à jour du trajet', 500);
            }
            
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la mise à jour du trajet: ' . $e->getMessage());
            return $this->error('Une erreur est survenue lors de la mise à jour du trajet', 500);
        }
    }

    /**
     * Suppression d'un trajet
     */
    public function destroy(int $id): array
    {
        try {
            // Vérifier que l'utilisateur est connecté
            $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
            
            if (!$userId) {
                return $this->error('Utilisateur non authentifié', 401);
            }
            
            $db = $this->app->getDatabase()->getMysqlConnection();
            
            // Vérifier que l'utilisateur est bien le propriétaire du covoiturage
            $stmt = $db->prepare(
                'SELECT c.covoiturage_id
                FROM Covoiturage c
                JOIN Voiture v ON c.voiture_id = v.voiture_id
                WHERE c.covoiturage_id = ? AND v.utilisateur_id = ?'
            );
            
            $stmt->execute([$id, $userId]);
            $ride = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$ride) {
                return $this->error('Vous n\'êtes pas autorisé à supprimer ce trajet ou le trajet n\'existe pas', 403);
            }
            
            // Commencer une transaction
            $db->beginTransaction();
            
            try {
                // Supprimer d'abord les participations associées
                $stmtDeleteParticipations = $db->prepare('DELETE FROM Participation WHERE covoiturage_id = ?');
                $stmtDeleteParticipations->execute([$id]);
                
                // Supprimer le covoiturage
                $stmtDeleteRide = $db->prepare('DELETE FROM Covoiturage WHERE covoiturage_id = ?');
                $result = $stmtDeleteRide->execute([$id]);
                
                if (!$result) {
                    $db->rollBack();
                    return $this->error('Erreur lors de la suppression du trajet', 500);
                }
                
                // Valider la transaction
                $db->commit();
                
                // Retourner en format JSON
                header('Content-Type: application/json');
                return $this->success([
                    'message' => 'Trajet supprimé avec succès'
                ]);
                
            } catch (Exception $e) {
                $db->rollBack();
                $this->logger->error('Erreur lors de la suppression du trajet: ' . $e->getMessage());
                return $this->error('Une erreur est survenue lors de la suppression du trajet', 500);
            }
            
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la suppression du trajet: ' . $e->getMessage());
            return $this->error('Une erreur est survenue lors de la suppression du trajet', 500);
        }
    }

    /**
     * Récupère ou crée un lieu et retourne son ID
     */
    private function getOrCreateLocation(PDO $db, string $locationName): int
    {
        // Vérifier si le lieu existe déjà
        $stmt = $db->prepare('SELECT lieu_id FROM Lieu WHERE nom = ?');
        $stmt->execute([$locationName]);
        $locationId = $stmt->fetchColumn();
        
        if ($locationId) {
            return $locationId;
        }
        
        // Créer un nouveau lieu
        $stmt = $db->prepare('INSERT INTO Lieu (nom) VALUES (?)');
        $stmt->execute([$locationName]);
        
        return (int) $db->lastInsertId();
    }

    /**
     * Calcule une heure d'arrivée à partir de l'heure de départ
     */
    private function calculateArrivalTime(string $departureTime): string
    {
        // Exemple simple : ajouter 1h30 à l'heure de départ
        list($hours, $minutes) = explode(':', $departureTime);
        $departureMinutes = (int)$hours * 60 + (int)$minutes;
        $arrivalMinutes = $departureMinutes + 90; // +1h30
        
        $arrivalHours = floor($arrivalMinutes / 60) % 24;
        $arrivalMinutes = $arrivalMinutes % 60;
        
        return sprintf('%02d:%02d', $arrivalHours, $arrivalMinutes);
    }

    /**
     * Récupère les covoiturages créés par l'utilisateur connecté
     */
    public function getMyRides(): array
    {
        try {
            // Vérifier que l'utilisateur est connecté
            $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
            
            if (!$userId) {
                return $this->error('Utilisateur non authentifié', 401);
            }
            
            $db = $this->app->getDatabase()->getMysqlConnection();
            
            // Requête pour récupérer les covoiturages créés par l'utilisateur
            $stmt = $db->prepare(
                'SELECT 
                    c.covoiturage_id as id, 
                    ld.nom AS departure, 
                    la.nom AS destination,
                    c.date_depart, 
                    c.heure_depart AS departureTime,
                    c.date_arrivee, 
                    c.heure_arrivee AS arrivalTime,
                    c.nb_place AS totalSeats,
                    c.prix_personne AS price,
                    (c.nb_place - (
                        SELECT COUNT(*)
                        FROM Participation p
                        WHERE p.covoiturage_id = c.covoiturage_id
                        AND p.statut_id = (SELECT statut_id FROM StatutParticipation WHERE libelle = "confirmé")
                    )) AS availableSeats,
                    sc.libelle AS status
                FROM 
                    Covoiturage c
                JOIN 
                    Lieu ld ON c.lieu_depart_id = ld.lieu_id
                JOIN 
                    Lieu la ON c.lieu_arrivee_id = la.lieu_id
                JOIN 
                    Voiture v ON c.voiture_id = v.voiture_id
                JOIN
                    StatutCovoiturage sc ON c.statut_id = sc.statut_id
                WHERE 
                    v.utilisateur_id = ?
                ORDER BY 
                    c.date_depart DESC, c.heure_depart ASC'
            );
            
            $stmt->execute([$userId]);
            $rides = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            return $this->success([
                'rides' => $rides
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Erreur lors de la récupération des covoiturages: ' . $e->getMessage());
            return $this->error('Une erreur est survenue lors de la récupération des covoiturages', 500);
        }
    }
} 