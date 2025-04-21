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
     * Récupère tous les enregistrements avec pagination
     *
     * @param int $page Numéro de page
     * @param int $limit Nombre d'éléments par page
     * @return array Tableau d'objets
     */
    public function findAll(int $page = 1, int $limit = 20): array;
    
    /**
     * Crée un nouvel enregistrement
     *
     * @param object $entity Entité à persister
     * @return int Identifiant de l'entité créée
     */
    public function create($entity): int;
    
    /**
     * Met à jour un enregistrement existant
     *
     * @param object $entity Entité à mettre à jour
     * @return bool Succès de l'opération
     */
    public function update($entity): bool;
    
    /**
     * Supprime un enregistrement par son ID
     *
     * @param int $id Identifiant de l'enregistrement
     * @return bool Succès de l'opération
     */
    public function delete(int $id): bool;
    
    /**
     * Compte le nombre total d'enregistrements
     *
     * @return int Nombre d'enregistrements
     */
    public function count(): int;
} 