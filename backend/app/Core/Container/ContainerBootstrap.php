<?php

namespace App\Core\Container;

use App\Core\Container\Container;
use App\Core\Container\ServiceProvider;
use App\Core\Application;

/**
 * Bootstrap du Container d'Injection de Dépendances
 * 
 * Initialise et configure le container pour l'application EcoRide
 */
class ContainerBootstrap
{
    private static ?Container $instance = null;
    private static bool $isBootstrapped = false;

    /**
     * Initialise le container avec tous les services EcoRide
     *
     * @param array $config Configuration personnalisée
     * @return Container
     */
    public static function bootstrap(array $config = []): Container
    {
        if (self::$isBootstrapped && self::$instance) {
            return self::$instance;
        }

        // Créer le container
        $container = new Container();
        
        // Créer et enregistrer le service provider
        $serviceProvider = new ServiceProvider($container);
        
        // Enregistrement des services selon l'environnement
        $environment = $config['environment'] ?? env('APP_ENV', 'production');
        
        if ($environment === 'production') {
            $serviceProvider->register();
        } else {
            // En développement, enregistrer tous les services (debug, profiler, etc.)
            $serviceProvider->registerAll($config);
        }

        // Enregistrer le container lui-même pour l'auto-injection
        $container->instance(ContainerInterface::class, $container);
        $container->alias('container', ContainerInterface::class);

        // Stocker l'instance
        self::$instance = $container;
        self::$isBootstrapped = true;

        return $container;
    }

    /**
     * Obtient l'instance du container (singleton)
     *
     * @return Container|null
     */
    public static function getInstance(): ?Container
    {
        return self::$instance;
    }

    /**
     * Intègre le container dans l'Application EcoRide existante
     *
     * @param Application $app Application EcoRide
     * @param array $config Configuration
     * @return void
     */
    public static function integrateWithLegacyApp(Application $app, array $config = []): void
    {
        $container = self::bootstrap($config);

        // Enregistrer l'application legacy dans le container
        $container->instance(Application::class, $app);
        $container->alias('app', Application::class);

        // Méthode helper globale pour accéder au container
        if (!function_exists('container')) {
            /**
             * Helper global pour accéder au container
             *
             * @param string|null $service Service à résoudre
             * @return mixed
             */
            function container(string $service = null)
            {
                $container = ContainerBootstrap::getInstance();
                
                if ($service === null) {
                    return $container;
                }
                
                return $container->get($service);
            }
        }

        // Méthode helper pour l'injection de dépendances
        if (!function_exists('resolve')) {
            /**
             * Helper pour résoudre un service avec ses dépendances
             *
             * @param string $className
             * @param array $parameters
             * @return mixed
             */
            function resolve(string $className, array $parameters = [])
            {
                return ContainerBootstrap::getInstance()->resolve($className, $parameters);
            }
        }
    }

    /**
     * Crée un contrôleur V3 avec injection automatique des dépendances
     *
     * @param string $controllerClass
     * @return mixed
     */
    public static function createController(string $controllerClass)
    {
        $container = self::getInstance();
        
        if (!$container) {
            throw new \RuntimeException('Container non initialisé. Appelez bootstrap() d\'abord.');
        }

        return $container->resolve($controllerClass);
    }

    /**
     * Configuration rapide pour différents environnements
     *
     * @param string $environment
     * @return array
     */
    public static function getEnvironmentConfig(string $environment): array
    {
        return match($environment) {
            'development', 'dev' => [
                'environment' => 'development',
                'logger' => [
                    'level' => 'debug',
                    'path' => BASE_PATH . '/logs/dev.log'
                ],
                'cache' => [
                    'ttl' => 60, // Cache court en dev
                    'enabled' => true
                ]
            ],
            
            'testing', 'test' => [
                'environment' => 'testing',
                'logger' => [
                    'level' => 'error',
                    'path' => BASE_PATH . '/logs/test.log'
                ],
                'cache' => [
                    'ttl' => 0, // Pas de cache en test
                    'enabled' => false
                ]
            ],
            
            'production', 'prod' => [
                'environment' => 'production',
                'logger' => [
                    'level' => 'warning',
                    'path' => BASE_PATH . '/logs/prod.log'
                ],
                'cache' => [
                    'ttl' => 3600, // Cache long en prod
                    'enabled' => true
                ]
            ],
            
            default => [
                'environment' => 'production'
            ]
        };
    }

