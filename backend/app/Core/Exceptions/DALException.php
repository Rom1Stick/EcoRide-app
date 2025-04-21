<?php

namespace App\Core\Exceptions;

/**
 * Exception de base pour la couche d'accès aux données
 * Toutes les exceptions spécifiques à la DAL en dérivent
 */
class DALException extends \Exception
{
    protected string $resourceType;
    protected string $action;
    
    /**
     * Constructeur avec format standard de message
     *
     * @param string $resourceType Type de ressource concernée (USER, TRIP, etc.)
     * @param string $action Action qui a échoué (CREATE, READ, UPDATE, DELETE, etc.)
     * @param string $reason Raison de l'échec
     * @param int $code Code d'erreur
     * @param \Throwable|null $previous Exception précédente
     */
    public function __construct(
        string $resourceType, 
        string $action, 
        string $reason, 
        int $code = 0, 
        \Throwable $previous = null
    ) {
        $this->resourceType = $resourceType;
        $this->action = $action;
        
        $message = sprintf("[%s] - %s - %s", $resourceType, $action, $reason);
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Récupère le type de ressource concernée
     *
     * @return string
     */
    public function getResourceType(): string
    {
        return $this->resourceType;
    }
    
    /**
     * Récupère l'action qui a échoué
     *
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }
} 