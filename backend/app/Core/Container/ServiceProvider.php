<?php

namespace App\Core\Container;

use App\Core\Container\ContainerInterface;
use App\Core\Logger;
use App\Core\Database\DatabaseInterface;
use App\Infrastructure\Database\DatabaseAdapter;
use App\Infrastructure\Factories\RepositoryFactory;
use App\Domain\Repositories\RideRepositoryInterface;
use App\Domain\Services\RideManagementService;
use App\Infrastructure\Repositories\MySQLRideRepository;
use App\Infrastructure\Repositories\MySQLLocationRepository;

/**
 * Service Provider pour EcoRide
 * 
 * Enregistre tous les services de l'application dans le container DI
 */
class ServiceProvider
{
    /**
     * Container d'injection de dépendances
     */
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Enregistre tous les services de l'application
     */
    public function register(): void
    {
        $this->registerCore();
        $this->registerRepositories();
        $this->registerServices();
        $this->registerControllers();
    }

    /**
     * Enregistre les services core (base de données, logger, etc.)
     */
    private function registerCore(): void
    {
        // Logger singleton
        $this->container->singleton(Logger::class, function(ContainerInterface $container) {
            $logPath = BASE_PATH . '/logs/ecoride.log';
            return new Logger($logPath);
        });

        // Alias pour le logger
        $this->container->alias('logger', Logger::class);

        // Database adapter (utilise la classe legacy Database)
        $this->container->singleton(DatabaseInterface::class, function(ContainerInterface $container) {
            global $app; // Accès à l'application legacy
            return new DatabaseAdapter($app->getDatabase());
        });

        // Alias pour la base de données
        $this->container->alias('database', DatabaseInterface::class);

        // Repository Factory
        $this->container->singleton(RepositoryFactory::class, function(ContainerInterface $container) {
            return new RepositoryFactory(
                $container->get(DatabaseInterface::class),
                $container->get(Logger::class)
            );
        });
    }

    /**
     * Enregistre les repositories
     */
    private function registerRepositories(): void
    {
        // Ride Repository
        $this->container->bind(
            RideRepositoryInterface::class,
            function(ContainerInterface $container) {
                $factory = $container->get(RepositoryFactory::class);
                return $factory->createRideRepository();
            },
            true // singleton
        );

        // Location Repository
        $this->container->singleton(
            MySQLLocationRepository::class,
            function(ContainerInterface $container) {
                $factory = $container->get(RepositoryFactory::class);
                return $factory->createLocationRepository();
            }
        );

        // Alias pour faciliter l'usage
        $this->container->alias('ride.repository', RideRepositoryInterface::class);
        $this->container->alias('location.repository', MySQLLocationRepository::class);
    }

    /**
     * Enregistre les services métier
     */
    private function registerServices(): void
    {
        // Ride Management Service
        $this->container->singleton(
            RideManagementService::class,
            function(ContainerInterface $container) {
                return new RideManagementService(
                    $container->get(RideRepositoryInterface::class)
                );
            }
        );

        // Alias
        $this->container->alias('ride.service', RideManagementService::class);
    }

    /**
     * Enregistre les contrôleurs refactorisés
     */
    private function registerControllers(): void
    {
        // Les contrôleurs ne sont pas des singletons pour permettre
        // l'instanciation multiple si nécessaire
        
        $this->container->bind(
            \App\Controllers\Refactored\RideControllerV2::class,
            \App\Controllers\Refactored\RideControllerV2::class
        );

        $this->container->bind(
            \App\Controllers\Refactored\SearchControllerV2::class,
            \App\Controllers\Refactored\SearchControllerV2::class
        );

        $this->container->bind(
            \App\Controllers\Refactored\LocationControllerV2::class,
            \App\Controllers\Refactored\LocationControllerV2::class
        );
    }

