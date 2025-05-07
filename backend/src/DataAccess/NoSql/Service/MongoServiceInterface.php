<?php

namespace App\DataAccess\NoSql\Service;

use MongoDB\BSON\ObjectId;

/**
 * Interface MongoServiceInterface
 * 
 * Définit le contrat de base pour les services d'accès à MongoDB
 */
interface MongoServiceInterface
{
    /**
     * Trouve un document par son ID
     * 
     * @param string|ObjectId $id Identifiant unique du document
     * @return mixed|null Le document trouvé ou null si non trouvé
     */
    public function findById($id);

    /**
     * Insère un nouveau document
     * 
     * @param array $data Données du document à insérer
     * @return ObjectId Identifiant du document inséré
     */
    public function insert(array $data): ObjectId;

    /**
     * Met à jour un document existant
     * 
     * @param string|ObjectId $id Identifiant unique du document
     * @param array $data Données à mettre à jour
     * @return bool True si la mise à jour a réussi, False sinon
     */
    public function update($id, array $data): bool;

    /**
     * Supprime un document par son ID
     * 
     * @param string|ObjectId $id Identifiant unique du document à supprimer
     * @return bool True si la suppression a réussi, False sinon
     */
    public function delete($id): bool;

    /**
     * Recherche des documents selon des critères
     * 
     * @param array $criteria Critères de recherche
     * @param array $options Options de recherche (tri, limite, etc.)
     * @return array Liste des documents correspondants
     */
    public function find(array $criteria = [], array $options = []): array;

    /**
     * Compte le nombre de documents correspondant aux critères
     * 
     * @param array $criteria Critères de comptage
     * @return int Nombre de documents
     */
    public function count(array $criteria = []): int;
} 