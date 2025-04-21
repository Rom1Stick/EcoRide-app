<?php

namespace App\Repositories\Interfaces;

use App\Models\Entities\Trip;

/**
 * Interface pour le repository des covoiturages
 */
interface ITripRepository extends IRepository
{
    /**
     * Récupère un covoiturage par son identifiant
     * 
     * @param int $id Identifiant du covoiturage
     * @return array|null Covoiturage trouvé ou null si inexistant
     */
    public function findById(int $id): ?array;

    /**
     * Récupère tous les covoiturages proposés par un conducteur
     * 
     * @param int $driverId Identifiant du conducteur (propriétaire du véhicule)
     * @return array Liste des covoiturages
     */
    public function findByDriverId(int $driverId): array;

    /**
     * Récupère les covoiturages dans une plage de dates
     * 
     * @param string $startDate Date de début (format Y-m-d)
     * @param string $endDate Date de fin (format Y-m-d)
     * @return array Liste des covoiturages
     */
    public function findByDateRange(string $startDate, string $endDate): array;

    /**
     * Récupère les covoiturages pour un trajet spécifique
     * 
     * @param int $departureId Identifiant du lieu de départ
     * @param int $arrivalId Identifiant du lieu d'arrivée
     * @return array Liste des covoiturages
     */
    public function findByRoute(int $departureId, int $arrivalId): array;
    
    /**
     * Recherche des covoiturages selon différents critères
     * 
     * @param array $criteria Critères de recherche (date, lieux, etc.)
     * @return array Liste des covoiturages correspondants
     */
    public function search(array $criteria): array;
} 