<?php

namespace App\Core\Exceptions;

/**
 * Exception pour les erreurs de requêtes ou problèmes de filtrage
 */
class QueryException extends DALException
{
    private array $queryParams;
    
    /**
     * Constructeur pour les erreurs de requête
     *
     * @param string $resourceType Type de ressource concernée (USER, TRIP, etc.)
     * @param string $reason Raison de l'échec
     * @param array $queryParams Paramètres de la requête qui a échoué
     * @param int $code Code d'erreur
     * @param \Throwable|null $previous Exception précédente
     */
    public function __construct(
        string $resourceType, 
        string $reason, 
        array $queryParams = [],
        int $code = 0, 
        \Throwable $previous = null
    ) {
        $this->queryParams = $queryParams;
        parent::__construct($resourceType, 'QUERY', $reason, $code, $previous);
    }
    
    /**
     * Récupère les paramètres de la requête qui a échoué
     *
     * @return array
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }
} 