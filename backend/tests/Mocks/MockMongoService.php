<?php

namespace Tests\Mocks;

use MongoDB\BSON\ObjectId;
use MongoDB\Collection;

/**
 * Service MongoDB de base pour les mocks
 * Cette classe pourra être étendue par des services spécifiques pour les tests
 */
class MockMongoService
{
    /**
     * @var object Collection MongoDB mockée ou compatible
     */
    protected $collection;

    /**
     * Constructeur
     * 
     * @param object $mockCollection Collection mockée à utiliser (compatible avec l'interface MongoDB\Collection)
     */
    public function __construct($mockCollection)
    {
        $this->collection = $mockCollection;
    }

    /**
     * Transforme un ID en ObjectId MongoDB
     * 
     * @param string|ObjectId $id
     * @return ObjectId
     */
    protected function toObjectId($id): ObjectId
    {
        if ($id instanceof ObjectId) {
            return $id;
        }
        
        return new ObjectId($id);
    }
} 