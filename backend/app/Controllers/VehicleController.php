<?php

namespace App\Controllers;

use App\Models\Entities\Vehicle;
use App\Models\Repositories\VehicleRepository;
use App\Services\Auth;
use App\Services\Validator;
use App\Services\Response;

/**
 * Contrôleur pour la gestion des véhicules
 */
class VehicleController extends BaseController
{
    private VehicleRepository $vehicleRepository;
    
    public function __construct(VehicleRepository $vehicleRepository)
    {
        parent::__construct();
        $this->vehicleRepository = $vehicleRepository;
    }
    
    /**
     * Liste tous les véhicules
     */
    public function index(): void
    {
        // Vérifier si l'utilisateur est administrateur
        if (!Auth::isAdmin()) {
            Response::json(['error' => 'Accès non autorisé'], 403);
            return;
        }
        
        $vehicles = $this->vehicleRepository->findAll();
        Response::json(['vehicles' => array_map(fn($v) => $v->toArray(), $vehicles)]);
    }
    
    /**
     * Liste les véhicules de l'utilisateur connecté
     */
    public function myVehicles(): void
    {
        $userId = Auth::getUserId();
        if (!$userId) {
            Response::json(['error' => 'Utilisateur non authentifié'], 401);
            return;
        }
        
        $vehicles = $this->vehicleRepository->findByUserId($userId);
        Response::json(['vehicles' => array_map(fn($v) => $v->toArray(), $vehicles)]);
    }
    
    /**
     * Affiche un véhicule spécifique
     */
    public function show(int $id): void
    {
        $vehicle = $this->vehicleRepository->findById($id);
        
        if (!$vehicle) {
            Response::json(['error' => 'Véhicule non trouvé'], 404);
            return;
        }
        
        // Vérifier si l'utilisateur est le propriétaire ou un admin
        if (!Auth::isAdmin() && $vehicle->utilisateur_id !== Auth::getUserId()) {
            Response::json(['error' => 'Accès non autorisé'], 403);
            return;
        }
        
        $vehicleDetails = $this->vehicleRepository->getVehicleDetails($id);
        Response::json(['vehicle' => $vehicleDetails]);
    }
    
    /**
     * Crée un nouveau véhicule
     */
    public function create(): void
    {
        $userId = Auth::getUserId();
        if (!$userId) {
            Response::json(['error' => 'Utilisateur non authentifié'], 401);
            return;
        }
        
        // Récupérer et valider les données
        $data = $this->getRequestData();
        $data['utilisateur_id'] = $userId; // Assigner le véhicule à l'utilisateur connecté
        
        $vehicle = Vehicle::fromArray($data);
        
        // Valider les données
        $validationErrors = $vehicle->validate();
        if (!empty($validationErrors)) {
            Response::json(['error' => 'Données invalides', 'details' => $validationErrors], 400);
            return;
        }
        
        // Vérifier si l'immatriculation existe déjà
        if ($this->vehicleRepository->registrationExists($vehicle->immatriculation)) {
            Response::json(['error' => 'Un véhicule avec cette immatriculation existe déjà'], 409);
            return;
        }
        
        // Créer le véhicule
        try {
            $vehicleId = $this->vehicleRepository->create($vehicle);
            $vehicle->voiture_id = $vehicleId;
            Response::json(['message' => 'Véhicule créé avec succès', 'vehicle' => $vehicle->toArray()], 201);
        } catch (\Exception $e) {
            Response::json(['error' => 'Erreur lors de la création du véhicule: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Met à jour un véhicule existant
     */
    public function update(int $id): void
    {
        $vehicle = $this->vehicleRepository->findById($id);
        
        if (!$vehicle) {
            Response::json(['error' => 'Véhicule non trouvé'], 404);
            return;
        }
        
        // Vérifier si l'utilisateur est le propriétaire ou un admin
        if (!Auth::isAdmin() && $vehicle->utilisateur_id !== Auth::getUserId()) {
            Response::json(['error' => 'Accès non autorisé'], 403);
            return;
        }
        
        // Récupérer et valider les données
        $data = $this->getRequestData();
        $data['voiture_id'] = $id;
        
        if (Auth::isAdmin()) {
            // Un admin peut changer le propriétaire
            if (!isset($data['utilisateur_id'])) {
                $data['utilisateur_id'] = $vehicle->utilisateur_id;
            }
        } else {
            // Un utilisateur normal ne peut pas changer le propriétaire
            $data['utilisateur_id'] = $vehicle->utilisateur_id;
        }
        
        $updatedVehicle = Vehicle::fromArray($data);
        
        // Valider les données
        $validationErrors = $updatedVehicle->validate();
        if (!empty($validationErrors)) {
            Response::json(['error' => 'Données invalides', 'details' => $validationErrors], 400);
            return;
        }
        
        // Vérifier si l'immatriculation existe déjà (sauf pour ce véhicule)
        if ($this->vehicleRepository->registrationExists($updatedVehicle->immatriculation, $id)) {
            Response::json(['error' => 'Un véhicule avec cette immatriculation existe déjà'], 409);
            return;
        }
        
        // Mettre à jour le véhicule
        if (!$this->vehicleRepository->update($updatedVehicle)) {
            Response::json(['error' => 'Erreur lors de la mise à jour du véhicule'], 500);
            return;
        }
        
        Response::json(['message' => 'Véhicule mis à jour avec succès', 'vehicle' => $updatedVehicle->toArray()]);
    }
    
    /**
     * Supprime un véhicule
     */
    public function delete(int $id): void
    {
        $vehicle = $this->vehicleRepository->findById($id);
        
        if (!$vehicle) {
            Response::json(['error' => 'Véhicule non trouvé'], 404);
            return;
        }
        
        // Vérifier si l'utilisateur est le propriétaire ou un admin
        if (!Auth::isAdmin() && $vehicle->utilisateur_id !== Auth::getUserId()) {
            Response::json(['error' => 'Accès non autorisé'], 403);
            return;
        }
        
        // Supprimer le véhicule
        if (!$this->vehicleRepository->delete($id)) {
            Response::json(['error' => 'Erreur lors de la suppression du véhicule'], 500);
            return;
        }
        
        Response::json(['message' => 'Véhicule supprimé avec succès']);
    }
    
    /**
     * Recherche de véhicules
     */
    public function search(): void
    {
        // Vérifier si l'utilisateur est administrateur
        if (!Auth::isAdmin()) {
            Response::json(['error' => 'Accès non autorisé'], 403);
            return;
        }
        
        $data = $this->getRequestData();
        
        $modeleId = $data['modele_id'] ?? null;
        $energieId = $data['energie_id'] ?? null;
        $immatriculation = $data['immatriculation'] ?? null;
        $couleur = $data['couleur'] ?? null;
        
        $vehicles = $this->vehicleRepository->search(
            $modeleId, 
            $energieId, 
            $immatriculation, 
            $couleur
        );
        
        Response::json(['vehicles' => array_map(fn($v) => $v->toArray(), $vehicles)]);
    }
} 