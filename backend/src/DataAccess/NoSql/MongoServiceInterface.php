<?php

namespace App\DataAccess\NoSql;

use App\DataAccess\NoSql\Service\MongoServiceInterface as ServiceMongoServiceInterface;
use MongoDB\BSON\ObjectId;

/**
 * Interface MongoServiceInterface
 * 
 * Alias pour compatibilité entre les différents namespaces utilisés dans l'application
 */
interface MongoServiceInterface extends ServiceMongoServiceInterface
{
} 