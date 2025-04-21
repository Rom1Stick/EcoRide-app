<?php

namespace App\Http\Controllers;

use App\Models\Entities\Ride;
use App\Models\Repositories\RideRepository;
use App\Models\Repositories\VehicleRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use DateTime;
use Exception;

/**
 * Contrôleur pour gérer les trajets
 */
class RideController extends Controller
{
    /**
     * Repository des trajets
     *
     * @var RideRepository
     */
    private RideRepository $rideRepository;
    
    /**
     * Repository des véhicules
     *
     * @var VehicleRepository
     */
    private VehicleRepository $vehicleRepository;
    
    /**
     * Constructeur
     *
     * @param RideRepository $rideRepository Repository des trajets
     * @param VehicleRepository $vehicleRepository Repository des véhicules
     */
    public function __construct(
        RideRepository $rideRepository,
        VehicleRepository $vehicleRepository
    ) {
        $this->rideRepository = $rideRepository;
        $this->vehicleRepository = $vehicleRepository;
    }
    
    /**
     * Récupère la liste des trajets pour l'utilisateur courant
     *
     * @param Request $request Requête HTTP
     * @return JsonResponse Réponse JSON avec la liste des trajets
     */
    public function index(Request $request): JsonResponse
    {
        // Récupérer l'ID de l'utilisateur connecté
        $userId = auth()->id();
        
        // Options de filtrage
        $options = [
            'limit' => $request->input('limit', 50),
            'skip' => $request->input('skip', 0)
        ];
        
        // Filtrage par statut
        if ($request->has('status')) {
            $options['status'] = $request->input('status');
        }
        
        // Filtrage par période
        if ($request->has('start_time_from')) {
            $options['start_time_from'] = $request->input('start_time_from');
        }
        
        if ($request->has('start_time_to')) {
            $options['start_time_to'] = $request->input('start_time_to');
        }
        
        // Tri
        if ($request->has('sort_field') && $request->has('sort_order')) {
            $options['sort'] = [
                $request->input('sort_field') => $request->input('sort_order') === 'asc' ? 1 : -1
            ];
        }
        
        // Récupérer les trajets
        $rides = $this->rideRepository->findByUserId($userId, $options);
        
        return response()->json([
            'success' => true,
            'data' => $rides
        ]);
    }
    
