<?php

namespace App\Infrastructure\Factories;

use App\Domain\Repositories\RideRepositoryInterface;
use App\Infrastructure\Repositories\MySQLRideRepository;
use App\Infrastructure\Repositories\MySQLLocationRepository;
use App\Infrastructure\Persistence\RideMapper;
use App\Infrastructure\Persistence\UserMapper;
use App\Infrastructure\Persistence\LocationMapper;
use App\Infrastructure\Persistence\VehicleMapper;
use App\Core\Database\DatabaseInterface;
use App\Infrastructure\Database\DatabaseAdapter;
use App\Core\Database;
use App\Core\Logger;

/**
 * Factory pour créer les repositories avec leurs dépendances
 */
class RepositoryFactory
{
    private DatabaseInterface $database;
    private Logger $logger;

    public function __construct(DatabaseInterface $database, Logger $logger)
    {
        $this->database = $database;
        $this->logger = $logger;
    }

    /**
     * Crée une instance de RepositoryFactory en utilisant la classe Database existante
     */
    public static function createFromLegacyDatabase(Database $legacyDatabase, Logger $logger): self
    {
        $databaseAdapter = new DatabaseAdapter($legacyDatabase);
        return new self($databaseAdapter, $logger);
    }

    /**
     * Crée une instance du repository de trajets
     */
    public function createRideRepository(): RideRepositoryInterface
    {
        $locationMapper = new LocationMapper();
        $userMapper = new UserMapper();
        $vehicleMapper = new VehicleMapper();
        $rideMapper = new RideMapper($userMapper, $locationMapper, $vehicleMapper);

        return new MySQLRideRepository(
            $this->database,
            $this->logger,
            $rideMapper,
            $userMapper,
            $locationMapper
        );
    }

    /**
     * Crée une instance du repository de lieux
     */
    public function createLocationRepository(): MySQLLocationRepository
    {
        $locationMapper = new LocationMapper();

        return new MySQLLocationRepository(
            $this->database,
            $this->logger,
            $locationMapper
        );
    }

    /**
     * Crée tous les mappers nécessaires
     */
    public function createMappers(): array
    {
        $locationMapper = new LocationMapper();
        $userMapper = new UserMapper();
        $vehicleMapper = new VehicleMapper();
        $rideMapper = new RideMapper($userMapper, $locationMapper, $vehicleMapper);

        return [
            'location' => $locationMapper,
            'user' => $userMapper,
            'vehicle' => $vehicleMapper,
            'ride' => $rideMapper
        ];
    }
} 