<?php

namespace App\Core\Container;

use App\Core\Container\ContainerInterface;
use App\Core\Container\Exceptions\ContainerException;
use App\Core\Container\Exceptions\NotFoundException;
use ReflectionClass;
use ReflectionParameter;
use ReflectionException;
use Closure;

/**
 * Container d'Injection de Dépendances V2 - Auto-wiring
 * 
 * Container moderne avec résolution automatique des dépendances,
 * support des singletons et configuration flexible.
 */
class ContainerV2 implements ContainerInterface
{
    /**
     * Services enregistrés dans le container
     */
    private array $services = [];
    
    /**
     * Instances singleton
     */
    private array $instances = [];
    
    /**
     * Bindings d'interfaces vers implémentations concrètes
     */
    private array $bindings = [];
    
    /**
     * Services marqués comme singletons
     */
    private array $singletons = [];

    /**
     * Enregistrer un service avec une factory
     */
    public function bind(string $id, $concrete, bool $singleton = false): void
    {
        $concrete = $concrete ?? $id;
        
        $this->services[$id] = $concrete;
        
        if ($singleton) {
            $this->singletons[$id] = true;
        }
    }

    /**
     * Enregistrer un service comme singleton
     */
    public function singleton(string $id, $concrete): void
    {
        $this->bind($id, $concrete, true);
    }

    /**
     * Enregistrer une instance déjà créée
     */
    public function instance(string $id, $instance): void
    {
        $this->instances[$id] = $instance;
    }

    /**
     * Bind une interface vers une implémentation concrète
     */
    public function bindInterface(string $interface, string $implementation): void
    {
        $this->bindings[$interface] = $implementation;
    }

    /**
     * Résoudre un service du container
     */
    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new NotFoundException("Service '{$id}' not found in container.");
        }

        // Instance déjà créée
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // Service singleton déjà résolu
        if (isset($this->singletons[$id]) && isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // Résoudre le service
        $service = $this->resolve($id);

        // Stocker en cache si singleton
        if (isset($this->singletons[$id])) {
            $this->instances[$id] = $service;
        }

        return $service;
    }

    /**
     * Vérifier si un service existe
     */
    public function has(string $id): bool
    {
        return isset($this->services[$id]) 
            || isset($this->instances[$id])
            || isset($this->bindings[$id])
            || class_exists($id)
            || interface_exists($id);
    }

    /**
     * Résoudre un service avec auto-wiring
     */
    public function resolve(string $className, array $parameters = []): mixed
    {
        try {
            // Service enregistré avec factory
            if (isset($this->services[$id])) {
                $concrete = $this->services[$id];
                
                if ($concrete instanceof Closure) {
                    return $concrete($this);
                }
                
                if (is_string($concrete)) {
                    return $this->build($concrete);
                }
            }

            // Interface binding
            if (isset($this->bindings[$id])) {
                return $this->resolve($this->bindings[$id]);
            }

            // Auto-wiring direct
            return $this->build($id);

        } catch (ReflectionException $e) {
            throw new ContainerException("Cannot resolve '{$id}': " . $e->getMessage());
        }
    }

    /**
     * Construire une instance avec auto-wiring
     */
    private function build(string $concrete): object
    {
        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new ContainerException("Class '{$concrete}' is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $parameters = $constructor->getParameters();
        $dependencies = $this->resolveDependencies($parameters);

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Résoudre les dépendances du constructeur
     */
    private function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $this->resolveDependency($parameter);
            $dependencies[] = $dependency;
        }

        return $dependencies;
    }

    /**
     * Résoudre une dépendance spécifique
     */
    private function resolveDependency(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
            // Paramètre primitif - essayer valeur par défaut
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }
            
            throw new ContainerException(
                "Cannot resolve primitive parameter '{$parameter->getName()}' without default value."
            );
        }

        $className = $type->getName();

        try {
            return $this->get($className);
        } catch (NotFoundException $e) {
            // Essayer valeur par défaut si paramètre optionnel
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }
            
            if ($parameter->allowsNull()) {
                return null;
            }

            throw new ContainerException(
                "Cannot resolve dependency '{$className}' for parameter '{$parameter->getName()}'."
            );
        }
    }

    /**
     * Créer une instance sans l'enregistrer dans le container
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        if (isset($this->bindings[$abstract])) {
            $abstract = $this->bindings[$abstract];
        }

        try {
            $reflector = new ReflectionClass($abstract);
            
            if (!$reflector->isInstantiable()) {
                throw new ContainerException("Class '{$abstract}' is not instantiable.");
            }

            $constructor = $reflector->getConstructor();

            if (is_null($constructor)) {
                return new $abstract;
            }

            $constructorParameters = $constructor->getParameters();
            $dependencies = [];

            foreach ($constructorParameters as $i => $parameter) {
                // Utiliser paramètre fourni si disponible
                if (isset($parameters[$i])) {
                    $dependencies[] = $parameters[$i];
                    continue;
                }

                // Sinon résoudre automatiquement
                $dependencies[] = $this->resolveDependency($parameter);
            }

            return $reflector->newInstanceArgs($dependencies);

        } catch (ReflectionException $e) {
            throw new ContainerException("Cannot make '{$abstract}': " . $e->getMessage());
        }
    }

    /**
     * Obtenir tous les services enregistrés
     */
    public function getBindings(): array
    {
        return [
            'services' => array_keys($this->services),
            'instances' => array_keys($this->instances),
            'bindings' => $this->bindings,
            'singletons' => array_keys($this->singletons)
        ];
    }

    /**
     * Effacer toutes les instances singleton (utile pour les tests)
     */
    public function flush(): void
    {
        $this->instances = [];
    }

    /**
     * Configurer le container avec les bindings de l'application
     */
    public function configure(): void
    {
        // Configuration des repositories
        $this->bindInterface(
            'App\Domain\Repositories\RideRepositoryInterface',
            'App\Infrastructure\Repositories\MySQLRideRepository'
        );
        
        $this->bindInterface(
            'App\Domain\Repositories\LocationRepositoryInterface', 
            'App\Infrastructure\Repositories\MySQLLocationRepository'
        );

        // Services métier en singleton
        $this->singleton('App\Domain\Services\RideManagementService');
        $this->singleton('App\Services\RideService');
        $this->singleton('App\Services\CreditService');

        // Core services en singleton
        $this->singleton('App\Core\Database');
        $this->singleton('App\Core\Logger');
        $this->singleton('App\Core\Validator');

        // Factory pour repositories
        $this->bind('App\Infrastructure\Factories\RepositoryFactory', function($container) {
            return \App\Infrastructure\Factories\RepositoryFactory::createFromLegacyDatabase(
                $container->get('App\Core\Database'),
                $container->get('App\Core\Logger')
            );
        });
    }
} 