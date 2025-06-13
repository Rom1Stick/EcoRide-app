<?php

namespace App\Core\Container;

use App\Core\Container\ContainerInterface;
use App\Core\Container\Exceptions\ContainerException;
use App\Core\Container\Exceptions\NotFoundException;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use Closure;

/**
 * Container d'Injection de Dépendances
 * 
 * Implémentation complète d'un container DI avec résolution automatique
 * des dépendances via Reflection API.
 */
class Container implements ContainerInterface
{
    /**
     * Services enregistrés dans le container
     * @var array
     */
    protected array $bindings = [];

    /**
     * Instances de singletons
     * @var array
     */
    protected array $instances = [];

    /**
     * Alias des services
     * @var array
     */
    protected array $aliases = [];

    /**
     * Services en cours de résolution (prévention de dépendances circulaires)
     * @var array
     */
    protected array $resolving = [];

    /**
     * {@inheritdoc}
     */
    public function get(string $id)
    {
        if (!$this->has($id)) {
            // Tentative de résolution automatique si c'est une classe
            if (class_exists($id)) {
                return $this->resolve($id);
            }
            
            throw new NotFoundException("Service '{$id}' non trouvé dans le container");
        }

        return $this->resolve($id);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || 
               isset($this->instances[$id]) || 
               isset($this->aliases[$id]) ||
               class_exists($id);
    }

