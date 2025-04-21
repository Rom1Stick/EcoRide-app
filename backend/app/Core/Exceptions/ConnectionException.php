<?php

namespace App\Core\Exceptions;

/**
 * Exception pour les problèmes de connexion aux bases de données
 */
class ConnectionException extends DALException
{
    /**
     * Constructeur pour les erreurs de connexion
     *
     * @param string $resourceType Type de ressource concernée (USER, TRIP, etc.)
     * @param string $reason Raison de l'échec de connexion
     * @param int $code Code d'erreur
     * @param \Throwable|null $previous Exception précédente
     */
    public function __construct(
        string $resourceType, 
        string $reason, 
        int $code = 0, 
        \Throwable $previous = null
    ) {
        parent::__construct($resourceType, 'CONNECTION', $reason, $code, $previous);
    }
} 