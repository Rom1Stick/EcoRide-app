<?php

namespace App\Core;

/**
 * Classe Router
 *
 * Cette classe gère les routes de l'application et dispatch les requêtes
 * vers les contrôleurs appropriés
 */
class Router
{
    /**
     * Liste des routes enregistrées
     *
     * @var array
     */
    private array $routes = [];

    /**
     * Liste des middlewares globaux
     *
     * @var array
     */
    private array $middlewares = [];

    /**
     * Enregistre une route GET
     *
     * @param  string $path    Chemin de la route
     * @param  string $handler Nom du contrôleur et de la méthode (Controller@method)
     * @return Route
     */
    public function get(string $path, string $handler): Route
    {
        return $this->addRoute('GET', $path, $handler);
    }

    /**
     * Enregistre une route POST
     *
     * @param  string $path    Chemin de la route
     * @param  string $handler Nom du contrôleur et de la méthode (Controller@method)
     * @return Route
     */
    public function post(string $path, string $handler): Route
    {
        return $this->addRoute('POST', $path, $handler);
    }

    /**
     * Enregistre une route PUT
     *
     * @param  string $path    Chemin de la route
     * @param  string $handler Nom du contrôleur et de la méthode (Controller@method)
     * @return Route
     */
    public function put(string $path, string $handler): Route
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Enregistre une route DELETE
     *
     * @param  string $path    Chemin de la route
     * @param  string $handler Nom du contrôleur et de la méthode (Controller@method)
     * @return Route
     */
    public function delete(string $path, string $handler): Route
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Ajoute une route au routeur
     *
     * @param  string $method  Méthode
     *                         HTTP
     * @param  string $path    Chemin de la route
     * @param  string $handler Nom du contrôleur et de la méthode (Controller@method)
     * @return Route
     */
    private function addRoute(string $method, string $path, string $handler): Route
    {
        // Créer un nouvel objet Route
        $route = new Route($method, $path, $handler);

        // Ajouter la route à la liste des routes
        $this->routes[] = $route;

        // Retourner la route pour permettre le chaînage des appels
        return $route;
    }