    /**
     * {@inheritdoc}
     */
    public function bind(string $id, $concrete, bool $singleton = false): void
    {
        $this->bindings[$id] = [
            'concrete' => $concrete,
            'singleton' => $singleton
        ];

        // Supprimer les instances existantes si on redéfinit un binding
        if (isset($this->instances[$id])) {
            unset($this->instances[$id]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function singleton(string $id, $concrete): void
    {
        $this->bind($id, $concrete, true);
    }

    /**
     * {@inheritdoc}
     */
    public function instance(string $id, $instance): void
    {
        $this->instances[$id] = $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function make(string $id, array $parameters = [])
    {
        return $this->resolve($id, $parameters, false);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $className, array $parameters = [])
    {
        return $this->resolveService($className, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function alias(string $alias, string $id): void
    {
        $this->aliases[$alias] = $id;
    }

    /**
     * {@inheritdoc}
     */
    public function canResolve(string $id): bool
    {
        return $this->has($id);
    }

    /**
     * {@inheritdoc}
     */
    public function forget(string $id): void
    {
        unset($this->bindings[$id], $this->instances[$id], $this->aliases[$id]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Résout un service avec gestion des singletons
     *
     * @param string $id Identifiant du service
     * @param array $parameters Paramètres supplémentaires
     * @param bool $respectSingleton Respecter la configuration singleton
     * @return mixed
     * @throws ContainerException
     */
    protected function resolveService(string $id, array $parameters = [], bool $respectSingleton = true)
    {
        // Vérifier les dépendances circulaires
        if (in_array($id, $this->resolving)) {
            throw new ContainerException("Dépendance circulaire détectée pour le service '{$id}'");
        }

        // Résoudre les alias
        $id = $this->resolveAlias($id);

        // Si c'est une instance déjà créée
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // Marquer comme en cours de résolution
        $this->resolving[] = $id;

        try {
            $concrete = $this->getConcrete($id);
            $instance = $this->buildInstance($concrete, $parameters);

            // Stocker comme singleton si nécessaire
            if ($respectSingleton && $this->isSingleton($id)) {
                $this->instances[$id] = $instance;
            }

            return $instance;

        } finally {
            // Retirer de la liste de résolution
            $this->resolving = array_filter($this->resolving, function($service) use ($id) {
                return $service !== $id;
            });
        }
    }

    /**
     * Résout les alias récursivement
     *
     * @param string $id
     * @return string
     */
    protected function resolveAlias(string $id): string
    {
        if (isset($this->aliases[$id])) {
            return $this->resolveAlias($this->aliases[$id]);
        }

        return $id;
    }

    /**
     * Obtient la définition concrète d'un service
     *
     * @param string $id
     * @return mixed
     */
    protected function getConcrete(string $id)
    {
        if (isset($this->bindings[$id])) {
            return $this->bindings[$id]['concrete'];
        }

        return $id;
    }

    /**
     * Construit une instance du service
     *
     * @param mixed $concrete
     * @param array $parameters
     * @return mixed
     * @throws ContainerException
     */
    protected function buildInstance($concrete, array $parameters = [])
    {
        // Si c'est une closure/factory
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        // Si c'est une chaîne de caractères (nom de classe)
        if (is_string($concrete)) {
            return $this->buildClass($concrete, $parameters);
        }

        // Si c'est déjà un objet
        if (is_object($concrete)) {
            return $concrete;
        }

        throw new ContainerException("Impossible de construire le service avec le type: " . gettype($concrete));
    }

    /**
     * Construit une instance de classe avec résolution automatique des dépendances
     *
     * @param string $className
     * @param array $parameters
     * @return object
     * @throws ContainerException
     */
    protected function buildClass(string $className, array $parameters = []): object
    {
        try {
            $reflection = new ReflectionClass($className);
        } catch (ReflectionException $e) {
            throw new ContainerException("Classe '{$className}' non trouvée: " . $e->getMessage());
        }

        if (!$reflection->isInstantiable()) {
            throw new ContainerException("Classe '{$className}' n'est pas instanciable");
        }

        $constructor = $reflection->getConstructor();

        // Si pas de constructeur, instanciation simple
        if (is_null($constructor)) {
            return new $className;
        }

        // Résoudre les dépendances du constructeur
        $dependencies = $this->resolveDependencies($constructor->getParameters(), $parameters);

        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     * Résout les dépendances d'une méthode
     *
     * @param ReflectionParameter[] $parameters
     * @param array $providedParameters
     * @return array
     * @throws ContainerException
     */
    protected function resolveDependencies(array $parameters, array $providedParameters = []): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();

            // Si le paramètre est fourni explicitement
            if (array_key_exists($name, $providedParameters)) {
                $dependencies[] = $providedParameters[$name];
                continue;
            }

            // Si le paramètre a un type hint
            $type = $parameter->getType();
            if ($type && !$type->isBuiltin()) {
                $typeName = $type->getName();
                $dependencies[] = $this->resolveService($typeName);
                continue;
            }

            // Si le paramètre a une valeur par défaut
            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }

            throw new ContainerException(
                "Impossible de résoudre le paramètre '{$name}' pour la classe"
            );
        }

        return $dependencies;
    }

    /**
     * Vérifie si un service est configuré comme singleton
     *
     * @param string $id
     * @return bool
     */
    protected function isSingleton(string $id): bool
    {
        return isset($this->bindings[$id]) && $this->bindings[$id]['singleton'];
    }

    /**
     * Appelle une méthode avec résolution automatique des dépendances
     *
     * @param mixed $object
     * @param string $method
     * @param array $parameters
     * @return mixed
     * @throws ContainerException
     */
    public function call($object, string $method, array $parameters = [])
    {
        try {
            $reflection = new ReflectionClass($object);
            $methodReflection = $reflection->getMethod($method);
            
            $dependencies = $this->resolveDependencies(
                $methodReflection->getParameters(), 
                $parameters
            );

            return $methodReflection->invokeArgs($object, $dependencies);

        } catch (ReflectionException $e) {
            throw new ContainerException("Erreur lors de l'appel de la méthode '{$method}': " . $e->getMessage());
        }
    }

    /**
     * Enregistre plusieurs services à partir d'un tableau de configuration
     *
     * @param array $services
     * @return void
     */
    public function registerServices(array $services): void
    {
        foreach ($services as $id => $config) {
            if (is_string($config)) {
                // Configuration simple: 'ServiceInterface' => 'ServiceImplementation'
                $this->bind($id, $config);
            } elseif (is_array($config)) {
                // Configuration avancée
                $concrete = $config['class'] ?? $config['concrete'] ?? $id;
                $singleton = $config['singleton'] ?? false;
                
                $this->bind($id, $concrete, $singleton);

                // Enregistrer les alias si définis
                if (isset($config['aliases'])) {
                    foreach ((array) $config['aliases'] as $alias) {
                        $this->alias($alias, $id);
                    }
                }
            }
        }
    }

    /**
     * Obtient les statistiques du container
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'bindings_count' => count($this->bindings),
            'instances_count' => count($this->instances),
            'aliases_count' => count($this->aliases),
            'singletons_count' => count(array_filter($this->bindings, function($binding) {
                return $binding['singleton'];
            })),
            'memory_usage' => memory_get_usage(),
            'resolving' => $this->resolving
        ];
    }

    /**
     * Vide complètement le container
     *
     * @return void
     */
    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
        $this->aliases = [];
        $this->resolving = [];
    }
} 