    /**
     * Affiche un trajet spécifique
     *
     * @param string $id Identifiant du trajet
     * @return JsonResponse Réponse JSON avec les détails du trajet
     */
    public function show(string $id): JsonResponse
    {
        $ride = $this->rideRepository->findById($id);
        
        if (!$ride) {
            return response()->json([
                'success' => false,
                'message' => 'Trajet non trouvé'
            ], 404);
        }
        
        // Vérifier que le trajet appartient à l'utilisateur connecté
        if ($ride->userId !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé à ce trajet'
            ], 403);
        }
        
        return response()->json([
            'success' => true,
            'data' => $ride
        ]);
    }
    
    /**
     * Crée un nouveau trajet
     *
     * @param Request $request Requête HTTP
     * @return JsonResponse Réponse JSON avec le trajet créé
     */
    public function store(Request $request): JsonResponse
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|integer',
            'start_location' => 'required|string|max:255',
            'end_location' => 'required|string|max:255',
            'start_time' => 'required|date',
            'distance' => 'nullable|numeric|min:0',
            'duration' => 'nullable|integer|min:0',
            'waypoints' => 'nullable|array',
            'metadata' => 'nullable|array'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Récupérer l'ID de l'utilisateur connecté
        $userId = auth()->id();
        
        // Vérifier que le véhicule appartient à l'utilisateur
        $vehicle = $this->vehicleRepository->findById($request->input('vehicle_id'));
        
        if (!$vehicle || $vehicle->userId !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Véhicule non trouvé ou non autorisé'
            ], 403);
        }
        
        try {
            // Créer le trajet
            $ride = new Ride(
                $userId,
                $request->input('vehicle_id'),
                $request->input('start_location'),
                $request->input('end_location'),
                new DateTime($request->input('start_time')),
                $request->input('distance', 0),
                $request->input('duration', 0),
                $request->input('waypoints', []),
                $request->input('metadata', [])
            );
            
            // Sauvegarder le trajet
            $savedRide = $this->rideRepository->save($ride);
            
            return response()->json([
                'success' => true,
                'message' => 'Trajet créé avec succès',
                'data' => $savedRide
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du trajet: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Met à jour un trajet existant
     *
     * @param Request $request Requête HTTP
     * @param string $id Identifiant du trajet
     * @return JsonResponse Réponse JSON avec le trajet mis à jour
     */
    public function update(Request $request, string $id): JsonResponse
    {
        // Récupérer le trajet
        $ride = $this->rideRepository->findById($id);
        
        if (!$ride) {
            return response()->json([
                'success' => false,
                'message' => 'Trajet non trouvé'
            ], 404);
        }
        
        // Vérifier que le trajet appartient à l'utilisateur connecté
        if ($ride->userId !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé à ce trajet'
            ], 403);
        }
        
        // Validation des données
        $validator = Validator::make($request->all(), [
            'start_location' => 'nullable|string|max:255',
            'end_location' => 'nullable|string|max:255',
            'start_time' => 'nullable|date',
            'distance' => 'nullable|numeric|min:0',
            'duration' => 'nullable|integer|min:0',
            'status' => 'nullable|string|in:planned,ongoing,completed,cancelled',
            'waypoints' => 'nullable|array',
            'metadata' => 'nullable|array'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            // Mettre à jour les champs si présents dans la requête
            if ($request->has('start_location') && $request->has('end_location')) {
                $ride->updateRoute(
                    $request->input('start_location'),
                    $request->input('end_location'),
                    $request->input('waypoints', $ride->waypoints)
                );
            }
            
            if ($request->has('start_time')) {
                $ride->startTime = new DateTime($request->input('start_time'));
            }
            
            if ($request->has('distance')) {
                $ride->distance = $request->input('distance');
            }
            
            if ($request->has('duration')) {
                $ride->duration = $request->input('duration');
            }
            
            // Gérer le changement de statut
            if ($request->has('status')) {
                $newStatus = $request->input('status');
                
                if ($newStatus === Ride::STATUS_ONGOING && !$ride->isOngoing()) {
                    $ride->start();
                } elseif ($newStatus === Ride::STATUS_COMPLETED && !$ride->isCompleted()) {
                    $ride->complete();
                } elseif ($newStatus === Ride::STATUS_CANCELLED && !$ride->isCancelled()) {
                    $ride->cancel();
                }
            }
            
            // Gérer les métadonnées
            if ($request->has('metadata')) {
                $metadata = $request->input('metadata');
                
                foreach ($metadata as $key => $value) {
                    $ride->addMetadata($key, $value);
                }
            }
            
            // Sauvegarder les modifications
            $updatedRide = $this->rideRepository->save($ride);
            
            return response()->json([
                'success' => true,
                'message' => 'Trajet mis à jour avec succès',
                'data' => $updatedRide
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du trajet: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Démarre un trajet
     *
     * @param string $id Identifiant du trajet
     * @return JsonResponse Réponse JSON avec le trajet démarré
     */
    public function start(string $id): JsonResponse
    {
        // Récupérer le trajet
        $ride = $this->rideRepository->findById($id);
        
        if (!$ride) {
            return response()->json([
                'success' => false,
                'message' => 'Trajet non trouvé'
            ], 404);
        }
        
        // Vérifier que le trajet appartient à l'utilisateur connecté
        if ($ride->userId !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé à ce trajet'
            ], 403);
        }
        
        try {
            // Vérifier si le trajet peut être démarré
            if (!$ride->isPlanned()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le trajet ne peut pas être démarré car il n\'est pas dans l\'état "planifié"'
                ], 400);
            }
            
            // Démarrer le trajet
            $ride->start();
            
            // Sauvegarder les modifications
            $updatedRide = $this->rideRepository->save($ride);
            
            return response()->json([
                'success' => true,
                'message' => 'Trajet démarré avec succès',
                'data' => $updatedRide
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du démarrage du trajet: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Termine un trajet
     *
     * @param Request $request Requête HTTP
     * @param string $id Identifiant du trajet
     * @return JsonResponse Réponse JSON avec le trajet terminé
     */
    public function complete(Request $request, string $id): JsonResponse
    {
        // Récupérer le trajet
        $ride = $this->rideRepository->findById($id);
        
        if (!$ride) {
            return response()->json([
                'success' => false,
                'message' => 'Trajet non trouvé'
            ], 404);
        }
        
        // Vérifier que le trajet appartient à l'utilisateur connecté
        if ($ride->userId !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé à ce trajet'
            ], 403);
        }
        
        // Validation des données
        $validator = Validator::make($request->all(), [
            'distance' => 'nullable|numeric|min:0',
            'duration' => 'nullable|integer|min:0'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            // Vérifier si le trajet peut être terminé
            if (!$ride->isOngoing()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le trajet ne peut pas être terminé car il n\'est pas en cours'
                ], 400);
            }
            
            // Terminer le trajet
            $ride->complete(
                new DateTime(),
                $request->input('distance', $ride->distance),
                $request->input('duration')
            );
            
            // Calculer le coût du trajet
            $ride->calculateCost();
            
            // Sauvegarder les modifications
            $updatedRide = $this->rideRepository->save($ride);
            
            return response()->json([
                'success' => true,
                'message' => 'Trajet terminé avec succès',
                'data' => $updatedRide
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la finalisation du trajet: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Annule un trajet
     *
     * @param string $id Identifiant du trajet
     * @return JsonResponse Réponse JSON avec le trajet annulé
     */
    public function cancel(string $id): JsonResponse
    {
        // Récupérer le trajet
        $ride = $this->rideRepository->findById($id);
        
        if (!$ride) {
            return response()->json([
                'success' => false,
                'message' => 'Trajet non trouvé'
            ], 404);
        }
        
        // Vérifier que le trajet appartient à l'utilisateur connecté
        if ($ride->userId !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé à ce trajet'
            ], 403);
        }
        
        try {
            // Vérifier si le trajet peut être annulé
            if ($ride->isCompleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le trajet ne peut pas être annulé car il est déjà terminé'
                ], 400);
            }
            
            // Annuler le trajet
            $ride->cancel();
            
            // Sauvegarder les modifications
            $updatedRide = $this->rideRepository->save($ride);
            
            return response()->json([
                'success' => true,
                'message' => 'Trajet annulé avec succès',
                'data' => $updatedRide
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation du trajet: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Supprime un trajet
     *
     * @param string $id Identifiant du trajet
     * @return JsonResponse Réponse JSON avec le statut de suppression
     */
    public function destroy(string $id): JsonResponse
    {
        // Récupérer le trajet
        $ride = $this->rideRepository->findById($id);
        
        if (!$ride) {
            return response()->json([
                'success' => false,
                'message' => 'Trajet non trouvé'
            ], 404);
        }
        
        // Vérifier que le trajet appartient à l'utilisateur connecté
        if ($ride->userId !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé à ce trajet'
            ], 403);
        }
        
        // Supprimer le trajet
        $success = $this->rideRepository->delete($id);
        
        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Trajet supprimé avec succès'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du trajet'
            ], 500);
        }
    }
    
    /**
     * Récupère les statistiques de l'utilisateur courant
     *
     * @param Request $request Requête HTTP
     * @return JsonResponse Réponse JSON avec les statistiques
     */
    public function getUserStats(Request $request): JsonResponse
    {
        // Récupérer l'ID de l'utilisateur connecté
        $userId = auth()->id();
        
        // Options de filtrage
        $options = [];
        
        // Filtrage par période
        if ($request->has('start_time_from')) {
            $options['start_time_from'] = $request->input('start_time_from');
        }
        
        if ($request->has('start_time_to')) {
            $options['start_time_to'] = $request->input('start_time_to');
        }
        
        // Récupérer les statistiques
        $stats = $this->rideRepository->getUserStats($userId, $options);
        
        // Récupérer le nombre de trajets par statut
        $ridesByStatus = $this->rideRepository->countRidesByStatus($userId, $options);
        
        // Combiner les résultats
        $result = array_merge($stats, ['rides_by_status' => $ridesByStatus]);
        
        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }
    
    /**
     * Récupère les trajets récents de l'utilisateur
     *
     * @param Request $request Requête HTTP
     * @return JsonResponse Réponse JSON avec les trajets récents
     */
    public function getRecentRides(Request $request): JsonResponse
    {
        // Récupérer l'ID de l'utilisateur connecté
        $userId = auth()->id();
        
        // Nombre de trajets à récupérer
        $limit = $request->input('limit', 5);
        
        // Récupérer les trajets récents
        $rides = $this->rideRepository->getRecentRides($userId, $limit);
        
        return response()->json([
            'success' => true,
            'data' => $rides
        ]);
    }
    
    /**
     * Récupère les trajets à venir de l'utilisateur
     *
     * @return JsonResponse Réponse JSON avec les trajets à venir
     */
    public function getUpcomingRides(): JsonResponse
    {
        // Récupérer l'ID de l'utilisateur connecté
        $userId = auth()->id();
        
        // Récupérer les trajets à venir
        $rides = $this->rideRepository->getUpcomingRides($userId);
        
        return response()->json([
            'success' => true,
            'data' => $rides
        ]);
    }
} 