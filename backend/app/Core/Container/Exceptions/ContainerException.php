<?php

namespace App\Core\Container\Exceptions;

use Psr\Container\ContainerExceptionInterface;
use Exception;

/**
 * Exception générale du container d'injection de dépendances
 */
class ContainerException extends Exception implements ContainerExceptionInterface
{
    //
} 