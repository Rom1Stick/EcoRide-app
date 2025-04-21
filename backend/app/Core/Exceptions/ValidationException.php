<?php

namespace App\Core\Exceptions;

/**
 * Exception pour les erreurs de validation des données
 */
class ValidationException extends DALException
{
    private array $errors;
    
    /**
     * Constructeur pour les erreurs de validation
     *
     * @param string $resourceType Type de ressource concernée (USER, TRIP, etc.)
     * @param array $errors Tableau associatif des erreurs de validation
     * @param string $reason Raison générale de l'échec (optionnel)
     * @param int $code Code d'erreur
     * @param \Throwable|null $previous Exception précédente
     */
    public function __construct(
        string $resourceType, 
        array $errors,
        string $reason = "Validation failed", 
        int $code = 0, 
        \Throwable $previous = null
    ) {
        $this->errors = $errors;
        parent::__construct($resourceType, 'VALIDATION', $reason, $code, $previous);
    }
    
    /**
     * Récupère les erreurs détaillées de validation
     *
     * @return array
     */
    public function getValidationErrors(): array
    {
        return $this->errors;
    }
} 