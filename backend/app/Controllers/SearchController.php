<?php

namespace App\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Validator;
use App\Services\RideService;
use App\Core\Logger;
use App\Core\Database\MySQLDatabase;
use Exception;

/**
 * Contrôleur pour la recherche de trajets
 */
class SearchController extends Controller
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
     * Constructeur du contrôleur de recherche
     */
    public function __construct()
    {
        parent::__construct();
        
        // Initialiser la réponse HTTP
        $this->response = new Response();
        
        // Initialiser le logger
        $logPath = BASE_PATH . '/logs/search.log';
        $this->logger = new Logger($logPath);
        
        // Obtenir l'instance de la base de données à partir de l'application
        $db = $this->app->getDatabase()->getPdo();
        
        // Créer un wrapper compatible avec notre interface
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
     * Recherche de trajets en fonction des critères
     *
     * @return array Résultats de la recherche
     */
    public function search(): array
    {
        try {
            // Créer une instance de la requête à partir des données globales
            $request = new Request();
            
            // Récupération et validation des paramètres de requête
            $validator = new Validator($request->query());
            
            // Paramètres obligatoires
            $validator->required('departureLocation', 'La localité de départ est obligatoire');
            $validator->required('arrivalLocation', 'La localité d\'arrivée est obligatoire');
            $validator->required('date', 'La date est obligatoire');
            
            // Validation du format de date
            $validator->date('date', 'Y-m-d', 'Le format de date doit être YYYY-MM-DD');
            $validator->dateInFutureOrToday('date', 'La date doit être dans le futur ou aujourd\'hui');
            
            // Paramètres optionnels
            if ($request->query('departureTime')) {
                $validator->time('departureTime', 'H:i', 'Le format de l\'heure doit être HH:MM');
            }
            
            if ($request->query('maxPrice')) {
                $validator->numeric('maxPrice', 'Le prix maximum doit être un nombre');
                $validator->min('maxPrice', 0, 'Le prix maximum doit être positif');
            }
            
            if ($request->query('sortBy')) {
                $validator->in('sortBy', ['departureTime', 'price'], 'Le critère de tri doit être valide');
            }
            
            if ($request->query('page')) {
                $validator->numeric('page', 'Le numéro de page doit être un nombre');
                $validator->min('page', 1, 'Le numéro de page doit être supérieur à 0');
            }
            
            if ($request->query('limit')) {
                $validator->numeric('limit', 'La limite doit être un nombre');
                $validator->min('limit', 1, 'La limite doit être supérieure à 0');
                $validator->max('limit', 50, 'La limite ne peut pas dépasser 50');
            }
            
            // Si la validation échoue, renvoyer les erreurs
            if (!$validator->isValid()) {
                return $this->error([
                    'success' => false,
                    'message' => 'Paramètres de recherche invalides',
                    'errors' => $validator->getErrors()
                ], 400);
            }
            
            // Préparation des critères de recherche validés
            $criteria = [
                'departureLocation' => $request->query('departureLocation'),
                'arrivalLocation' => $request->query('arrivalLocation'),
                'date' => $request->query('date'),
                'departureTime' => $request->query('departureTime'),
                'maxPrice' => $request->query('maxPrice'),
            ];
            
            // Paramètres de pagination
            $page = (int) $request->query('page', 1);
            $limit = (int) $request->query('limit', 10);
            
            // Critère de tri
            $sortBy = $request->query('sortBy', 'departureTime');
            
            // Appel au service pour effectuer la recherche
            $searchResults = $this->rideService->searchRides(
                $criteria,
                $sortBy,
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
            // Log de l'erreur
            $this->logger->error('Erreur lors de la recherche de trajets: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            // Réponse d'erreur générique pour l'utilisateur
            return $this->error([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la recherche de trajets'
            ], 500);
        }
    }
} 