    /**
     * Ajoute un middleware global
     *
     * @param  string $middleware Nom du middleware
     * @return self
     */
    public function middleware(string $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Dispatche une requête vers le contrôleur approprié
     *
     * @param  string $method Méthode HTTP
     * @param  string $uri    URI de la
     *                        requête
     * @return mixed
     */
    public function dispatch(string $method, string $uri)
    {
        // Débogage: Enregistrer dans un fichier que le routeur est exécuté
        $logPath = dirname(__DIR__, 2) . '/logs/router_debug.log';
        file_put_contents($logPath, "Router::dispatch exécuté à " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        file_put_contents($logPath, "Méthode: $method, URI: $uri\n", FILE_APPEND);

        // Nettoyer l'URI
        $uri = parse_url($uri, PHP_URL_PATH);

        // Initialiser le tableau de paramètres
        $params = [];
        // Trouver la route correspondante
        $route = $this->findRoute($method, $uri, $params);

        if ($route === null) {
            // Route non trouvée
            file_put_contents($logPath, "Route non trouvée pour $method $uri\n\n", FILE_APPEND);
            http_response_code(404);
            return [
                'error' => 'Not Found',
                'message' => 'La route demandée n\'existe pas'
            ];
        }

        file_put_contents($logPath, "Route trouvée: " . $route->getHandler() . "\n", FILE_APPEND);

        // Extraire le contrôleur et la méthode
        list($controllerName, $methodName) = explode('@', $route->getHandler());

        // Préfixer le nom du contrôleur avec le namespace
        $controllerClass = "\\App\\Controllers\\$controllerName";

        // Vérifier si le contrôleur existe
        if (!class_exists($controllerClass)) {
            // Contrôleur non trouvé
            file_put_contents($logPath, "Contrôleur non trouvé: $controllerClass\n\n", FILE_APPEND);
            http_response_code(500);
            return [
                'error' => 'Server Error',
                'message' => "Le contrôleur $controllerName n'existe pas"
            ];
        }

        // Créer une instance du contrôleur
        $controller = new $controllerClass();

        // Vérifier si la méthode existe
        if (!method_exists($controller, $methodName)) {
            // Méthode non trouvée
            file_put_contents($logPath, "Méthode non trouvée: $methodName dans $controllerClass\n\n", FILE_APPEND);
            http_response_code(500);
            return [
                'error' => 'Server Error',
                'message' => "La méthode $methodName n'existe pas dans le contrôleur $controllerName"
            ];
        }

        // Exécuter les middlewares
        $middlewares = array_merge($this->middlewares, $route->getMiddlewares());
        file_put_contents($logPath, "Middlewares à exécuter: " . json_encode($middlewares) . "\n", FILE_APPEND);
        
        foreach ($middlewares as $middleware) {
            // Convertir auth -> AuthMiddleware, cors -> CorsMiddleware, etc.
            $middlewareClassname = ucfirst($middleware) . 'Middleware'; 
            $middlewareClass = "\\App\\Middlewares\\$middlewareClassname";
            
            file_put_contents($logPath, "Tentative de chargement du middleware: $middlewareClass\n", FILE_APPEND);

            if (!class_exists($middlewareClass)) {
                // Log ou gérer l'erreur de middleware non trouvé
                file_put_contents($logPath, "ERREUR: Middleware non trouvé: $middlewareClass\n", FILE_APPEND);
                continue;
            }

            $middlewareInstance = new $middlewareClass();
            file_put_contents($logPath, "Middleware instancié: $middlewareClass\n", FILE_APPEND);
            
            $result = $middlewareInstance->handle();
            
            if (is_array($result) && isset($result['error']) && $result['error'] === true) {
                file_put_contents($logPath, "Middleware a retourné une erreur: " . json_encode($result) . "\n\n", FILE_APPEND);
            } else {
                file_put_contents($logPath, "Middleware exécuté avec succès\n", FILE_APPEND);
            }

            if ($result !== true) {
                return $result;
            }
        }

        file_put_contents($logPath, "Tous les middlewares ont été exécutés avec succès\n", FILE_APPEND);
        file_put_contents($logPath, "Appel du contrôleur: $controllerClass->$methodName\n\n", FILE_APPEND);

        // Appeler la méthode du contrôleur avec les paramètres extraits
        return $controller->$methodName(...array_values($params));
    }

    /**
     * Trouve une route correspondant à la méthode HTTP et à l'URI
     *
     * @param  string $method  Méthode
     *                         HTTP
     * @param  string $uri     URI de la
     *                         requête
     * @param  array  &$params Paramètres extraits de
     *                         l'URI
     * @return Route|null
     */
    private function findRoute(string $method, string $uri, array &$params = []): ?Route
    {
        foreach ($this->routes as $route) {
            // Vérifier si la méthode HTTP correspond
            if ($route->getMethod() !== $method) {
                continue;
            }

            // Convertir le chemin de la route en expression régulière
            $pattern = $this->convertRouteToRegex($route->getPath(), $paramNames);

            // Vérifier si l'URI correspond au pattern
            if (preg_match($pattern, $uri, $matches)) {
                // Extraire les paramètres
                for ($i = 1; $i < count($matches); $i++) {
                    $params[$paramNames[$i - 1]] = $matches[$i];
                }

                return $route;
            }
        }

        return null;
    }

    /**
     * Convertit un chemin de route en expression régulière
     *
     * @param  string $path        Chemin de la route
     * @param  array  &$paramNames Noms des paramètres
     *                             extraits
     * @return string
     */
    private function convertRouteToRegex(string $path, array &$paramNames = null): string
    {
        // Initialiser le tableau de paramètres
        $paramNames = [];

        // Remplacer les paramètres {param} par des groupes de capture
        $pattern = preg_replace_callback(
            '/{([^\/]+)}/',
            function ($matches) use (&$paramNames) {
                $paramNames[] = $matches[1];
                // Capture un segment sans slash, l'échappement sera géré ensuite
                return '([^/]+)';
            },
            $path
        );

        // Échapper les caractères spéciaux
        $pattern = str_replace('/', '\/', $pattern);

        // Ajouter les délimiteurs
        return '/^' . $pattern . '$/';
    }
}
