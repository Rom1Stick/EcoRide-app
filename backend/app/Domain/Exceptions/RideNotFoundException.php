<?php

namespace App\Domain\Exceptions;

use Exception;

/**
 * Exception levée quand un trajet n'est pas trouvé
 */
class RideNotFoundException extends Exception
{
    public function __construct(string $message = "Trajet non trouvé", int $code = 404, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 