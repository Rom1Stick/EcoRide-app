<?php

namespace App\Core\Database;

/**
 * Interface pour les opérations de base de données
 */
interface DatabaseInterface
{
    /**
     * Exécute une requête SQL avec des paramètres
     *
     * @param string $query Requête SQL
     * @param array $params Paramètres pour la requête
     * @return mixed Résultat de la requête
     */
    public function query(string $query, array $params = []);
    
    /**
     * Prépare une requête SQL
     *
     * @param string $query Requête SQL
     * @return mixed Statement préparé
     */
    public function prepare(string $query);
    
    /**
     * Commence une transaction
     *
     * @return bool True si la transaction a été démarrée
     */
    public function beginTransaction(): bool;
    
    /**
     * Valide une transaction
     *
     * @return bool True si la transaction a été validée
     */
    public function commit(): bool;
    
    /**
     * Annule une transaction
     *
     * @return bool True si la transaction a été annulée
     */
    public function rollBack(): bool;
    
    /**
     * Récupère l'identifiant de la dernière ligne insérée
     *
     * @return string Identifiant de la dernière ligne insérée
     */
    public function lastInsertId(): string;
} 