    /**
     * Migre progressivement les routes vers les contrôleurs V3
     *
     * @param array $routes Routes à migrer
     * @return void
     */
    public static function setupProgressiveMigration(array $routes = []): void
    {
        $container = self::getInstance();
        
        if (!$container) {
            throw new \RuntimeException('Container non initialisé.');
        }

        // Routes par défaut pour la migration
        $defaultRoutes = [
            '/api/v3/rides' => \App\Controllers\Refactored\RideControllerV3::class,
            '/api/v3/search' => \App\Controllers\Refactored\SearchControllerV3::class,
            '/api/v3/locations' => \App\Controllers\Refactored\LocationControllerV3::class,
        ];

        $routesToMigrate = array_merge($defaultRoutes, $routes);

        // Enregistrer les routes dans le container pour le routeur
        $container->instance('v3.routes', $routesToMigrate);

        // Helper pour créer un middleware de migration
        $container->singleton('migration.middleware', function() use ($container) {
            return new class($container) {
                private ContainerInterface $container;
                
                public function __construct(ContainerInterface $container) {
                    $this->container = $container;
                }
                
                public function handle(string $path, string $method = 'GET') {
                    $routes = $this->container->get('v3.routes');
                    
                    foreach ($routes as $route => $controllerClass) {
                        if (strpos($path, $route) === 0) {
                            // Utiliser le contrôleur V3
                            return $this->container->resolve($controllerClass);
                        }
                    }
                    
                    // Utiliser le contrôleur legacy
                    return null;
                }
            };
        });
    }

    /**
     * Réinitialise le container (utile pour les tests)
     *
     * @return void
     */
    public static function reset(): void
    {
        if (self::$instance) {
            self::$instance->flush();
        }
        
        self::$instance = null;
        self::$isBootstrapped = false;
    }

    /**
     * Valide la configuration du container
     *
     * @return array Résultats de validation
     */
    public static function validateConfiguration(): array
    {
        $container = self::getInstance();
        $results = [
            'status' => 'ok',
            'services' => [],
            'errors' => []
        ];

        if (!$container) {
            $results['status'] = 'error';
            $results['errors'][] = 'Container non initialisé';
            return $results;
        }

        // Services critiques à vérifier
        $criticalServices = [
            'logger' => \App\Core\Logger::class,
            'database' => \App\Core\Database\DatabaseInterface::class,
            'ride.repository' => \App\Domain\Repositories\RideRepositoryInterface::class,
            'ride.service' => \App\Domain\Services\RideManagementService::class
        ];

        foreach ($criticalServices as $alias => $className) {
            try {
                if ($container->has($alias)) {
                    $service = $container->get($alias);
                    $results['services'][$alias] = [
                        'status' => 'ok',
                        'class' => get_class($service),
                        'expected' => $className
                    ];
                } else {
                    $results['services'][$alias] = [
                        'status' => 'missing',
                        'expected' => $className
                    ];
                    $results['errors'][] = "Service manquant: {$alias}";
                }
            } catch (\Exception $e) {
                $results['services'][$alias] = [
                    'status' => 'error',
                    'error' => $e->getMessage(),
                    'expected' => $className
                ];
                $results['errors'][] = "Erreur service {$alias}: " . $e->getMessage();
            }
        }

        if (!empty($results['errors'])) {
            $results['status'] = 'error';
        }

        return $results;
    }

    /**
     * Génère un rapport de performance du container
     *
     * @return array
     */
    public static function getPerformanceReport(): array
    {
        $container = self::getInstance();
        
        if (!$container) {
            return ['error' => 'Container non initialisé'];
        }

        $stats = $container->getStats();
        
        // Calculer des métriques additionnelles
        $singletonRatio = $stats['singletons_count'] > 0 
            ? round(($stats['singletons_count'] / $stats['bindings_count']) * 100, 2)
            : 0;

        return [
            'container_stats' => $stats,
            'performance_metrics' => [
                'singleton_ratio' => $singletonRatio . '%',
                'memory_usage_mb' => round($stats['memory_usage'] / 1024 / 1024, 2),
                'services_per_mb' => round($stats['bindings_count'] / ($stats['memory_usage'] / 1024 / 1024), 2)
            ],
                         'recommendations' => self::generateRecommendations($stats)
        ];
    }

    /**
     * Génère des recommandations d'optimisation
     *
     * @param array $stats
     * @return array
     */
    private static function generateRecommendations(array $stats): array
    {
        $recommendations = [];

        if ($stats['singletons_count'] / $stats['bindings_count'] < 0.3) {
            $recommendations[] = 'Considérer d\'enregistrer plus de services comme singletons pour améliorer les performances';
        }

        if ($stats['memory_usage'] > 50 * 1024 * 1024) { // > 50MB
            $recommendations[] = 'Usage mémoire élevé - vérifier les fuites mémoire potentielles';
        }

        if ($stats['instances_count'] > 100) {
            $recommendations[] = 'Beaucoup d\'instances créées - optimiser la gestion du cycle de vie';
        }

        return $recommendations;
    }
} 