    /**
     * Enregistre des services de développement/debug
     */
    public function registerDevServices(): void
    {
        if (env('APP_ENV', 'production') !== 'production') {
            // Service de profiling
            $this->container->singleton('profiler', function() {
                return new class {
                    private array $timers = [];
                    
                    public function start(string $name): void {
                        $this->timers[$name] = microtime(true);
                    }
                    
                    public function stop(string $name): float {
                        if (!isset($this->timers[$name])) {
                            return 0.0;
                        }
                        return microtime(true) - $this->timers[$name];
                    }
                    
                    public function getTimers(): array {
                        return $this->timers;
                    }
                };
            });

            // Service de debug
            $this->container->singleton('debug', function(ContainerInterface $container) {
                return new class($container) {
                    private ContainerInterface $container;
                    
                    public function __construct(ContainerInterface $container) {
                        $this->container = $container;
                    }
                    
                    public function getContainerStats(): array {
                        return $this->container->getStats();
                    }
                    
                    public function dumpContainer(): void {
                        echo "<h3>Container Stats:</h3>";
                        echo "<pre>" . print_r($this->getContainerStats(), true) . "</pre>";
                    }
                };
            });
        }
    }

    /**
     * Configuration avancée des services avec paramètres
     */
    public function configureWithParams(array $config): void
    {
        // Configuration du logger avec paramètres
        if (isset($config['logger'])) {
            $logConfig = $config['logger'];
            
            $this->container->singleton(Logger::class, function() use ($logConfig) {
                $logPath = $logConfig['path'] ?? BASE_PATH . '/logs/ecoride.log';
                $level = $logConfig['level'] ?? 'info';
                
                $logger = new Logger($logPath);
                // Configuration du niveau de log si la méthode existe
                if (method_exists($logger, 'setLevel')) {
                    $logger->setLevel($level);
                }
                return $logger;
            });
        }

        // Configuration de la base de données avec paramètres
        if (isset($config['database'])) {
            $dbConfig = $config['database'];
            
            $this->container->singleton(DatabaseInterface::class, function() use ($dbConfig) {
                // Utilisation des paramètres de configuration personnalisés
                // si nécessaire pour créer une nouvelle connexion
                global $app;
                return new DatabaseAdapter($app->getDatabase());
            });
        }
    }

    /**
     * Enregistre des services tiers/externes
     */
    public function registerExternalServices(): void
    {
        // Service de cache (Redis, Memcached, etc.)
        $this->container->singleton('cache', function() {
            // Pour l'instant, cache simple en mémoire
            return new class {
                private array $cache = [];
                
                public function get(string $key, $default = null) {
                    return $this->cache[$key] ?? $default;
                }
                
                public function set(string $key, $value, int $ttl = 3600): void {
                    $this->cache[$key] = [
                        'value' => $value,
                        'expires' => time() + $ttl
                    ];
                }
                
                public function has(string $key): bool {
                    if (!isset($this->cache[$key])) {
                        return false;
                    }
                    
                    if ($this->cache[$key]['expires'] < time()) {
                        unset($this->cache[$key]);
                        return false;
                    }
                    
                    return true;
                }
                
                public function forget(string $key): void {
                    unset($this->cache[$key]);
                }
            };
        });

        // Service de notification (email, SMS, push)
        $this->container->singleton('notification', function() {
            return new class {
                public function sendEmail(string $to, string $subject, string $body): bool {
                    // Implémentation simple pour démo
                    error_log("EMAIL TO $to: $subject");
                    return true;
                }
                
                public function sendSMS(string $phone, string $message): bool {
                    // Implémentation simple pour démo
                    error_log("SMS TO $phone: $message");
                    return true;
                }
            };
        });
    }

    /**
     * Enregistre tous les services en une fois
     */
    public function registerAll(array $config = []): void
    {
        $this->register();
        $this->registerDevServices();
        $this->registerExternalServices();
        
        if (!empty($config)) {
            $this->configureWithParams($config);
        }
    }
} 