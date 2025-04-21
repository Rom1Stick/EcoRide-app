<?php

namespace App\Repositories\Interfaces;

/**
 * Interface générique pour tous les repositories
 * 
 * @template T
 */
interface IRepository
{
    /**
     * Trouve une entité par son identifiant
     *
     * @param int $id Identifiant de l'entité
     * @return T|null L'entité trouvée ou null si non trouvée
     */
    public function findById(int $id);
    
    /**
     * Récupère toutes les entités avec pagination
     *
     * @param int $page Numéro de page (commence à 1)
     * @param int $limit Nombre d'éléments par page
     * @return array<T> Liste des entités
     */
    public function findAll(int $page = 1, int $limit = 20): array;
    
    /**
     * Crée une nouvelle entité
     *
     * @param T $entity L'entité à créer
     * @return int L'identifiant de la nouvelle entité
     */
    public function create($entity): int;
    
    /**
     * Met à jour une entité existante
     *
     * @param T $entity L'entité à mettre à jour
     * @return bool Succès de la mise à jour
     */
    public function update($entity): bool;
    
    /**
     * Supprime une entité par son identifiant
     *
     * @param int $id Identifiant de l'entité à supprimer
     * @return bool Succès de la suppression
     */
    public function delete(int $id): bool;
    
    /**
     * Compte le nombre total d'entités
     *
     * @return int Nombre total d'entités
     */
    public function count(): int;
} 