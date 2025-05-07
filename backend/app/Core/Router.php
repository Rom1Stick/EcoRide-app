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
        // Nettoyer l'URI
        $uri = parse_url($uri, PHP_URL_PATH);

        // Trouver la route correspondante
        $route = $this->findRoute($method, $uri, $params);

        if ($route === null) {
            // Route non trouvée
            http_response_code(404);
            return [
                'error' => 'Not Found',
                'message' => 'La route demandée n\'existe pas'
            ];
        }

        // Extraire le contrôleur et la méthode
        list($controllerName, $methodName) = explode('@', $route->getHandler());

        // Préfixer le nom du contrôleur avec le namespace
        $controllerClass = "\\App\\Controllers\\$controllerName";

        // Vérifier si le contrôleur existe
        if (!class_exists($controllerClass)) {
            // Contrôleur non trouvé
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
            http_response_code(500);
            return [
                'error' => 'Server Error',
                'message' => "La méthode $methodName n'existe pas dans le contrôleur $controllerName"
            ];
        }

        // Exécuter les middlewares
        $middlewares = array_merge($this->middlewares, $route->getMiddlewares());
        foreach ($middlewares as $middleware) {
            $middlewareClass = "\\App\\Middlewares\\$middleware";

            if (!class_exists($middlewareClass)) {
                continue;
            }

            $middlewareInstance = new $middlewareClass();
            $result = $middlewareInstance->handle();

            if ($result !== true) {
                return $result;
            }
        }

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
        // Initialiser le tableau de paramètres
        $params = [];
        
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
                return '([^\/]+)';
            },
            $path
        );

        // Échapper les caractères spéciaux
        $pattern = str_replace('/', '\/', $pattern);

        // Ajouter les délimiteurs
        return '/^' . $pattern . '$/';
    }
}
