<?php

namespace App\Core\Exceptions;

/**
 * Exception pour les erreurs lors des opérations de persistance
 */
class PersistenceException extends DALException
{
    private ?int $entityId;
    
    /**
     * Constructeur pour les erreurs de persistance
     *
     * @param string $resourceType Type de ressource concernée (USER, TRIP, etc.)
     * @param string $action Action CRUD qui a échoué (CREATE, READ, UPDATE, DELETE)
     * @param string $reason Raison de l'échec
     * @param int|null $entityId Identifiant de l'entité concernée (si applicable)
     * @param int $code Code d'erreur
     * @param \Throwable|null $previous Exception précédente
     */
    public function __construct(
        string $resourceType, 
        string $action, 
        string $reason, 
        ?int $entityId = null,
        int $code = 0, 
        \Throwable $previous = null
    ) {
        $this->entityId = $entityId;
        parent::__construct($resourceType, $action, $reason, $code, $previous);
    }
    
    /**
     * Récupère l'identifiant de l'entité concernée par l'erreur
     *
     * @return int|null
     */
    public function getEntityId(): ?int
    {
        return $this->entityId;
    }
} 