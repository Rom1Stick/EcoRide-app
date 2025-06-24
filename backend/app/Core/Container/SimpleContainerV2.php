<?php

namespace App\Core\Container;

use App\Core\Container\ContainerInterface;
use App\Core\Container\Exceptions\ContainerException;
use App\Core\Container\Exceptions\NotFoundException;

/**
 * Container d'Injection de Dépendances Simplifié V2
 * 
 * Version simplifiée du container pour la Phase 2 de migration
 */
class SimpleContainerV2 implements ContainerInterface
{
    private array $bindings = [];
    private array $instances = [];
    private array $singletons = [];
    private array $aliases = [];

    public function bind(string $id, $concrete, bool $singleton = false): void
    {
        $this->bindings[$id] = $concrete;
        if ($singleton) {
            $this->singletons[$id] = true;
        }
    }

    public function singleton(string $id, $concrete): void
    {
        $this->bind($id, $concrete, true);
    }

    public function instance(string $id, $instance): void
    {
        $this->instances[$id] = $instance;
    }

    public function get(string $id): mixed
    {
        // Alias
        if (isset($this->aliases[$id])) {
            $id = $this->aliases[$id];
        }

        // Instance déjà créée
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // Service lié
        if (isset($this->bindings[$id])) {
            $concrete = $this->bindings[$id];
            $instance = is_callable($concrete) ? $concrete($this) : new $concrete();
            
            // Stocker si singleton
            if (isset($this->singletons[$id])) {
                $this->instances[$id] = $instance;
            }
            
            return $instance;
        }

        // Auto-wiring simple
        if (class_exists($id)) {
            $instance = new $id();
            return $instance;
        }

        throw new NotFoundException("Service '{$id}' not found");
    }

    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) 
            || isset($this->instances[$id]) 
            || isset($this->aliases[$id])
            || class_exists($id);
    }

    public function make(string $id, array $parameters = []): mixed
    {
        // Version simple sans paramètres pour l'instant
        return new $id();
    }

    public function resolve(string $className, array $parameters = []): mixed
    {
        return $this->make($className, $parameters);
    }

    public function alias(string $alias, string $id): void
    {
        $this->aliases[$alias] = $id;
    }

    public function canResolve(string $id): bool
    {
        return $this->has($id);
    }

    public function forget(string $id): void
    {
        unset($this->bindings[$id], $this->instances[$id], $this->singletons[$id]);
    }

    public function getBindings(): array
    {
        return [
            'bindings' => array_keys($this->bindings),
            'instances' => array_keys($this->instances),
            'singletons' => array_keys($this->singletons),
            'aliases' => $this->aliases
        ];
    }

    /**
     * Configuration pour EcoRide Phase 3
     */
    public function configureForEcoRide(): void
    {
        // === DATABASE ET CORE ===
        $this->singleton('App\Core\Database', 'App\Core\Database');
        $this->singleton('App\Core\Logger', 'App\Core\Logger');
        
        // === REPOSITORIES ===
        $this->bind(
            'App\Domain\Repositories\RideRepositoryInterface',
            'App\Infrastructure\Repositories\MySQLRideRepository'
        );
        
        $this->bind(
            'App\Domain\Repositories\BookingRepositoryInterface',
            'App\Infrastructure\Repositories\MySQLBookingRepository'
        );
        
        $this->bind(
            'App\Domain\Repositories\UserRepositoryInterface',
            'App\Infrastructure\Repositories\MySQLUserRepository'
        );
        
        $this->bind(
            'App\Domain\Repositories\LocationRepositoryInterface',
            'App\Infrastructure\Repositories\MySQLLocationRepository'
        );

        // === SERVICES MÉTIER ===
        $this->singleton('App\Domain\Services\RideManagementService', function($container) {
            $factory = new \App\Infrastructure\Factories\RepositoryFactory();
            $rideRepo = $factory->createRideRepository();
            return new \App\Domain\Services\RideManagementService($rideRepo);
        });
        
        $this->singleton('App\Domain\Services\BookingService', function($container) {
            $factory = new \App\Infrastructure\Factories\RepositoryFactory();
            return new \App\Domain\Services\BookingService(
                $factory->createBookingRepository(),
                $factory->createRideRepository(),
                $factory->createUserRepository(),
                new \App\Services\CreditService($container->get('App\Core\Database')->getMysqlConnection()),
                $container->get('App\Core\Database')->getMysqlConnection()
            );
        });
        
        $this->singleton('App\Domain\Services\UserService', function($container) {
            $factory = new \App\Infrastructure\Factories\RepositoryFactory();
            return new \App\Domain\Services\UserService(
                $factory->createUserRepository()
            );
        });
        
        $this->singleton('App\Domain\Services\LocationService', function($container) {
            $factory = new \App\Infrastructure\Factories\RepositoryFactory();
            return new \App\Domain\Services\LocationService(
                $factory->createLocationRepository()
            );
        });
        
        // === SERVICES LEGACY ===
        $this->singleton('App\Services\CreditService', function($container) {
            return new \App\Services\CreditService(
                $container->get('App\Core\Database')->getMysqlConnection()
            );
        });

        // Phase 4 - Service de recherche avancé
        $this->singleton('App\Domain\Services\SearchService', function() {
            return new \App\Domain\Services\SearchService(
                $this->get('App\Domain\Repositories\RideRepositoryInterface'),
                $this->get('App\Domain\Repositories\LocationRepositoryInterface')
            );
        });
    }
} 