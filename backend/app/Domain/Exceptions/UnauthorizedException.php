<?php

namespace App\Domain\Exceptions;

use Exception;

/**
 * Exception levée lors d'erreurs d'autorisation
 */
class UnauthorizedException extends Exception
{
    public function __construct(string $message = "Accès non autorisé", int $code = 403, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 