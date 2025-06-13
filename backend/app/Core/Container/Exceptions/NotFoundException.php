<?php

namespace App\Core\Container\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Exception pour les services non trouvés dans le container
 */
class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{
    //
} 