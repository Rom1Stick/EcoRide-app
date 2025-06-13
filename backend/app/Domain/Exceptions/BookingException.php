<?php

namespace App\Domain\Exceptions;

use Exception;

/**
 * Exception levée lors d'erreurs de réservation
 */
class BookingException extends Exception
{
    public function __construct(string $message = "Erreur de réservation", int $code = 400, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 