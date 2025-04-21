<?php

namespace App\Repositories\Interfaces;

use App\Models\Entities\Vehicle;

/**
 * Interface pour le repository des véhicules
 * 
 * @extends IRepository<Vehicle>
 */
interface IVehicleRepository extends IRepository
{
    /**
     * Récupère un véhicule par son ID et le convertit en objet Vehicle
     * 
     * @param int $id ID du véhicule
     * @return Vehicle|null Véhicule trouvé ou null
     */
    public function findVehicleById(int $id): ?Vehicle;
    
    /**
     * Trouve un véhicule par son immatriculation
     *
     * @param string $immatriculation Immatriculation du véhicule
     * @return Vehicle|null Le véhicule trouvé ou null si non trouvé
     */
    public function findByImmatriculation(string $immatriculation): ?Vehicle;
    
    /**
     * Trouve les véhicules d'un utilisateur spécifique
     *
     * @param int $userId Identifiant de l'utilisateur
     * @param int $page Numéro de page
     * @param int $limit Nombre d'éléments par page
     * @return array<Vehicle> Liste des véhicules de l'utilisateur
     */
    public function findByUserId(int $userId, int $page = 1, int $limit = 20): array;
    
    /**
     * Trouve les véhicules par type d'énergie
     *
     * @param int $energieId Identifiant du type d'énergie
     * @param int $page Numéro de page
     * @param int $limit Nombre d'éléments par page
     * @return array<Vehicle> Liste des véhicules utilisant ce type d'énergie
     */
    public function findByEnergieId(int $energieId, int $page = 1, int $limit = 20): array;
    
    /**
     * Compte le nombre de véhicules par utilisateur
     *
     * @param int $userId Identifiant de l'utilisateur
     * @return int Nombre de véhicules de l'utilisateur
     */
    public function countByUserId(int $userId): int;
} 