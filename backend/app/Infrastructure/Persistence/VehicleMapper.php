<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\Vehicle;

/**
 * Mapper pour convertir les données SQL en entités Vehicle
 */
class VehicleMapper
{
    /**
     * Convertit un tableau de données SQL en entité Vehicle (temporaire/simple)
     */
    public function mapToVehicle(array $data): object
    {
        // Pour l'instant, on retourne un objet simple
        // Dans une implémentation complète, on créerait une vraie entité Vehicle
        return (object) [
            'id' => (int) ($data['voiture_id'] ?? 0),
            'model' => $data['modele'] ?? '',
            'brand' => $data['marque'] ?? '',
            'energy' => $data['type_energie'] ?? '',
            'energyId' => (int) ($data['energie_id'] ?? 0)
        ];
    }

    /**
     * Convertit une entité Vehicle en tableau pour l'insertion/mise à jour SQL
     */
    public function mapToArray(object $vehicle): array
    {
        return [
            'voiture_id' => $vehicle->id ?? 0,
            'modele' => $vehicle->model ?? '',
            'marque' => $vehicle->brand ?? '',
            'type_energie' => $vehicle->energy ?? '',
            'energie_id' => $vehicle->energyId ?? 0
        ];
    }
} 