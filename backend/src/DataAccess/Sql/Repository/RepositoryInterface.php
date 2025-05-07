<?php

namespace App\DataAccess\Sql\Repository;

/**
 * Interface RepositoryInterface
 * 
 * Définit le contrat de base pour tous les repositories SQL
 */
interface RepositoryInterface
{
    /**
     * Trouve une entité par son ID
     * 
     * @param int $id Identifiant unique de l'entité
     * @return mixed|null L'entité trouvée ou null si non trouvée
     */
    public function findById(int $id);

    /**
     * Crée une nouvelle entité
     * 
     * @param mixed $entity L'entité à créer
     * @return int L'identifiant de la nouvelle entité créée
     */
    public function create($entity): int;

    /**
     * Met à jour une entité existante
     * 
     * @param mixed $entity L'entité à mettre à jour
     * @return bool True si la mise à jour a réussi, False sinon
     */
    public function update($entity): bool;

    /**
     * Supprime une entité par son ID
     * 
     * @param int $id Identifiant unique de l'entité à supprimer
     * @return bool True si la suppression a réussi, False sinon
     */
    public function delete(int $id): bool;
} 